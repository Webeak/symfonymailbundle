<?php
namespace Webeak\Bundle\MailBundle\HeavyTask;

use Webeak\Bundle\DebugBundle\Logger;
use Webeak\Bundle\HeavyTaskBundle\AbstractHeavyTask;
use Webeak\Bundle\HeavyTaskBundle\HeavyTaskContext;
use Webeak\Bundle\MailBundle\MessageTracker;
use Webeak\Bundle\MailBundle\Spooler;
use Webeak\Bundle\SharedStorageBundle\SharedStorageInterface;

class FlushSpoolerTask extends AbstractHeavyTask
{
    /** @var Spooler */
    private $spooler;

    /** @var MessageTracker */
    private $messageTracker;

    public function __construct(SharedStorageInterface $sharedStorage,
                                Logger $logger,
                                Spooler $spooler,
                                MessageTracker $messageTracker)
    {
        parent::__construct($sharedStorage, $logger);
        $this->spooler = $spooler;
        $this->messageTracker = $messageTracker;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return '[Mail] Flush spooler task';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return 'Flush the spooler every 5 seconds to send the next mails in queue.';
    }

    /**
     * @inheritDoc
     *
     * @throws
     */
    public function execute(HeavyTaskContext $context)
    {
        $i = 0;
        do {
            $this->spooler->flush();
            $this->messageTracker->flush();
            $context->setProgress('Last flush: ' . date('H:i:s'));
            sleep(5);
        } while ($i < 120); // ~10 minutes
    }
}
