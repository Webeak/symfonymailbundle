<?php
namespace Webeak\Bundle\MailBundle;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Webeak\Bundle\EssentialBundle\Exception\BadMethodCallException;
use Doctrine\ORM\EntityManager;
use Webeak\Component\Utils\ArrayUtils;

/**
 * Facade providing easy access to main services of the bundle.
 */
class Mailer
{
    /** @var RouterInterface */
    private $router;

    /** @var MessageBuilderFactory */
    private $messageBuilderFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var MessageTracker */
    private $messageTracker;

    /** @var Spooler */
    private $spooler;

    /** @var MessageBuilderInterface */
    private $currentBuilder;

    public function __construct(RouterInterface $router,
                                MessageBuilderFactory $messageBuilderFactory,
                                MessageTracker $messageTracker,
                                Spooler $spooler)
    {
        $this->router = $router;
        $this->messageBuilderFactory = $messageBuilderFactory;
        $this->messageTracker = $messageTracker;
        $this->spooler = $spooler;
        $this->entityManager = null;
        $this->currentBuilder = null;
    }

    /**
     * Creates a new message builder.
     *
     * @return MessageBuilderInterface
     */
    public function createMessageBuilder()
    {
        $this->currentBuilder = $this->messageBuilderFactory->create();
        return $this->currentBuilder;
    }

    /**
     * Enable tracking for one or multiple messages.
     *
     * @param MessageInterface|MessageBuilderInterface|MessageInterface[]|MessageBuilderInterface[] $messages
     *
     * @return $this
     */
    public function trackMessage($messages)
    {
        $messages = $this->ensureArrayOfMessages($messages);
        for ($i = 0, $ii = count($messages); $i < $ii; ++$i) {
            $this->messageTracker->trackMessage($messages[$i]);
            $trackingUrl = $this->router->generate('wb_mail_open', ['identifier' => $messages[$i]->getIdentifier()], UrlGeneratorInterface::ABSOLUTE_URL);
            $messages[$i]->addVariables([
                '_tracker' => '<img src="' . $trackingUrl . '" />'
            ]);
        }
        return $this;
    }

    /**
     * Wrap a link so it will be tracked when clicked.
     *
     * @param string $url   url to track
     * @param array  $extra extra data you want to associate with the link
     *
     * @return string the new link url
     *
     * @throws
     */
    public function trackLink($url, array $extra = [])
    {
        if ($this->currentBuilder === null) {
            throw new BadMethodCallException('You must call "createMessageBuilder()" before tracking a link.');
        }
        return $this->messageTracker->trackLink($this->currentBuilder->getMessage(), $url, $extra);
    }

    /**
     * Adds messages to the spooler queue for deferred sending.
     * The spooler will be flushed on 'kernel.terminate'.
     *
     * @param MessageInterface|MessageBuilderInterface|MessageInterface[]|MessageBuilderInterface[] $messages
     *
     * @return $this
     */
    public function send($messages)
    {
        $this->spooler->schedule($this->ensureArrayOfMessages($messages));
        return $this;
    }

    /**
     * Send messages immediately.
     *
     * In most cases you should use `send()` instead.
     * However, if your email needs variables that are not serializable, you will not be able to use deferred sending.
     * In such a case, using `sendNow()` can save you because it will not serialize your data before processing
     * your email.
     *
     * But you should still try to make the data you pass to a message serializable because if the instant send fails,
     * the spooler will try to queue it for retry, and will fail if your variables are not serializable.
     *
     * @param MessageInterface|MessageBuilderInterface|MessageInterface[]|MessageBuilderInterface[] $messages
     *
     * @return $this
     */
    public function sendNow($messages)
    {
        $this->spooler->instant($this->ensureArrayOfMessages($messages));
        return $this;
    }

    /**
     * Takes a message, array of message, builder or array of builders
     * and return an array of messages.
     *
     * @param MessageInterface|MessageBuilderInterface|MessageInterface[]|MessageBuilderInterface[] $messages
     *
     * @return MessageInterface[]
     */
    private function ensureArrayOfMessages($messages)
    {
        $messages = array_values(ArrayUtils::ensureArray($messages));
        for ($i = 0, $ii = count($messages); $i < $ii; ++$i) {
            if ($messages[$i] instanceof MessageBuilderInterface) {
                /** @var MessageBuilderInterface[] $messages */
                $messages[$i] = $messages[$i]->getMessage();
            }
        }
        return $messages;
    }
}
