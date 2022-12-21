<?php
namespace Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity;

use Doctrine\Common\Collections\ArrayCollection;

interface TrackedMessageEntityInterface
{
    /**
     * Gets the unique string identifier of the message.
     *
     * @return string
     */
    public function getIdentifier();

    /**
     * Sets the subject of the message.
     *
     * @param string $subject
     *
     * @return TrackedMessageEntityInterface
     */
    public function setSubject(?string $subject);

    /**
     * Sets the from of the message.
     *
     * @param string $from
     *
     * @return TrackedMessageEntityInterface
     */
    public function setFrom(?array $from);

    /**
     * Sets the to of the message.
     *
     * @param array|null $to
     *
     * @return TrackedMessageEntityInterface
     */
    public function setTo(?array $to);

    /**
     * Sets the cc of the message.
     *
     * @param array|null $cc
     *
     * @return TrackedMessageEntityInterface
     */
    public function setCc(?array $cc);

    /**
     * Sets the bcc of the message.
     *
     * @param array|null $bcc
     *
     * @return TrackedMessageEntityInterface
     */
    public function setBcc(?array $to);

    /**
     * Sets the unique string identifier of the message.
     *
     * @param string $identifier
     *
     * @return TrackedMessageEntityInterface
     */
    public function setIdentifier(string $identifier);

    /**
     * Set if the message is sent.
     *
     * @param boolean $value
     *
     * @return TrackedMessageEntityInterface
     */
    public function setSent(bool $value);

    /**
     * Get if the message has been sent.
     *
     * @return boolean
     */
    public function getSent(): bool;

    /**
     * Set if the message is queued for sending.
     *
     * @param boolean $queued
     *
     * @return TrackedMessageEntityInterface
     */
    public function setQueued(bool $queued);

    /**
     * Get if the message is queued for sending.
     *
     * @return boolean
     */
    public function getQueued(): bool;

    /**
     * Set if the message has failed to be sent and will never retry.
     *
     * @param boolean $value
     *
     * @return TrackedMessageEntityInterface
     */
    public function setFailed(bool $value);

    /**
     * Get if the message has failed to be sent and will never retry.
     *
     * @return boolean
     */
    public function getFailed(): bool;

    /**
     * Set errors messages describing why each try failed to send.
     *
     * @param string[] $reasons
     *
     * @return TrackedMessageEntityInterface
     */
    public function setFailureReasons(array $reasons);

    /**
     * Get error messages describing why each try failed to send.
     *
     * @return string[]
     */
    public function getFailureReasons(): array;

    /**
     * Set the error message describing why the message has been abandoned.
     *
     * @param string $reason
     *
     * @return TrackedMessageEntityInterface
     */
    public function setAbandonReason(string $reason);

    /**
     * Get the error message describing why the message has been abandoned.
     *
     * @return string|null
     */
    public function getAbandonReason(): ?string;

    /**
     * Set the number of times the message has tried to be sent.
     *
     * @param integer $count
     *
     * @return TrackedMessageEntityInterface
     */
    public function setSendTryCount(int $count);

    /**
     * Get the number of times the message has tried to be sent.
     *
     * @return integer
     */
    public function getSendTryCount(): int;

    /**
     * Set the number of time the message has been opened.
     *
     * @param integer $openCount
     *
     * @return TrackedMessageEntityInterface
     */
    public function setOpenCount(int $openCount);

    /**
     * Get the number of times the message has been opened.
     *
     * @return integer
     */
    public function getOpenCount(): int;

    /**
     * Set the date of the last opening.
     *
     * @param \DateTime $lastOpenDate
     *
     * @return TrackedMessageEntityInterface
     */
    public function setLastOpenDate(\DateTime $lastOpenDate);

    /**
     * Get the last opening date.
     *
     * @return \DateTime|null
     */
    public function getLastOpenDate(): ?\DateTime;

    /**
     * Set the date at which the message has been successfully sent.
     *
     * @param \DateTime $sendDate
     *
     * @return TrackedMessageEntityInterface
     */
    public function setSendDate(\DateTime $sendDate);

    /**
     * Get the date at which the message has been successfully sent.
     *
     * @return \DateTime|null
     */
    public function getSendDate(): ?\DateTime;

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
     * Add a tracked link.
     *
     * @param TrackedLinkEntityInterface $link
     *
     * @return TrackedMessageEntityInterface
     */
    public function addTrackedLink(TrackedLinkEntityInterface $link);

    /**
     * Remove a tracked link.
     *
     * @param TrackedLinkEntityInterface $link
     *
     * @return TrackedMessageEntityInterface
     */
    public function removeTrackedLink(TrackedLinkEntityInterface $link);

    /**
     * Get all tracked links.
     *
     * @return ArrayCollection
     */
    public function getTrackedLinks();
}
