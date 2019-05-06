<?php
namespace Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity;

use Doctrine\ORM\Mapping as ORM;
use Webeak\Bundle\DoctrineExtensionsBundle\Entity\AbstractBasicEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="wb_mail_tracked_link")
 */
class TrackedLink extends AbstractBasicEntity implements TrackedLinkEntityInterface
{
    /**
     * @ORM\Column(type="string", length=16, unique=true, nullable=false, options={"collation":"utf8_bin"})
     */
    private $identifier;
    
    /**
     * @ORM\Column(type="text", nullable=false)
     */
    private $url;

    /**
     * @ORM\Column(type="smallint")
     */
    private $clickCount;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastClickDate;

    /**
     * @ORM\Column(type="json", nullable=false)
     */
    private $extras;

    /**
     * @ORM\ManyToOne(targetEntity="TrackedMessage", inversedBy="trackedLinks")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", nullable=false)
     */
    private $trackedMessage;

    public function __construct()
    {
        $this->clickCount = 0;
        $this->lastClickDate = null;
        $this->extras = [];
    }

    /**
     * Gets the unique string identifier of the link.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Sets the unique string identifier of the link.
     *
     * @param string $identifier
     *
     * @return TrackedLinkEntityInterface
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Set the real url of the link.
     *
     * @param string $url
     *
     * @return TrackedLinkEntityInterface
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
        return $this;
    }
    
    /**
     * Get the real url of the link.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set how many time the link have been clicked.
     *
     * @param integer $clickCount
     *
     * @return TrackedLinkEntityInterface
     */
    public function setClickCount(int $clickCount)
    {
        $this->clickCount = $clickCount;
        return $this;
    }

    /**
     * Get how many times the link has been clicked.
     *
     * @return integer
     */
    public function getClickCount(): int
    {
        return $this->clickCount;
    }

    /**
     * Set the last time the link was clicked.
     *
     * @param \DateTime $lastClickDate
     *
     * @return TrackedLinkEntityInterface
     */
    public function setLastClickDate(\DateTime $lastClickDate)
    {
        $this->lastClickDate = $lastClickDate;
        return $this;
    }

    /**
     * Get the last time the link was clicked.
     *
     * @return \DateTime|null
     */
    public function getLastClickDate(): ?\DateTime
    {
        return $this->lastClickDate;
    }

    /**
     * Set custom extras data.
     *
     * @param array $extras
     *
     * @return TrackedLinkEntityInterface
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
     * Set the tracked message associated with the link.
     *
     * @param TrackedMessageEntityInterface $message
     *
     * @return TrackedLinkEntityInterface
     */
    public function setTrackedMessage(TrackedMessageEntityInterface $message)
    {
        $this->trackedMessage = $message;

        return $this;
    }

    /**
     * Get the tracked message associated with the link.
     *
     * @return TrackedMessageEntityInterface
     */
    public function getTrackedMessage(): TrackedMessageEntityInterface
    {
        return $this->trackedMessage;
    }
}
