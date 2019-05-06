<?php
namespace Webeak\Bundle\MailBundle\Event;

use Webeak\Bundle\MailBundle\MessageInterface;

/**
 * Message tracker's 'tracker:create-message-entity' event.
 */
class MessageTrackerOnCreateMessageEntityEvent extends AbstractEntityCreationEvent
{
    /** @var MessageInterface */
    private $message;

    public function __construct(MessageInterface $message)
    {
        parent::__construct();
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

    /**
     * Gets extra data associated with the message.
     *
     * @return array
     */
    public function getExtras(): array
    {
        return $this->extras;
    }
}
