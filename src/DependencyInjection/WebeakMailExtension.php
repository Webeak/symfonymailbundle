<?php

namespace Webeak\Bundle\MailBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

class WebeakMailExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'wb_mail';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('wb.mail.tracker', [
            'message_entity_class' => trim(str_replace('/', '\\', $config['tracker']['message_entity_class']), '\\'),
            'message_entity_identifier_attr' => $config['tracker']['message_entity_identifier_attr'],
            'link_entity_class' => trim(str_replace('/', '\\', $config['tracker']['link_entity_class']), '\\'),
            'link_entity_identifier_attr' => $config['tracker']['link_entity_identifier_attr'],
        ]);
        $container->setParameter('wb.mail.spooler', [
            'nb_message_per_batch' => intval($config['spooler']['nb_message_per_batch']),
            'send_delay_per_message' => intval($config['spooler']['send_delay_per_message']),
            'send_delay_per_batch' => intval($config['spooler']['send_delay_per_batch']),
            'max_retry_count' => intval($config['spooler']['max_retry_count']),
            'max_execution_time' => max(intval($config['spooler']['send_delay_per_batch'] + 1000) * 0.001, intval($config['spooler']['max_execution_time'] * 0.001)),
            'save_path' => $config['spooler']['save_path'],
            'webviews_save_path' => $config['spooler']['webviews_save_path'],
            'priorities' => (array)$config['spooler']['priorities']
        ]);
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $yamlParser = new YamlParser();
        $locator = new FileLocator(__DIR__.'/../Bridge/Doctrine/Resources/config');
        $file = $locator->locate('doctrine.yaml');
        try {
            $doctrineConfig = $yamlParser->parse(file_get_contents($file));
        } catch (ParseException $e) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML.', $file), 0, $e);
        }
        $container->prependExtensionConfig('doctrine', $doctrineConfig['doctrine']);
    }
}
