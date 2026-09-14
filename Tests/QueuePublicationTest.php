<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\ClassMetadata as Metadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedMessage;
use Webeak\Bundle\MailBundle\Bridge\SwiftMailer\SwiftMessage;
use Webeak\Bundle\MailBundle\EventListener\MessageTrackerListener;
use Webeak\Bundle\MailBundle\Events;
use Webeak\Bundle\MailBundle\MessageTracker;
use Webeak\Bundle\MailBundle\Spooler;

// Map the fields used by this workflow; no application schema is needed.
final class TrackingTestDriver implements MappingDriver
{
    public function loadMetadataForClass($className, Metadata $metadata)
    {
        $metadata->setPrimaryTable(['name' => 'tracking']);
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        foreach (['identifier' => 'string', 'queued' => 'boolean', 'sent' => 'boolean', 'failed' => 'boolean',
                  'sendTryCount' => 'integer', 'sendDate' => 'datetime', 'failureReasons' => 'json',
                  'abandonReason' => 'string'] as $field => $type) {
            $metadata->mapField(['fieldName' => $field, 'type' => $type, 'nullable' => true]);
        }
    }
    public function getAllClassNames() { return [TrackedMessage::class]; }
    public function isTransient($className) { return $className !== TrackedMessage::class; }
}

final class QueuePublicationTest extends TestCase
{
    private $root;
    private $producer;
    private $consumer;
    private $reader;
    private $tracker;
    private $dispatcher;
    private $taskManager;
    private $spooler;
    private $transport;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/mail-queue-test-'.bin2hex(random_bytes(8));
        mkdir($this->root.'/queues/high', 0777, true);
        $configuration = new Configuration();
        $configuration->setMetadataDriverImpl(new TrackingTestDriver());
        $configuration->setMetadataCache(new ArrayAdapter());
        $configuration->setProxyDir($this->root);
        $configuration->setProxyNamespace('QueueTestProxies');
        $connection = ['driver' => 'pdo_sqlite', 'path' => $this->root.'/tracking.sqlite'];
        $this->producer = EntityManager::create($connection, $configuration);
        $this->consumer = EntityManager::create($connection, $configuration);
        $this->reader = DriverManager::getConnection($connection);
        (new SchemaTool($this->producer))->createSchema([$this->producer->getClassMetadata(TrackedMessage::class)]);
        $this->tracker = $this->tracker($this->producer);
        $this->dispatcher = $this->dispatcher($this->tracker);
        $this->taskManager = new class {
            public $starts = 0;
            public $onStart;
            public function start($type, $options) {
                ++$this->starts;
                if ($this->onStart) { ($this->onStart)(); }
            }
        };
        $this->transport = new class {
            public $results = [1];
            public $sent = 0;
            public function send($message) { ++$this->sent; return array_shift($this->results) ?? 1; }
        };
        $this->spooler = $this->spooler($this->dispatcher);
    }

    protected function tearDown(): void
    {
        foreach ([$this->producer, $this->consumer] as $em) { $em->getConnection()->close(); }
        $this->reader->close();
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
        rmdir($this->root);
    }

    public function testTrackingIsCommittedBeforePublicationAndAnImmediateConsumerCanRecordSuccess(): void
    {
        $message = $this->message('first');
        $checked = false;
        $this->dispatcher->addListener(Events::spoolerOnQueue, function () use (&$checked, $message) {
            $checked = true;
            self::assertSame([], $this->publishedFiles());
            $row = $this->row('first'); // Separate connection: uncommitted inserts are invisible.
            self::assertIsArray($row);
            self::assertSame(1, (int)$row['queued']);
            self::assertSame(0, (int)$row['sent']);
            $temporary = glob($this->root.'/queues/high/*.tmp');
            self::assertCount(1, $temporary);
            self::assertSame(serialize($message), file_get_contents($temporary[0]));
        }, -100);
        $consumer = $this->spooler($this->dispatcher($this->tracker($this->consumer)));
        $this->taskManager->onStart = function () use ($consumer) {
            self::assertCount(1, $this->publishedFiles());
            $consumer->flush();
        };
        self::assertSame(1, $this->spooler->schedule([$message]));
        self::assertTrue($checked);
        self::assertSame(1, $this->transport->sent);
        self::assertSame([], $this->publishedFiles());
        // A late producer flush must not overwrite the consumer's result.
        $this->tracker->flush();
        self::assertSame(1, (int)$this->row('first')['sent']);
        self::assertSame(0, (int)$this->row('first')['queued']);
        $consumer->flush();
        self::assertSame(1, $this->transport->sent);
    }

    public function testSqlFailurePreventsPublicationAndPreservesThePendingBatch(): void
    {
        $this->producer->getConnection()->executeStatement("CREATE TRIGGER fail_insert BEFORE INSERT ON tracking BEGIN SELECT RAISE(ABORT, 'forced SQL failure'); END");
        $message = $this->message('sql-failure');
        self::assertSame(0, $this->spooler->schedule([$message]));
        self::assertSame([], glob($this->root.'/queues/high/*'));
        self::assertSame(0, $this->taskManager->starts);
        self::assertFalse($this->row('sql-failure'));
        self::assertCount(1, self::get($this->tracker, 'waitingForPersist'));
    }

    public function testOpenTransactionIsRejectedWithoutCommittingOrRollingBackIt(): void
    {
        $this->producer->beginTransaction();
        self::assertSame(0, $this->spooler->schedule([$this->message('transaction')]));
        self::assertTrue($this->producer->getConnection()->isTransactionActive());
        self::assertFalse($this->row('transaction'));
        self::assertSame([], glob($this->root.'/queues/high/*'));
        self::assertSame(0, $this->taskManager->starts);
        $this->producer->rollback();
    }

    public function testFileWriteFailureIsNotReportedAsAQueuedMessage(): void
    {
        rmdir($this->root.'/queues/high');
        self::assertSame(0, $this->spooler->schedule([$this->message('disk-failure')]));
        self::assertSame(0, $this->taskManager->starts);
        $this->tracker->flush(true);
        self::assertSame(0, (int)$this->row('disk-failure')['queued']);
        self::assertSame(1, (int)$this->row('disk-failure')['failed']);
    }

    public function testRenameFailureRemovesTemporaryFileAndMarksTrackingFailed(): void
    {
        $this->dispatcher->addListener(Events::spoolerOnQueue, function () {
            // A directory at the destination reliably makes rename fail.
            mkdir($this->root.'/queues/high/rename-failure.message.r1');
        }, -100);
        self::assertSame(0, $this->spooler->schedule([$this->message('rename-failure')]));
        self::assertSame([], $this->publishedFiles());
        self::assertSame([], glob($this->root.'/queues/high/*.tmp'));
        self::assertSame(0, $this->taskManager->starts);
        $this->tracker->flush(true);
        self::assertSame(0, (int)$this->row('rename-failure')['queued']);
        self::assertSame(1, (int)$this->row('rename-failure')['failed']);
    }

    public function testMixedBatchCountsOnlyPublishedMessages(): void
    {
        $bad = $this->message('bad-priority');
        $bad->setSpoolerPriority('unknown');
        self::assertSame(1, $this->spooler->schedule([$bad, $this->message('good')]));
        self::assertSame(1, $this->taskManager->starts);
        self::assertCount(1, $this->publishedFiles());
        self::assertSame(1, (int)$this->row('good')['queued']);
    }

    public function testSmtpFailureRequeuesSameMessageThenSuccessUpdatesSameRow(): void
    {
        $this->transport->results = [0, 1];
        self::assertSame(1, $this->spooler->schedule([$this->message('retry')]));
        $consumer = $this->spooler($this->dispatcher($this->tracker($this->consumer)));
        $consumer->flush();
        $retry = $this->root.'/queues/high/retry.message.r2';
        self::assertFileExists($retry);
        self::assertSame(1, (int)$this->row('retry')['queued']);
        self::assertSame(0, (int)$this->row('retry')['sent']);
        touch($retry, time() - 61);
        clearstatcache();
        $consumer->flush();
        self::assertSame(2, $this->transport->sent);
        self::assertSame(1, (int)$this->reader->fetchOne('SELECT COUNT(*) FROM tracking'));
        self::assertSame(1, (int)$this->row('retry')['sent']);
        self::assertSame(0, (int)$this->row('retry')['queued']);
        self::assertSame([], $this->publishedFiles());
    }

    public function testExhaustedSmtpRetriesAbandonTheSameMessage(): void
    {
        $this->transport->results = [0, 0];
        self::assertSame(1, $this->spooler->schedule([$this->message('abandon')]));
        $consumer = $this->spooler($this->dispatcher($this->tracker($this->consumer)));
        $consumer->flush();
        touch($this->root.'/queues/high/abandon.message.r2', time() - 61);
        clearstatcache();
        $consumer->flush();
        self::assertSame(2, $this->transport->sent);
        self::assertSame(0, (int)$this->row('abandon')['queued']);
        self::assertSame(1, (int)$this->row('abandon')['failed']);
        self::assertSame([], $this->publishedFiles());
    }

    public function testCallerManagedTrackingCannotBePublishedWithoutSavingIt(): void
    {
        $message = $this->message('caller-managed');
        // Equivalent to a tracker:create-message-entity listener with autoSave=false.
        self::set($this->tracker, 'waitingForPersist', []);
        self::set($this->tracker, 'autoPersistEntitiesIdentifiers', []);
        self::assertSame(1, $this->spooler->schedule([$message]));
        self::assertSame(1, (int)$this->row('caller-managed')['queued']);
    }

    public function testLegacyFlushReportsFailureAndRetainsItsBatch(): void
    {
        $this->message('legacy-failure');
        $this->producer->getConnection()->executeStatement("CREATE TRIGGER fail_insert BEFORE INSERT ON tracking BEGIN SELECT RAISE(ABORT, 'forced SQL failure'); END");
        $this->tracker->flush();
        self::assertCount(1, self::get($this->tracker, 'errorTracker')->errors);
        self::assertCount(1, self::get($this->tracker, 'waitingForPersist'));
    }

    public function testUntrackedMailStillUsesTheFileQueue(): void
    {
        $message = $this->message('untracked', false);
        self::assertSame(1, $this->spooler->schedule([$message]));
        self::assertFalse($this->row('untracked'));
        self::assertCount(1, $this->publishedFiles());
    }

    private function message($identifier, $tracked = true)
    {
        $message = (new ReflectionClass(SwiftMessage::class))->newInstanceWithoutConstructor();
        foreach (['identifier' => $identifier, 'instance' => new Swift_Message('Test'), 'variables' => [], 'extras' => [],
                  'attachments' => [], 'hasWebview' => false, 'baseUrl' => '/'] as $key => $value) {
            self::set($message, $key, $value);
        }
        $message->setFrom('sender@example.test');
        $message->setTo('recipient@example.test');
        $message->setSpoolerPriority('high');
        if ($tracked) { $this->tracker->trackMessage($message); }
        return $message;
    }

    private function tracker(EntityManager $em)
    {
        $tracker = (new ReflectionClass(MessageTracker::class))->newInstanceWithoutConstructor();
        foreach (['entityManager' => $em, 'dispatcher' => new EventDispatcher(), 'messageEntityClass' => TrackedMessage::class,
                  'messageEntityIdentifierAttr' => 'identifier', 'trackedMessages' => [], 'trackedMessagesEntities' => [],
                  'trackedLinksEntities' => [], 'trackedLinks' => [], 'waitingForPersist' => [],
                  'autoPersistEntitiesIdentifiers' => [], 'hasBeenFlushed' => false,
                  'errorTracker' => new class { public $errors = []; public function track($error) { $this->errors[] = $error; } }] as $key => $value) {
            self::set($tracker, $key, $value);
        }
        return $tracker;
    }

    private function dispatcher(MessageTracker $tracker)
    {
        $dispatcher = new EventDispatcher();
        $listener = new MessageTrackerListener($tracker);
        foreach ([Events::spoolerOnQueue => 'onQueue', Events::spoolerOnSend => 'onSend',
                  Events::spoolerOnSendSuccess => 'onSendSuccess', Events::spoolerOnSendFailure => 'onSendFailure',
                  Events::spoolerOnSendAbandon => 'onSendAbandon', Events::spoolerOnBatchEnd => 'onBatchEnd'] as $event => $method) {
            $dispatcher->addListener($event, [$listener, $method]);
        }
        return $dispatcher;
    }

    private function spooler(EventDispatcher $dispatcher)
    {
        $spooler = (new ReflectionClass(Spooler::class))->newInstanceWithoutConstructor();
        foreach (['configuration' => ['save_path' => $this->root, 'priorities' => ['high'], 'max_retry_count' => 2,
                  'nb_message_per_batch' => 5, 'send_delay_per_message' => 0, 'send_delay_per_batch' => 0, 'max_execution_time' => 60],
                  'dispatcher' => $dispatcher, 'heavyTaskManager' => $this->taskManager, 'mailer' => $this->transport] as $key => $value) {
            self::set($spooler, $key, $value);
        }
        return $spooler;
    }

    private function row($identifier) { return $this->reader->fetchAssociative('SELECT * FROM tracking WHERE identifier = ?', [$identifier]); }
    private function publishedFiles() { return array_values(array_filter(glob($this->root.'/queues/high/*'), static function ($path) { return is_file($path) && preg_match('/[.]message[.]r[0-9]+$/', $path); })); }
    private static function set($object, $name, $value) { $p = new ReflectionProperty($object, $name); $p->setAccessible(true); $p->setValue($object, $value); }
    private static function get($object, $name) { $p = new ReflectionProperty($object, $name); $p->setAccessible(true); return $p->getValue($object); }
}
