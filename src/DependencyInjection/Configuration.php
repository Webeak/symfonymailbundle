<?php

namespace Webeak\Bundle\MailBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedMessage;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('wb_mail');

        $rootNode
            ->children()
                ->scalarNode('http_root')
                    ->info('HTTP path pointing to the "public" directory of the project.')
                    ->defaultNull()
                ->end()
                ->arrayNode('tracker')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('message_entity_class')
                            ->info('FQCN of the database entity storing tracked messages data.')
                            ->defaultValue(TrackedMessage::class)
                        ->end()
                        ->scalarNode('message_entity_identifier_attr')
                            ->info('Name of the attribute holding the message string identifier in the database entity.')
                            ->defaultValue('identifier')
                        ->end()
                        ->scalarNode('link_entity_class')
                            ->info('FQCN of the database entity storing tracked links.')
                            ->defaultValue(TrackedMessage::class)
                        ->end()
                        ->scalarNode('link_entity_identifier_attr')
                            ->info('Name of the attribute holding the link string identifier in the database entity.')
                            ->defaultValue('identifier')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('spooler')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('nb_message_per_batch')
                            ->info('Maximum number of messages sent in a row.')
                            ->defaultValue(5)
                        ->end()
                        ->scalarNode('send_delay_per_message')
                            ->info('Time between each message (in ms).')
                            ->defaultValue(100)
                        ->end()
                        ->scalarNode('send_delay_per_batch')
                            ->info('Time to wait between batches (in ms).')
                            ->defaultValue(1000)
                        ->end()
                        ->scalarNode('max_execution_time')
                            ->info('Maximum execution time of the CRON task (in ms).')
                            ->defaultValue(50000)
                        ->end()
                        ->scalarNode('max_retry_count')
                            ->info('Maximum number of time to try to send a mail before considering it as failed.')
                            ->defaultValue(2)
                        ->end()
                        ->scalarNode('save_path')
                            ->info('Root directory where the spooler should write its files.')
                            ->defaultValue('%kernel.project_dir%/var/storage/wb-mail/spooler')
                        ->end()
                        ->scalarNode('webviews_save_path')
                            ->info('Root directory where the web versions of sent emails are kept.')
                            ->defaultValue('%kernel.project_dir%/var/storage/wb-mail/webviews')
                        ->end()
                        ->arrayNode('priorities')
                            ->info('Level of priority the spooler can understand.')
                            ->treatNullLike(array())
                            ->prototype('scalar')->end()
                            ->defaultValue(['high', 'normal', 'low'])
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
