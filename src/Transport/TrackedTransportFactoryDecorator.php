<?php
namespace Webeak\Bundle\MailBundle\Transport;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

class TrackedTransportFactoryDecorator implements TransportFactoryInterface
{
    public function __construct(private readonly TransportFactoryInterface $decoratedFactory,
                                private readonly LoggerInterface $logger,
                                private readonly EntityManagerInterface $entityManager)
    {

    }

    public function supports(Dsn $dsn): bool
    {
        return $this->decoratedFactory->supports($dsn);
    }

    public function create(Dsn $dsn): TransportInterface
    {
        $transport = $this->decoratedFactory->create($dsn);
        return new TrackedTransportDecorator($transport, $this->logger, $this->entityManager);
    }
}
