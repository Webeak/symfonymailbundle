<?php
namespace Webeak\Bundle\MailBundle\Transport;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Webeak\Bundle\MailBundle\Entity\MailLog;
use Webeak\Component\Utils\RandomGenerator;

class TrackedTransportDecorator implements TransportInterface
{
    public function __construct(private readonly TransportInterface $decoratedTransport,
                                private readonly LoggerInterface $logger,
                                private readonly EntityManagerInterface $entityManager)
    {

    }

    public function send(RawMessage $message, Envelope $envelope = null): SentMessage
    {
        $this->createEmailLog($message);
        try {
            $sentMessage = $this->decoratedTransport->send($message, $envelope);
            $this->updateLog($message, function(MailLog $log) {
                $log->setSent(true);
                $log->setSendDate(new \DateTime());
            });
            return $sentMessage;
        } catch (\Throwable $e) {
            $this->updateLog($message, function(MailLog $log) use ($e) {
                $log->setAbandoned(true);
                $log->setFailureReason($e->getMessage());
            });
            throw $e;
        }
    }

    private function updateLog(RawMessage $message, callable $callback)
    {
        $log = $this->findEmailLog($message);
        if ($log !== null) {
            try {
                $callback($log);
                $this->entityManager->persist($log);
                $this->entityManager->flush();
            } catch (\Throwable $e) {
                $this->logger->warning(sprintf('Failed to update MailLog: %s', $e->getMessage()), ['exception' => $e]);
            }
        }
    }

    private function createEmailLog(RawMessage $email): ?MailLog
    {
        try {
            if (!($email instanceof Email)) {
                return null;
            }
            $log = $this->findEmailLog($email);
            if (!$log) {
                $log = new MailLog();
                $log->setRecipients($this->recipientsToString($email));
                $log->setRef(uniqid() . RandomGenerator::randomString(12));
                $log->setSubject($email->getSubject());
                $log->setHtml($email->getHtmlBody());
                $log->setText($email->getTextBody());
                $log->setTryCount(1);
                $email->getHeaders()->addHeader('x-tracked-ref', $log->getRef());
            } else {
                $log->setTryCount($log->getTryCount() + 1);
            }
            $log->setQueued(true);
            $this->entityManager->persist($log);
            $this->entityManager->flush();
            $this->knownLogs[$log->getRef()] = $log;
            return $log;
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
        return null;
    }

    private function recipientsToString(Email $email): string
    {
        $output = [];
        $allRecipients = array_merge(
            $email->getTo(),
            $email->getCc(),
            $email->getBcc()
        );
        foreach ($allRecipients as $recipient) {
            $recipientString = $recipient->getName()
                ? sprintf('%s <%s>', $recipient->getName(), $recipient->getAddress())
                : $recipient->getAddress();
            $output[] = $recipientString;
        }
        return implode(', ', $output);
    }

    private function findEmailLog(RawMessage $message): ?MailLog
    {
        $trackedRef = $this->getEmailTrackedRef($message);
        if (!$trackedRef) {
            return null;
        }
        return $this->entityManager->getRepository(MailLog::class)->findOneBy(['ref' => $trackedRef]);
    }

    private function getEmailTrackedRef(RawMessage $message): ?string
    {
        if (!($message instanceof Email) || !($trackedRefHeader = $message->getHeaders()->get('x-tracked-ref'))) {
            return null;
        }
        return $trackedRefHeader->getBodyAsString();
    }

    public function __toString(): string
    {
        return (string)$this->decoratedTransport;
    }
}
