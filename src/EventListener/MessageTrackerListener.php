<?php
namespace Webeak\Bundle\MailBundle\EventListener;

use Webeak\Bundle\MailBundle\Event\SpoolerOnBatchEndEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnInstantSendEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnQueueEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendAbandonEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendFailureEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendSuccessEvent;
use Webeak\Bundle\MailBundle\MessageTracker;

class MessageTrackerListener
{
    /** @var MessageTracker */
    private $tracker;

    public function __construct(MessageTracker $tracker)
    {
        $this->tracker = $tracker;
    }

    /**
     * Spooler 'spooler:queue' listener.
     *
     * @param SpoolerOnQueueEvent $event
     */
    public function onQueue(SpoolerOnQueueEvent $event)
    {
        $entity = $this->tracker->getTrackedMessageEntity($event->getMessage());
        if ($entity !== null) {
            $entity->setQueued(true);
            // Queue publication cannot rely on the caller or shutdown to save
            // this entity: another process may consume the file immediately.
            $this->tracker->persist($entity, true);
            $this->tracker->flush(true);
        }
    }

    /**
     * Spooler 'spooler:send-instant' listener.
     *
     * @param SpoolerOnInstantSendEvent $event
     */
    public function onInstantSend(SpoolerOnInstantSendEvent $event)
    {
        $entity = $this->tracker->getTrackedMessageEntity($event->getMessage());
        if ($entity !== null) {
            $entity->setSendTryCount(intval($entity->getSendTryCount()) + 1);
            $this->tracker->persist($entity);
        }
    }

    /**
     * Spooler 'spooler:send' listener.
     *
     * @param SpoolerOnSendEvent $event
     */
    public function onSend(SpoolerOnSendEvent $event)
    {
        $entity = $this->tracker->getTrackedMessageEntity($event->getMessage());
        if ($entity !== null) {
            $entity->setSendTryCount(intval($entity->getSendTryCount()) + 1);
            $this->tracker->persist($entity);
        }
    }

    /**
     * Spooler 'spooler:send-success' listener.
     *
     * @param SpoolerOnSendSuccessEvent $event
     *
     * @throws
     */
    public function onSendSuccess(SpoolerOnSendSuccessEvent $event)
    {
        $entity = $this->tracker->getTrackedMessageEntity($event->getMessage());
        if ($entity !== null) {
            $entity->setSent(true);
            $entity->setQueued(false);
            $entity->setSendDate(new \DateTime());
            $this->tracker->persist($entity);
        }
    }

    /**
     * Spooler 'spooler:send-failure' listener.
     *
     * @param SpoolerOnSendFailureEvent $event
     */
    public function onSendFailure(SpoolerOnSendFailureEvent $event)
    {
        $entity = $this->tracker->getTrackedMessageEntity($event->getMessage());
        if ($entity !== null) {
            $reasons = $entity->getFailureReasons();
            $reasons[] = $event->getReason();
            $entity->setFailed(true);
            $entity->setSent(false);
            $entity->setFailureReasons($reasons);
            $this->tracker->persist($entity);
        }
    }

    /**
     * Spooler 'spooler:send-abandon' listener.
     *
     * @param SpoolerOnSendAbandonEvent $event
     */
    public function onSendAbandon(SpoolerOnSendAbandonEvent $event)
    {
        $entity = $this->tracker->getTrackedMessageEntity($event->getMessage());
        if ($entity !== null) {
            $entity->setAbandonReason($event->getReason());
            $entity->setQueued(false);
            $entity->setFailed(true);
            $this->tracker->persist($entity);
        }
    }

    /**
     * Spooler 'spooler:batch-end' listener.
     *
     * @param SpoolerOnBatchEndEvent $event
     */
    public function onBatchEnd(SpoolerOnBatchEndEvent $event)
    {
        $this->tracker->flush();
    }

    /**
     * Symfony 'kernel.terminate' event.
     */
    public function onKernelTerminate()
    {
        $this->tracker->flush();
    }
}
