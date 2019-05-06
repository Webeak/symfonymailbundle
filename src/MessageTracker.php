<?php
namespace Webeak\Bundle\MailBundle;

use Webeak\Bundle\ErrorTrackerBundle\ErrorTrackerInterface;
use Webeak\Bundle\EssentialBundle\Exception\InvalidArgumentException;
use Webeak\Bundle\EssentialBundle\Exception\InvalidConfigurationException;
use Webeak\Bundle\EssentialBundle\Exception\RuntimeException;
use Webeak\Bundle\EssentialBundle\UniqueIdGenerator;
use Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedLinkEntityInterface;
use Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedMessageEntityInterface;
use Webeak\Bundle\MailBundle\Event\MessageTrackerOnCreateMessageEntityEvent;
use Webeak\Bundle\MailBundle\Event\MessageTrackerOnCreateMessageLinkEvent;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webeak\Component\Utils\ArrayUtils;

/**
 * Uses spooler's events to track messages.
 */
class MessageTracker
{
    /** @var EntityManager */
    private $entityManager;

    /** @var Router */
    private $router;

    /** @var EventDispatcher */
    private $dispatcher;

    /** @var UniqueIdGenerator */
    private $uniqueIdGenerator;

    /** @var ErrorTrackerInterface */
    private $errorTracker;

    /** @var string */
    private $messageEntityClass;

    /** @var string */
    private $messageEntityIdentifierAttr;

    /** @var string */
    private $linkEntityClass;

    /** @var string */
    private $linkEntityIdentifierAttr;

    /** @var MessageInterface */
    private $trackedMessages;

    /** @var array */
    private $trackedLinks;

    /** @var mixed */
    private $waitingForPersist;

    /** @var TrackedMessageEntityInterface[] */
    private $trackedMessagesEntities;

    /** @var TrackedMessageEntityInterface[] */
    private $trackedLinksEntities;

    /**
     * List of entities' identifiers for which persistence is handled by the tracker.
     *
     * @var string[]
     */
    private $autoPersistEntitiesIdentifiers;

    /** @var boolean */
    private $hasBeenFlushed;

    public function __construct(Router $router,
                                EntityManager $entityManager,
                                UniqueIdGenerator $uniqueIdGenerator,
                                ErrorTrackerInterface $errorTracker,
                                array $configuration)
    {
        $this->dispatcher = new EventDispatcher();
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->messageEntityClass = ArrayUtils::getValue($configuration, 'message_entity_class');
        $this->messageEntityIdentifierAttr = ArrayUtils::getValue($configuration, 'message_entity_identifier_attr');
        $this->linkEntityClass = ArrayUtils::getValue($configuration, 'link_entity_class');
        $this->linkEntityIdentifierAttr = ArrayUtils::getValue($configuration, 'link_entity_identifier_attr');
        $this->uniqueIdGenerator = $uniqueIdGenerator;
        $this->errorTracker = $errorTracker;
        $this->trackedMessages = [];
        $this->trackedMessagesEntities = [];
        $this->trackedLinksEntities = [];
        $this->trackedLinks = [];
        $this->waitingForPersist = [];
        $this->autoPersistEntitiesIdentifiers = [];
        $this->hasBeenFlushed = false;
    }

    /**
     * Register a message to track.
     *
     * @param MessageInterface $message
     *
     * @return $this
     */
    public function trackMessage(MessageInterface $message)
    {
        if ($this->isMessageTracked($message)) {
            return $this;
        }
        $identifier = $message->getIdentifier();
        $this->trackedMessages[$identifier] = $message;
        $this->trackedMessagesEntities[$identifier] = $this->createTrackedMessageEntity($message);
        return $this;
    }

    /**
     * Wrap a link into a proxy action managed by the tracker.
     *
     * @param MessageInterface $message
     * @param string           $url
     * @param array            $extra   (optional) extra data you want to associate with the link
     *
     * @return string the new url
     */
    public function trackLink(MessageInterface $message, $url, array $extra = [])
    {
        if (!$this->isMessageTracked($message)) {
            $this->trackMessage($message);
        }
        $messageIdentifier = $message->getIdentifier();
        $messageEntity = $this->trackedMessagesEntities[$messageIdentifier];
        if (!array_key_exists($messageIdentifier, $this->trackedLinks)) {
            $this->trackedLinks[$messageIdentifier] = [];
        }
        if (!array_key_exists($messageIdentifier, $this->trackedLinksEntities)) {
            $this->trackedLinksEntities[$messageIdentifier] = [];
        }
        $linkIdentifier = $this->uniqueIdGenerator->generateId(16);
        $this->trackedLinks[$messageIdentifier][$linkIdentifier] = $url;
        $this->trackedLinksEntities[$messageIdentifier][$linkIdentifier] = $this->createTrackedLinkEntity($message, $messageEntity, $linkIdentifier, $url, $extra);
        return $this->router->generate('wb_mail_link', ['identifier' => $linkIdentifier], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Test if a message is tracked.
     *
     * @param MessageInterface $message
     *
     * @return boolean
     */
    public function isMessageTracked(MessageInterface $message): bool
    {
        return array_key_exists($message->getIdentifier(), $this->trackedMessages);
    }

    /**
     * Try to get the TrackedMessageEntityInterface corresponding to a message.
     *
     * @param MessageInterface $message
     *
     * @return TrackedMessageEntityInterface|null
     */
    public function getTrackedMessageEntity(MessageInterface $message): ?TrackedMessageEntityInterface
    {
        $identifier = $message->getIdentifier();
        if (array_key_exists($identifier, $this->trackedMessagesEntities)) {
            return $this->trackedMessagesEntities[$identifier];
        }
        try {
            if ($this->messageEntityClass && $this->messageEntityIdentifierAttr) {
                $entity = $this->entityManager->getRepository($this->messageEntityClass)->findOneBy([$this->messageEntityIdentifierAttr => $identifier]);
                if ($entity instanceof TrackedMessageEntityInterface) {
                    $this->trackedMessagesEntities[$identifier] = $entity;

                    // If we need to get the entity from the database it means that we are in a different process
                    // that the one that created the message originally.
                    // In this case we can persist the entity no matter its relations.
                    if (!in_array($identifier, $this->autoPersistEntitiesIdentifiers)) {
                        $this->autoPersistEntitiesIdentifiers[] = $identifier;
                    }
                    return $entity;
                }
            } else {
                $this->errorTracker->track(new InvalidConfigurationException(
                    'You cannot call getTrackedMessageEntity() without a setting '.
                    '"tracker->message_entity_class" and "tracker->message_entity_identifier_attr'
                ));
            }
        } catch (\Exception $e) {
            $this->errorTracker->track($e);
        }
        return null;
    }

    /**
     * Try to get the TrackedLinkEntityInterface corresponding to an identifier.
     *
     * @param string $identifier
     *
     * @return TrackedMessageEntityInterface|null
     */
    public function getTrackedLinkEntity(string $identifier): ?TrackedLinkEntityInterface
    {
        foreach ($this->trackedLinksEntities as $entityIdentifier => $links) {
            foreach ($links as $linkIdentifier => $link) {
                /** @var TrackedLinkEntityInterface $link */
                if ($linkIdentifier === $identifier) {
                    return $link;
                }
            }
        }
        try {
            if ($this->linkEntityClass && $this->linkEntityIdentifierAttr) {
                $entity = $this->entityManager->getRepository($this->linkEntityClass)->findOneBy([$this->linkEntityIdentifierAttr => $identifier]);
                if ($entity instanceof TrackedLinkEntityInterface) {
                    $this->trackedMessagesEntities[$identifier] = $entity;
                    return $entity;
                }
            } else {
                $this->errorTracker->track(new InvalidConfigurationException(
                    'You cannot call getTrackedLinkEntity() without a setting '.
                    '"tracker->link_entity_class" and "tracker->link_entity_identifier_attr'
                ));
            }
        } catch (\Exception $e) {
            $this->errorTracker->track($e);
        }
        return null;
    }

    /**
     * Tell the tracker that a tracked message or link has changed so it can persist it if necessary.
     *
     * @param TrackedMessageEntityInterface|TrackedLinkEntityInterface $entity
     *
     * @throws
     */
    public function persist($entity)
    {
        if (!($entity instanceof TrackedMessageEntityInterface) && !($entity instanceof TrackedLinkEntityInterface)) {
            $this->errorTracker->trackAndThrow(new InvalidArgumentException(sprintf(
                'You can only persist entities that derive from "%s" or "%s".',
                TrackedMessageEntityInterface::class,
                TrackedLinkEntityInterface::class
            )));
        }
        $autoPersist = in_array($entity->getIdentifier(), $this->autoPersistEntitiesIdentifiers);
        $alreadyPersisted = in_array($entity, $this->waitingForPersist, true);
        if (($autoPersist || $this->hasBeenFlushed) && !$alreadyPersisted) {
            $this->waitingForPersist[] = $entity;
        }
    }

    /**
     * Persist and flush all entities waiting to be written in the database.
     */
    public function flush()
    {
        try {
            foreach ($this->waitingForPersist as $entity) {
                $this->entityManager->persist($entity);
            }
            $this->entityManager->flush($this->waitingForPersist);
            $this->waitingForPersist = [];
            $this->hasBeenFlushed = true;
        } catch (\Exception | \Throwable $e) {
            $this->errorTracker->track(new RuntimeException(
                sprintf('Failed to flush tracking entities. Reason: "%s".', $e->getMessage()),
                0,
                $e
            ));
        }
    }

    /**
     * Dependency injection callback for registering services bound using services' tags.
     *
     * @param mixed $service
     * @param array $attributes
     */
    public function registerEvent($service, $attributes)
    {
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }
        for ($i = 0, $ii = count($attributes); $i < $ii; ++$i) {
            if (array_key_exists('event', $attributes[$i])) {
                $event = $attributes[$i]['event'];
                $method = array_key_exists('method', $attributes[$i]) ? $attributes[$i]['method'] : $event;
                if (method_exists($service, $method)) {
                    $this->dispatcher->addListener($attributes[$i]['event'], [$service, $method]);
                } else {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Failed to register "%s" listener, no "%s" method found on "%s".',
                            $event,
                            $method,
                            get_class($service)
                        )
                    );
                }
            }
        }
    }

    /**
     * Get or create a TrackedMessageEntityInterface for a given message.
     *
     * @param MessageInterface $message
     *
     * @return TrackedMessageEntityInterface
     *
     * @throws
     */
    private function createTrackedMessageEntity(MessageInterface $message): TrackedMessageEntityInterface
    {
        $event = new MessageTrackerOnCreateMessageEntityEvent($message);
        $this->dispatcher->dispatch(Events::messageTrackerOnCreateMessageEntity, $event);
        $entity = $event->getEntity();
        $autoSave = $event->getAutoSave();
        if ($entity === null && $this->messageEntityClass) {
            $entity = new $this->messageEntityClass();
            $autoSave = true;
        }
        if (!is_object($entity)) {
            $this->errorTracker->trackAndThrow(new \InvalidArgumentException(
                'No valid tracking entity could be created. You must either define the "tracker->message_entity_class" parameter '.
                'or bind to the "tracker:create-message-entity" event to create one manually.'
            ));
        }
        if (!($entity instanceof TrackedMessageEntityInterface)) {
            $this->errorTracker->trackAndThrow(new \InvalidArgumentException(sprintf(
                'Invalid tracking entity "%s". It must implement "%s".',
                get_class($entity), TrackedMessageEntityInterface::class
            )));
        }
        /** @var TrackedMessageEntityInterface $entity */
        $identifier = $message->getIdentifier();
        $entity->setIdentifier($identifier);
        $entity->setExtras($message->getExtras());
        if ($autoSave) {
            $this->waitingForPersist[] = $entity;
            if (!in_array($identifier, $this->autoPersistEntitiesIdentifiers)) {
                $this->autoPersistEntitiesIdentifiers[] = $identifier;
            }
        }
        return $entity;
    }

    /**
     * Create a TrackedLinkEntityInterface for a message link.
     *
     * @param MessageInterface              $message
     * @param TrackedMessageEntityInterface $entity
     * @param string                        $identifier
     * @param string                        $url
     * @param array                         $extras
     *
     * @return TrackedLinkEntityInterface
     *
     * @throws
     */
    private function createTrackedLinkEntity(MessageInterface $message,
                                             TrackedMessageEntityInterface $entity,
                                             string $identifier,
                                             string $url,
                                             array $extras): TrackedLinkEntityInterface
    {
        $event = new MessageTrackerOnCreateMessageLinkEvent($identifier, $url, $entity, $message, $extras);
        $this->dispatcher->dispatch(Events::messageTrackerOnCreateLink, $event);
        $entity = $event->getEntity();
        $autoSave = $event->getAutoSave();
        if ($entity === null && $this->linkEntityClass) {
            $entity = new $this->linkEntityClass();
            $autoSave = true;
        }
        if (!is_object($entity)) {
            $this->errorTracker->trackAndThrow(new \InvalidArgumentException(
                'No valid tracking entity could be created. You must either define the "tracker->link_entity_class" parameter '.
                'or bind to the "tracker:create-link-entity" event to create one manually.'
            ));
        }
        if (!($entity instanceof TrackedLinkEntityInterface)) {
            $this->errorTracker->trackAndThrow(new \InvalidArgumentException(sprintf(
                'Invalid tracking entity "%s". It must implement "%s.',
                get_class($entity), TrackedLinkEntityInterface::class
            )));
        }
        /** @var TrackedMessageEntityInterface $messageEntity */
        $messageEntity = $this->getTrackedMessageEntity($message);
        if (!($messageEntity instanceof TrackedMessageEntityInterface)) {
            $this->errorTracker->trackAndThrow(new \InvalidArgumentException('No valid message entity found.'));
        }

        /** @var TrackedLinkEntityInterface $entity */
        $entity->setIdentifier($identifier);
        $entity->setUrl($url);
        $entity->setExtras($extras);
        $entity->setTrackedMessage($messageEntity);

        $messageEntity->addTrackedLink($entity);

        if ($autoSave) {
            $this->waitingForPersist[] = $entity;
            if (!in_array($identifier, $this->autoPersistEntitiesIdentifiers)) {
                $this->autoPersistEntitiesIdentifiers[] = $identifier;
            }
        }
        return $entity;
    }
}
