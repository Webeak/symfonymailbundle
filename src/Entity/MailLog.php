<?php
namespace Webeak\Bundle\MailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Webeak\Bundle\EssentialBundle\Entity\AbstractEntity;

#[ORM\Entity]
#[ORM\Table(name: 'wb_mail_log')]
class MailLog extends AbstractEntity
{
    #[ORM\Column(type: "string", length: 45, unique: true, nullable: false, options: ["collation" => "utf8mb4_bin"])]
    protected $ref;

    #[ORM\Column(type: 'text')]
    private string $recipients;

    #[ORM\Column(type: 'string', length: 255)]
    private string $subject;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $html;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $text;

    #[ORM\Column(type: 'smallint')]
    private ?int $tryCount = 0;

    #[ORM\Column(type: 'smallint')]
    private ?int $openedCount = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $queued = true;

    #[ORM\Column(type: 'boolean')]
    private bool $sent = false;

    #[ORM\Column(type: 'boolean')]
    private bool $abandoned = false;

    #[ORM\Column(type: "text", nullable: true)]
    protected ?string $failureReason;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $extras = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    protected ?\DateTimeInterface $sendDate;

    #[ORM\Column(type: "datetime", nullable: true)]
    protected ?\DateTimeInterface $abandonDate;

    #[ORM\Column(type: "datetime", nullable: true)]
    protected ?\DateTimeInterface $lastOpenDate;

    #[ORM\Column(type: "json", nullable: true)]
    protected ?array $extra;

    public function setRecipients(string $recipients): self
    {
        $this->recipients = $recipients;
        return $this;
    }

    public function getRecipients(): string
    {
        return $this->recipients;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setHtml(?string $html): self
    {
        $this->html = $html;
        return $this;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setTryCount(?int $tryCount): self
    {
        $this->tryCount = $tryCount;
        return $this;
    }

    public function getTryCount(): ?int
    {
        return $this->tryCount;
    }

    public function setOpenedCount(?int $openedCount): self
    {
        $this->openedCount = $openedCount;
        return $this;
    }

    public function getOpenedCount(): ?int
    {
        return $this->openedCount;
    }

    public function setQueued(bool $queued): self
    {
        $this->queued = $queued;
        if ($this->queued) {
            $this->sent = false;
            $this->abandoned = false;
        }
        return $this;
    }

    public function isQueued(): bool
    {
        return $this->queued;
    }

    public function setSent(bool $sent): self
    {
        $this->sent = $sent;
        if ($this->sent) {
            $this->queued = false;
            $this->abandoned = false;
        }
        return $this;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function setAbandoned(bool $abandoned): self
    {
        $this->abandoned = $abandoned;
        if ($this->abandoned) {
            $this->sent = false;
            $this->queued = false;
        }
        return $this;
    }

    public function isAbandoned(): bool
    {
        return $this->abandoned;
    }

    public function setFailureReason(?string $failureReason): self
    {
        $this->failureReason = $failureReason;
        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setExtras(?array $extras): self
    {
        $this->extras = $extras;
        return $this;
    }

    public function getExtras(): ?array
    {
        return $this->extras;
    }

    public function setSendDate(?\DateTimeInterface $sendDate): self
    {
        $this->sendDate = $sendDate;
        return $this;
    }

    public function getSendDate(): ?\DateTimeInterface
    {
        return $this->sendDate;
    }

    public function setAbandonDate(?\DateTimeInterface $abandonDate): self
    {
        $this->abandonDate = $abandonDate;
        return $this;
    }

    public function getAbandonDate(): ?\DateTimeInterface
    {
        return $this->abandonDate;
    }

    public function setLastOpenDate(?\DateTimeInterface $lastOpenDate): self
    {
        $this->lastOpenDate = $lastOpenDate;
        return $this;
    }

    public function getLastOpenDate(): ?\DateTimeInterface
    {
        return $this->lastOpenDate;
    }

    public function setExtra(?array $extra): self
    {
        $this->extra = $extra;
        return $this;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }
}
