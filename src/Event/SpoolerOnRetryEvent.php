<?php
namespace Webeak\Bundle\MailBundle\Event;

use Webeak\Bundle\MailBundle\MessageInterface;

/**
 * Spooler's 'spooler:retry' event.
 */
class SpoolerOnRetryEvent extends AbstractSpoolerBaseEvent
{
    /** @var integer */
    private $retryCount;

    /** @var integer */
    private $triesLeft;

    public function __construct(MessageInterface $message, $identifier, $retryCount, $triesLeft)
    {
        parent::__construct($message, $identifier);
        $this->retryCount = $retryCount;
        $this->triesLeft = $triesLeft;
    }

    /**
     * Gets the number of tries consumed for this message.
     *
     * @return integer
     */
    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    /**
     * Gets the number of tries left for this message.
     *
     * @return integer
     */
    public function getTriesLeft(): int
    {
        return $this->triesLeft;
    }
}
