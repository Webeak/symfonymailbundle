<?php
namespace Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Webeak\Bundle\DoctrineExtensionsBundle\Entity\AbstractBasicEntity;
use Webeak\Component\Utils\ArrayUtils;

/**
 * @ORM\Entity
 * @ORM\Table(name="wb_mail_tracked_message")
 */
class TrackedMessage extends AbstractBasicEntity implements TrackedMessageEntityInterface
{
    /**
     * @ORM\Column(type="string", length=10, unique=true, nullable=false, options={"collation":"utf8_bin"})
     */
    protected $identifier;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $subject;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $sent;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $queued;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $failed;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    protected $failureReasons;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $abandonReason;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $sendTryCount;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $openCount;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastOpenDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $sendDate;

    /**
     * @ORM\Column(type="json", nullable=false)
     */
    protected $extras;

    /**
     * @ORM\OneToMany(targetEntity="TrackedLink", mappedBy="trackedMessage")
     */
    protected $trackedLinks;

    public function __construct()
    {
        $this->subject = '';
        $this->sent = false;
        $this->queued = false;
        $this->failed = false;
        $this->failureReasons = null;
        $this->abandonReason = null;
        $this->sendTryCount = 0;
        $this->openCount = 0;
        $this->lastOpenDate = null;
        $this->sendDate = null;
        $this->extras = [];
        $this->trackedLinks = new ArrayCollection();
    }

    /**
     * Gets the unique string identifier of the message.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Sets the unique string identifier of the message.
     *
     * @param string $identifier
     *
     * @return TrackedMessageEntityInterface
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Set if the message is sent.
     *
     * @param boolean $value
     *
     * @return TrackedMessageEntityInterface
     */
    public function setSent(bool $value)
    {
        $this->sent = $value;
        return $this;
    }

    /**
     * Get if the message has been sent.
     *
     * @return boolean
     */
    public function getSent(): bool
    {
        return $this->sent;
    }

    /**
     * Set if the message is queued for sending.
     *
     * @param boolean $queued
     *
     * @return TrackedMessageEntityInterface
     */
    public function setQueued(bool $queued)
    {
        $this->queued = $queued;
        return $this;
    }

    /**
     * Get if the message is queued for sending.
     *
     * @return boolean
     */
    public function getQueued(): bool
    {
        return $this->queued;
    }

    /**
     * Set if the message has failed to be sent and will never retry.
     *
     * @param boolean $value
     *
     * @return TrackedMessageEntityInterface
     */
    public function setFailed(bool $value)
    {
        $this->failed = $value;
        return $this;
    }

    /**
     * Get if the message has failed to be sent and will never retry.
     *
     * @return boolean
     */
    public function getFailed(): bool
    {
        return $this->failed;
    }

    /**
     * Set errors messages describing why each try failed to send.
     *
     * @param string[] $reasons
     *
     * @return TrackedMessageEntityInterface
     */
    public function setFailureReasons(array $reasons)
    {
        $this->failureReasons = $reasons;
        return $this;
    }

    /**
     * Get error messages describing why each try failed to send.
     *
     * @return string[]
     */
    public function getFailureReasons(): array
    {
        return ArrayUtils::ensureArray($this->failureReasons);
    }

    /**
     * Set the error message describing why the message has been abandoned.
     *
     * @param string $reason
     *
     * @return TrackedMessageEntityInterface
     */
    public function setAbandonReason(string $reason)
    {
        $this->abandonReason = $reason;
        return $this;
    }

    /**
     * Get the error message describing why the message has been abandoned.
     *
     * @return string|null
     */
    public function getAbandonReason(): ?string
    {
        return $this->abandonReason;
    }

    /**
     * Set the number of times the message has tried to be sent.
     *
     * @param integer $count
     *
     * @return TrackedMessageEntityInterface
     */
    public function setSendTryCount(int $count)
    {
        $this->sendTryCount = $count;
        return $this;
    }

    /**
     * Get the number of times the message has tried to be sent.
     *
     * @return integer
     */
    public function getSendTryCount(): int
    {
        return $this->sendTryCount;
    }

    /**
     * Set the number of time the message has been opened.
     *
     * @param integer $openCount
     *
     * @return TrackedMessageEntityInterface
     */
    public function setOpenCount(int $openCount)
    {
        $this->openCount = $openCount;
        return $this;
    }

    /**
     * Get the number of times the message has been opened.
     *
     * @return integer
     */
    public function getOpenCount(): int
    {
        return $this->openCount;
    }

    /**
     * Set the date of the last opening.
     *
     * @param \DateTime $lastOpenDate
     *
     * @return TrackedMessageEntityInterface
     */
    public function setLastOpenDate(\DateTime $lastOpenDate)
    {
        $this->lastOpenDate = $lastOpenDate;
        return $this;
    }

    /**
     * Get the last opening date.
     *
     * @return \DateTime|null
     */
    public function getLastOpenDate(): ?\DateTime
    {
        return $this->lastOpenDate;
    }

    /**
     * Set the date at which the message has been successfully sent.
     *
     * @param \DateTime $sendDate
     *
     * @return TrackedMessageEntityInterface
     */
    public function setSendDate(\DateTime $sendDate)
    {
        $this->sendDate = $sendDate;
        return $this;
    }

    /**
     * Get the date at which the message has been successfully sent.
     *
     * @return \DateTime|null
     */
    public function getSendDate(): ?\DateTime
    {
        return $this->sendDate;
    }

    /**
     * Set custom extras data.
     *
     * @param array $extras
     *
     * @return TrackedMessageEntityInterface
     */
    public function setExtras(array $extras)
    {
        $this->extras = $extras;
        return $this;
    }

    /**
     * Get custom extras data.
     *
     * @return array
     */
    public function getExtras(): array
    {
        return $this->extras;
    }

    /**
     * Add a tracked link.
     *
     * @param TrackedLinkEntityInterface $link
     *
     * @return TrackedMessageEntityInterface
     */
    public function addTrackedLink(TrackedLinkEntityInterface $link)
    {
        if (!$this->trackedLinks->contains($link)) {
            $this->trackedLinks->add($link);
        }
        return $this;
    }

    /**
     * Remove a tracked link.
     *
     * @param TrackedLinkEntityInterface $link
     *
     * @return TrackedMessageEntityInterface
     */
    public function removeTrackedLink(TrackedLinkEntityInterface $link)
    {
        if ($this->trackedLinks->contains($link)) {
            $this->trackedLinks->removeElement($link);
        }
        return $this;
    }

    /**
     * Get all tracked links.
     *
     * @return ArrayCollection
     */
    public function getTrackedLinks()
    {
        return $this->trackedLinks;
    }
}
