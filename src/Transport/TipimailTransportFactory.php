<?php
namespace Webeak\Bundle\MailBundle\Transport;

use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

class TipimailTransportFactory extends AbstractTransportFactory
{

    public function supports(Dsn $dsn): bool
    {
        return $dsn->getScheme() === 'tipimail';
    }

    public function create(Dsn $dsn): TransportInterface
    {
        return new TipimailTransport($this->client, $dsn);
    }

    protected function getSupportedSchemes(): array
    {
        return ['tipimail'];
    }
}
