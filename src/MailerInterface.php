<?php
namespace Webeak\Bundle\MailBundle;

/**
 * Represent a mailer.
 *
 * @package Webeak\Bundle\MailBundle
 */
interface MailerInterface
{
    /**
     * Send one or multiples messages.
     *
     * @param mixed $messages
     *
     * @return integer
     */
    public function send($messages);
}
