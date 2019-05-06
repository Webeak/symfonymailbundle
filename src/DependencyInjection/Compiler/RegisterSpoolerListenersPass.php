<?php
namespace Webeak\Bundle\MailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Webeak\Bundle\MailBundle\Spooler;

class RegisterSpoolerListenersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(Spooler::class) === false) {
            return;
        }
        $definition = $container->getDefinition(Spooler::class);
        foreach ($container->findTaggedServiceIds('wb.mail.spooler_event_listener') as $id => $attributes) {
            $definition->addMethodCall('registerEvent', [new Reference($id), $attributes]);
        }
    }
}
