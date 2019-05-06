<?php
namespace Webeak\Bundle\MailBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webeak\Bundle\MailBundle\Spooler;

class FlushSpoolerMailingCommand extends Command
{
    /** @var Spooler */
    private $spooler;

    public function __construct(Spooler $spooler, string $name = null)
    {
        parent::__construct($name);
        $this->spooler = $spooler;
    }

    protected function configure()
    {
        $this
            ->setName('wb:mail:flush-spooler')
            ->setDescription('Send messages in the spooler queue.')
            ->addArgument('priority', InputArgument::OPTIONAL, 'To specify a priority to flush. If not defined all queues will be processed.')
            ->addOption('v', null, null, 'To output detailed information on what happens.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->spooler->flush(null, $input->getOption('v') ? $output : null);
    }
}

