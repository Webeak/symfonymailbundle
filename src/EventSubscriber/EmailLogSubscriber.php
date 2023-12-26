<?php
namespace Webeak\Bundle\MailBundle\EventSubscriber;

use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Email;
use Webeak\Bundle\MailBundle\Entity\MailLog;
use Webeak\Component\Utils\RandomGenerator;

class EmailLogSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {

    }

    public static function getSubscribedEvents()
    {
        return [
            MessageEvent::class => 'onMessageSend'
        ];
    }

    public function onMessageSend(MessageEvent $event)
    {
//        $email = $event->getMessage();
//        if (!$email instanceof Email) {
//            return;
//        }
//        $log = new MailLog();
//        $log->setRecipients($this->recipientsToString($email));
//        $log->setRef(uniqid() . RandomGenerator::randomString(12));
//        $log->setSubject($email->getSubject());
//        $log->setHtml($email->getHtmlBody());
//        $log->setText($email->getTextBody());
//
//        $this->entityManager->persist($log);
//        $this->entityManager->flush();
    }

//    private function recipientsToString(Email $email): string
//    {
//        $output = [];
//        $allRecipients = array_merge(
//            $email->getTo(),
//            $email->getCc(),
//            $email->getBcc()
//        );
//        foreach ($allRecipients as $recipient) {
//            $recipientString = $recipient->getName()
//                ? sprintf('%s <%s>', $recipient->getName(), $recipient->getAddress())
//                : $recipient->getAddress();
//            $output[] = $recipientString;
//        }
//        return implode(', ', $output);
//    }
}
