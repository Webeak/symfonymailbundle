<?php
namespace Webeak\Bundle\MailBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Webeak\Bundle\MailBundle\Spooler;

/**
 * Flush the spooler on kernel.terminate if no error occurred.
 */
class EmailSenderListener
{
    /** @var Spooler */
    private $spooler;

    /** @var boolean */
    private $isOnError;

    public function __construct(Spooler $spooler)
    {
        $this->spooler = $spooler;
        $this->isOnError = false;
    }

    /**
     * Called if the 'kernel.exception' event is fired.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $this->isOnError = true;
    }

    /**
     * Called when the 'kernel.terminate' event is fired.
     */
    public function onKernelTerminate()
    {
        if (!$this->isOnError) {
            $this->spooler->flush();
        }
    }
}
