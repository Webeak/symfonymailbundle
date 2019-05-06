<?php
namespace Webeak\Bundle\MailBundle\EventListener;

use Webeak\Bundle\ErrorTrackerBundle\ErrorTrackerInterface;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendAbandonEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendFailureEvent;
use Webeak\Bundle\MailBundle\MessageInterface;

/**
 * Forward errors to the bug tracker.
 */
class ErrorTrackerListener
{
    /** @var ErrorTrackerInterface */
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }

    /**
     * Spooler 'onSendFailure' listener.
     *
     * @param SpoolerOnSendFailureEvent $event
     */
    public function onSendFailure(SpoolerOnSendFailureEvent $event)
    {
        $message = $event->getReason();
        $this->errorTracker->track(new \RuntimeException($message), [
            'message' => $this->exportMessageGenericRepresentation($event->getMessage())
        ]);
    }

    /**
     * Spooler 'onSendAbandon' listener.
     *
     * @param SpoolerOnSendAbandonEvent $event
     */
    public function onSendAbandon(SpoolerOnSendAbandonEvent $event)
    {
        $message = $event->getReason();
        $this->errorTracker->track(new \RuntimeException($message), [
            'message' => $this->exportMessageGenericRepresentation($event->getMessage())
        ]);
    }

    /**
     * Export useful data from a message in a simple PHP array easy to process
     * by the exception tracker.
     *
     * @param MessageInterface $message
     *
     * @return array
     */
    private function exportMessageGenericRepresentation(MessageInterface $message)
    {
        return [
            'subject' => $message->getSubject(),
            'to' => $message->getTo(),
            'html' => $message->getHtml(),
            'text' => $message->getText()
        ];
    }
}
