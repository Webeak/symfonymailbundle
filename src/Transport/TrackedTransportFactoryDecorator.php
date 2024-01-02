<?php
namespace Webeak\Bundle\MailBundle\Transport;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

class TrackedTransportFactoryDecorator implements TransportFactoryInterface
{
    public function __construct(protected readonly LoggerInterface $logger,
                                protected readonly ManagerRegistry $doctrine,
                                protected readonly TransportFactoryInterface $decoratedFactory)
    {

    }

    public function supports(Dsn $dsn): bool
    {
        return $this->decoratedFactory->supports($dsn);
    }

    public function create(Dsn $dsn): TransportInterface
    {
        $transport = $this->decoratedFactory->create($dsn);
        return new TrackedTransportDecorator($transport, $this->logger, $this->doctrine);
    }
}
