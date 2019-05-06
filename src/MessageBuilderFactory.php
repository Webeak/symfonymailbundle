<?php
namespace Webeak\Bundle\MailBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

class MessageBuilderFactory
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Create a new MessageBuilderInterface.
     *
     * @return MessageBuilderInterface
     */
    public function create(): MessageBuilderInterface
    {
        return $this->container->get(MessageBuilderInterface::class);
    }
}
