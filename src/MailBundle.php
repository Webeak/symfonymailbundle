<?php
namespace Webeak\Bundle\MailBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Webeak\Bundle\MailBundle\DependencyInjection\Compiler\RegisterSpoolerListenersPass;
use Webeak\Bundle\MailBundle\DependencyInjection\Compiler\RegisterTrackerListenersPass;
use Webeak\Bundle\MailBundle\DependencyInjection\WebeakMailExtension;

class MailBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new RegisterSpoolerListenersPass());
        $container->addCompilerPass(new RegisterTrackerListenersPass());
    }

    /**
     * @inheritDoc
     */
    public function getContainerExtension()
    {
        return new WebeakMailExtension();
    }
}
