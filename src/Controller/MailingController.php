<?php
namespace Webeak\Bundle\MailBundle\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Webeak\Bundle\ErrorTrackerBundle\ErrorTrackerInterface;
use Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedLinkEntityInterface;
use Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedMessageEntityInterface;
use Webeak\Bundle\MailBundle\Response\TransparentPixelResponse;
use Webeak\Bundle\MailBundle\Spooler;

class MailingController extends AbstractController
{
    /**
     * @Route(name="wb_mail_open", path="/AOMNJkqVkLCWXicwckvTfelNtFvoOIMi/{identifier}", methods={"GET"})
     */
    public function open($identifier, ManagerRegistry $managerRegistry, ErrorTrackerInterface $errorTracker)
    {
        try {
            $trackerConfiguration = $this->getParameter('wb.mail.tracker');
            $em = $managerRegistry->getManagerForClass($trackerConfiguration['message_entity_class']);
            $messageEntity = $em->getRepository($trackerConfiguration['message_entity_class'])->findOneBy([$trackerConfiguration['message_entity_identifier_attr'] => $identifier]);
            if ($messageEntity instanceof TrackedMessageEntityInterface) {
                $messageEntity->setOpenCount($messageEntity->getOpenCount() + 1);
                $messageEntity->setLastOpenDate(new \DateTime());
                $em->persist($messageEntity);
                $em->flush();
                return new TransparentPixelResponse();
            }
        } catch (\Exception | \Throwable $e) {
            $errorTracker->track($e, ['identifier' => $identifier]);
        }
        throw $this->createNotFoundException();
    }

    /**
     * @Route(name="wb_mail_link", path="/hHKeAnCcxapKpcqziCvPQNLRvKQHwJqC/{identifier}", methods={"GET"})
     */
    public function link($identifier, ManagerRegistry $managerRegistry, ErrorTrackerInterface $errorTracker)
    {
        try {
            $trackerConfiguration = $this->getParameter('wb.mail.tracker');
            $em = $managerRegistry->getManagerForClass($trackerConfiguration['link_entity_class']);
            $linkEntity = $em->getRepository($trackerConfiguration['link_entity_class'])->findOneBy([$trackerConfiguration['link_entity_identifier_attr'] => $identifier]);
            if ($linkEntity instanceof TrackedLinkEntityInterface) {
                $linkEntity->setClickCount($linkEntity->getClickCount() + 1);
                $linkEntity->setLastClickDate(new \DateTime());
                $em->persist($linkEntity);
                $messageEntity = $linkEntity->getTrackedMessage();
                if ($messageEntity && !$messageEntity->getOpenCount()) {
                    $messageEntity->setOpenCount($messageEntity->getOpenCount() + 1);
                    $messageEntity->setLastOpenDate(new \DateTime());
                    $em->persist($messageEntity);
                }
                $em->flush();
                return new RedirectResponse($linkEntity->getUrl());
            }
        } catch (\Exception | \Throwable $e) {
            $errorTracker->track($e, ['identifier' => $identifier]);
        }
        throw $this->createNotFoundException();
    }

    /**
     * @Route(name="wb_mail_webview", path="/mail/webview/{identifier}", methods={"GET"})
     *
     * @param string $identifier message's identifier
     *
     * @return Response
     *
     * @throws
     */
    public function webview($identifier)
    {
        try {
            $configuration = $this->getParameter('wb.mail.spooler');
            $basePath = realpath($configuration['webviews_save_path']);
            if ($basePath !== false) {
                $fullPath = $basePath . '/' . $identifier;
                if (file_exists($fullPath)) {
                    return new Response(file_get_contents($fullPath));
                }
            }
        } catch (\Exception $e) { }
        throw $this->createNotFoundException();
    }

    /**
     * @Route(name="wb_mail_spooler_flush", path="/mail/spooler/flush", methods={"GET"})
     *
     * @param Spooler $spooler
     *
     * @return Response
     */
    public function flushAction(Spooler $spooler)
    {
        $spooler->flush();
        return new Response('ok');
    }
}

