<?php
namespace Webeak\Bundle\MailBundle\Event;

use Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedMessageEntityInterface;
use Webeak\Bundle\MailBundle\MessageInterface;

/**
 * Message tracker's 'tracker:create-message-link' event.
 */
class MessageTrackerOnCreateMessageLinkEvent extends AbstractEntityCreationEvent
{
    /** @var string */
    private $identifier;

    /** @var string */
    private $url;

    /** @var MessageInterface */
    private $message;

    /** @var TrackedMessageEntityInterface */
    private $messageEntity;

    /** @var array */
    private $extras;

    public function __construct(string $identifier,
                                string $url,
                                TrackedMessageEntityInterface $messageEntity,
                                MessageInterface $message,
                                array $extras)
    {
        parent::__construct();
        $this->message = $message;
        $this->messageEntity = $messageEntity;
        $this->identifier = $identifier;
        $this->url = $url;
        $this->extras = $extras;
    }

    /**
     * Gets the message that has been queued.
     *
     * @return MessageInterface
     */
    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    /**
     * Gets the message doctrine entity.
     *
     * @return TrackedMessageEntityInterface
     */
    public function getMessageEntity(): TrackedMessageEntityInterface
    {
        return $this->messageEntity;
    }

    /**
     * Gets the link identifier.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Gets the link url.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Gets extra data associated with the link.
     *
     * @return array
     */
    public function getExtras(): array
    {
        return $this->extras;
    }
}
