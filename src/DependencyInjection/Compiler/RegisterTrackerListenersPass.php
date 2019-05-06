<?php
namespace Webeak\Bundle\MailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Webeak\Bundle\MailBundle\EventListener\MessageTracker;

class RegisterTrackerListenersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(MessageTracker::class) === false) {
            return;
        }
        $definition = $container->getDefinition(MessageTracker::class);
        foreach ($container->findTaggedServiceIds('wb.mail.message_tracker_event_listener') as $id => $attributes) {
            $definition->addMethodCall('registerEvent', [new Reference($id), $attributes]);
        }
    }
}
