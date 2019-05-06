<?php
namespace Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity;

interface TrackedLinkEntityInterface
{
    /**
     * Gets the unique string identifier of the link.
     *
     * @return string
     */
    public function getIdentifier();

    /**
     * Sets the unique string identifier of the link.
     *
     * @param string $identifier
     *
     * @return TrackedLinkEntityInterface
     */
    public function setIdentifier(string $identifier);

    /**
     * Set the real url of the link.
     *
     * @param string $url
     *
     * @return TrackedLinkEntityInterface
     */
    public function setUrl(string $url);

    /**
     * Get the real url of the link.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Set how many time the link have been clicked.
     *
     * @param integer $clickCount
     *
     * @return TrackedLinkEntityInterface
     */
    public function setClickCount(int $clickCount);

    /**
     * Get how many times the link has been clicked.
     *
     * @return integer
     */
    public function getClickCount(): int;

    /**
     * Set the last time the link was clicked.
     *
     * @param \DateTime $lastClickDate
     *
     * @return TrackedLinkEntityInterface
     */
    public function setLastClickDate(\DateTime $lastClickDate);

    /**
     * Get the last time the link was clicked.
     *
     * @return \DateTime|null
     */
    public function getLastClickDate(): ?\DateTime;

    /**
     * Set custom extras data.
     *
     * @param array $extras
     *
     * @return TrackedLinkEntityInterface
     */
    public function setExtras(array $extras);

    /**
     * Get custom extras data.
     *
     * @return array
     */
    public function getExtras(): array;

    /**
     * Set the tracked message associated with the link.
     *
     * @param TrackedMessageEntityInterface $message
     *
     * @return TrackedLinkEntityInterface
     */
    public function setTrackedMessage(TrackedMessageEntityInterface $message);

    /**
     * Get the tracked message associated with the link.
     *
     * @return TrackedMessageEntityInterface
     */
    public function getTrackedMessage(): TrackedMessageEntityInterface;
}
