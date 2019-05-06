<?php
namespace Webeak\Bundle\MailBundle\Bridge\SwiftMailer;

use Webeak\Bundle\MailBundle\MailerInterface;
use Webeak\Component\Utils\ArrayUtils;

/**
 * SwiftMailer proxy.
 */
class SwiftMailer implements MailerInterface
{
    /** @var \Swift_Mailer */
    protected $instance;

    /** @var \Swift_Transport */
    protected $realTransport;

    public function __construct(\Swift_Mailer $mailer, \Swift_Transport $realTransport)
    {
        $this->instance = $mailer;
        $this->realTransport = $realTransport;
    }

    /**
     * Send one or multiples messages using SwiftMailer.
     *
     * @param mixed $messages
     *
     * @return integer
     */
    public function send($messages)
    {
        $successCount = 0;
        $messages = ArrayUtils::ensureArray($messages);
        foreach ($messages as $message) {
            /** @var \Swift_Message $message */
            $successCount += $this->instance->send($message);
        }
        /** @var \Swift_Spool $spool */
        $spoolTransport = $this->instance->getTransport();
        if ($spoolTransport instanceof \Swift_Transport_SpoolTransport) {
            $spool = $spoolTransport->getSpool();
            if ($spool) {
                $spool->flushQueue($this->realTransport);
            }
        }
        return $successCount;
    }
}
