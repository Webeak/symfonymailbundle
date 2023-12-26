<?php
namespace Webeak\Bundle\MailBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Webeak\Bundle\MailBundle\DependencyInjection\WebeakMailExtension;

class WebeakMailBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new WebeakMailExtension();
    }
}
