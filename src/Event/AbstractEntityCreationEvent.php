<?php
namespace Webeak\Bundle\MailBundle\Event;

use Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedMessageEntityInterface;
use Symfony\Component\EventDispatcher\Event;

class AbstractEntityCreationEvent extends Event
{
    /** @var TrackedMessageEntityInterface */
    protected $entity;

    /** @var boolean */
    protected $autoSave;

    public function __construct()
    {
        $this->entity = null;
        $this->autoSave = false;
    }

    /**
     * Sets the entity.
     *
     * @param mixed $entity
     *
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * Gets the message entity.
     *
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set if the entity is should be persisted by the tracker or not.
     *
     * By default, its false. So if the entity is created outside of the tracker,
     * the tracker will not persist/flush it.
     *
     * Setting this to true will add the entity to the list of scheduled persist/flush.
     * This will probably fail for entities with relations. So set it to true only if no other
     * entity depends on it.
     *
     * @param boolean $value
     *
     * @return $this
     */
    public function setAutoSave(bool $value)
    {
        $this->autoSave = $value;
        return $this;
    }

    /**
     * Get if the entity will be auto saved by the tracker.
     *
     * @return boolean
     */
    public function getAutoSave()
    {
        return $this->autoSave;
    }
}
