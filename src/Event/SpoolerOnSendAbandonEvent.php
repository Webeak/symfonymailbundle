<?php
namespace Webeak\Bundle\MailBundle\Event;

use Webeak\Bundle\MailBundle\MessageInterface;

/**
 * Spooler's 'spooler:send-abandon' event.
 */
class SpoolerOnSendAbandonEvent extends AbstractSpoolerBaseEvent
{
    /** @var string */
    protected $reason;

    public function __construct(MessageInterface $message, $reason)
    {
        parent::__construct($message);
        $this->reason = $reason;
    }

    /**
     * Get the failure reason.
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }
}
