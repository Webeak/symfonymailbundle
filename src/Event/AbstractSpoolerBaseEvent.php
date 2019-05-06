<?php
namespace Webeak\Bundle\MailBundle\Event;

use Webeak\Bundle\MailBundle\MessageInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base event for all spooler's events.
 */
abstract class AbstractSpoolerBaseEvent extends Event
{
    /** @var MessageInterface */
    protected $message;

    public function __construct(MessageInterface $message)
    {
        $this->message = $message;
    }

    /**
     * Gets the message that has been queued.
     *
     * @return MessageInterface
     */
    public function getMessage()
    {
        return $this->message;
    }
}
