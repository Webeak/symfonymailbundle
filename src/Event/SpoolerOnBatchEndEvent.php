<?php
namespace Webeak\Bundle\MailBundle\Event;

use Webeak\Bundle\MailBundle\MessageInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Spooler's 'spooler:batch-end' event.
 */
class SpoolerOnBatchEndEvent extends Event
{
    /** @var MessageInterface[] */
    protected $messages;

    public function __construct(array $messages)
    {
        $this->messages = $messages;
    }

    /**
     * Get the array of messages sent by this batch.
     *
     * @return array|MessageInterface[]
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
