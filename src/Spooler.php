<?php
namespace Webeak\Bundle\MailBundle;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Webeak\Bundle\EssentialBundle\UniqueIdGenerator;
use Webeak\Bundle\MailBundle\Event\SpoolerOnBatchEndEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnCreateWebViewsEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnInstantSendEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnQueueEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnRetryEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendAbandonEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendFailureEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendSuccessEvent;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;

/**
 * The role of the spooler is to ensure emails are sent at a controlled rate and in a priority order.
 */
class Spooler
{
    /** @var MailerInterface */
    private $mailer;

    /** @var \Twig_Environment */
    private $twig;

    /** @var RouterInterface */
    private $router;

    /** @var EventDispatcher */
    private $dispatcher;

    /** @var UniqueIdGenerator */
    private $uniqueIdGenerator;

    /** @var array */
    private $configuration;

    public function __construct(Environment $twig,
                                RouterInterface $router,
                                MailerInterface $mailer,
                                UniqueIdGenerator $uniqueIdGenerator,
                                array $configuration)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->router = $router;
        $this->uniqueIdGenerator = $uniqueIdGenerator;
        $this->configuration = $configuration;
        $this->dispatcher = new EventDispatcher();

        $this->configuration['save_path'] = $this->ensurePathExists($this->configuration['save_path']);
        $this->configuration['webviews_save_path'] = $this->ensurePathExists($this->configuration['webviews_save_path']);
        $this->configuration['priorities'] = (array)$this->configuration['priorities'];
        for ($i = 0, $ii = count($this->configuration['priorities']); $i < $ii; ++$i) {
            $this->ensurePathExists($this->configuration['save_path'].'/queues/'.$this->configuration['priorities'][$i]);
        }
    }

    /**
     * Instantly send one or multiple messages.
     *
     * You should use schedule() preferably, this is blocking until mails are sent.
     *
     * @param MessageInterface[] $messages
     *
     * @throws
     */
    public function instant(array $messages)
    {
        for ($i = 0, $ii = count($messages); $i < $ii; ++$i) {
            $this->prepareMessage($messages[$i]);
            $this->dispatcher->dispatch(Events::spoolerOnInstantSend, new SpoolerOnInstantSendEvent($messages[$i]));
            if (!$this->sendMessage($messages[$i])) {
                $this->scheduleMessage($messages[$i], 1);
            }
        }
    }

    /**
     * Schedule one or multiple messages for sending.
     *
     * @param MessageInterface[] $messages
     *
     * @throws
     */
    public function schedule(array $messages)
    {
        for ($i = 0, $ii = count($messages); $i < $ii; ++$i) {
            $this->prepareMessage($messages[$i]);
            $this->scheduleMessage($messages[$i]);
        }
    }

    /**
     * Flush part of the queue by order of priority.
     * This method is meant to be called by a CRON task.
     *
     * @param string          $priority (optional) level of priority to flush. If none, all priorities are processed.
     * @param OutputInterface $output   (optional) output to log what's happening
     */
    public function flush($priority = null, OutputInterface $output = null)
    {
        $this->setTimeLimit();
        $batchCount = 0;
        $batchMessages = [];
        $time = time();
        if ($output) { $output->writeLn('<info>Starts flushing.</info>'); }
        for ($i = 1; $i <= $this->configuration['max_retry_count']; ++$i) {
            for ($j = 0, $jj = count($this->configuration['priorities']); $j < $jj; ++$j) {
                $currentPriority = $this->configuration['priorities'][$j];
                if ($priority !== null && $currentPriority !== $priority) {
                    continue ;
                }
                $finder = new Finder();
                $finder->files()->name('*.r'.$i)->sortByAccessedTime();
                $path = $this->configuration['save_path'].'/queues/'.$currentPriority;

                if ($output) { $output->writeLn(sprintf('Searching for priority <comment>%s</comment> and retry <info>%d</info>..', $currentPriority, $i)); }
                foreach ($finder->in($path) as $file) {
                    try {
                        /** @var File $file */
                        $filepath = $file->getRealPath();
                        $filename = $file->getFilename();
                        $filetime = $file->getMTime();
                        $newpath = $filepath . '.sending';

                        if ($i > 1 && ($time - $filetime) < $this->configuration['max_execution_time']) {
                            continue;
                        }
                    } catch (\Exception $e) {
                        // The getMTime() may fail as we have still not renamed the file.
                        // If it fails, another process may have renamed it at the same time, simply ignore it.
                        continue ;
                    }
                    if (@rename($filepath, $newpath) !== false) {
                        /** @var MessageInterface $message */
                        $message = @unserialize(file_get_contents($newpath));
                        $identifier = substr($filename, 0, strpos($filename, '.', 0));
                        $try = intval(substr($filepath, strrpos($filepath, 'r', 0) + 1));

                        if ($output) { $output->write(sprintf('Sending <info>%s</info>..', $identifier)); }
                        $this->dispatcher->dispatch(Events::spoolerOnSend, new SpoolerOnSendEvent($message));
                        if (!$this->sendMessage($message)) {
                            if (!$this->scheduleMessage($message, $try) && $output) {
                                $output->writeLn(sprintf('<error>Failed, abandoning.</error>. Max retry count reached.'));
                            } else if ($output) {
                                $output->writeLn('<error>Failed.</error>');
                            }
                        } else {
                            if ($output) { $output->writeLn('<info>Success.</info>'); }
                        }
                        @unlink($newpath);
                        ++$batchCount;
                        $batchMessages[] = $message;
                        if ($batchCount < $this->configuration['nb_message_per_batch']) {
                            usleep($this->configuration['send_delay_per_message'] * 1000);
                        } else {
                            $batchCount = 0;
                            $batchMessages = [];
                            usleep($this->configuration['send_delay_per_batch'] * 1000);
                            $this->dispatcher->dispatch(Events::spoolerOnBatchEnd, new SpoolerOnBatchEndEvent($batchMessages));
                        }
                        if ((time() - $time) >= $this->configuration['max_execution_time']) {
                            if ($output) { $output->writeLn('<comment>Ends flushing. Max execution time reached.</comment>'); }
                            return ;
                        }
                    }
                }
            }
        }
        if (count($batchMessages) > 0) {
            $this->dispatcher->dispatch(Events::spoolerOnBatchEnd, new SpoolerOnBatchEndEvent($batchMessages));
        }
        if ($output) { $output->writeLn('<comment>Ends flushing. No more messages.</comment>'); }
        // We may have some time to loose, let's check if there is emails still 'sending' for a long time
        $this->recover($output);
    }

    /**
     * Try to find messages marked as 'sending' for too long to be normal.
     * Their host may have crashed or they may have errors in their templates..
     *
     * Anyway we will try to requeue them until they reach their maximum try count.
     *
     * @param OutputInterface $output   (optional) output to log what's happening
     */
    public function recover(OutputInterface $output = null)
    {
        if ($output) { $output->writeLn('<info>Starts recovering.</info>'); }
        for ($i = 0, $ii = count($this->configuration['priorities']); $i < $ii; ++$i) {
            $finder = new Finder();
            $finder->files()->name('*.sending')->sortByAccessedTime();
            $path = $this->configuration['save_path'] . '/queues/' . $this->configuration['priorities'][$i];
            if ($output) { $output->writeLn(sprintf('Searching for priority <comment>%s</comment>..', $this->configuration['priorities'][$i])); }
            foreach ($finder->in($path) as $file) {
                /** @var File $file */
                $time = $file->getMTime();
                $fullpath = $file->getRealPath();
                $filename = $file->getFilename();

                // If the file is on a sending state for more than the max_execution_time of the script + 60 seconds
                // then we may have a problem with it.
                if (time() - $time > $this->configuration['max_execution_time'] + 60) {
                    /** @var MessageInterface $message */
                    $message = @unserialize(file_get_contents($fullpath));
                    $identifier = substr($filename, 0, strpos($filename, '.', 0));
                    $newName = substr($filename, 0, -8);
                    $try = intval(substr($newName, strrpos($newName, 'r', 0) + 1));
                    if ($try < $this->configuration['max_retry_count']) {
                        ++$try;
                        $this->dispatcher->dispatch(Events::spoolerOnRetry, new SpoolerOnRetryEvent(
                            $message,
                            $identifier,
                            $try,
                            $this->configuration['max_retry_count'] - $try
                        ));
                        $newName = substr($newName, 0, strrpos($newName, 'r', 0) + 1).$try;
                        @rename($fullpath, $path.'/'.$newName);
                        if ($output) { $output->writeLn(sprintf('<info>%s</info> requeued.', $identifier)); }
                    } else {
                        @unlink($fullpath);
                        if ($output) { $output->writeLn(sprintf('<error>Abandoning</error> <info>%s</info>. Max retry count reached.', $identifier)); }
                        $this->dispatcher->dispatch(Events::spoolerOnSendAbandon, new SpoolerOnSendAbandonEvent($message, 'Maximum number of tries reached in recover.'));
                    }
                }
            }
        }
        if ($output) { $output->writeLn('<info>Ends recovering.</info>'); }
    }

    /**
     * Dependency injection callback for registering
     * services bound using services' tags.
     *
     * To register here you need to add the tag "wb.mail.spooler_event_listener"
     * to your service, and add to it the attribute "event" specifying the event you want to listen to.
     *
     * @param mixed $service
     * @param array $attributes
     */
    public function registerEvent($service, $attributes)
    {
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }
        for ($i = 0, $ii = count($attributes); $i < $ii; ++$i) {
            if (array_key_exists('event', $attributes[$i])) {
                $event = $attributes[$i]['event'];
                $method = array_key_exists('method', $attributes[$i]) ? $attributes[$i]['method'] : $event;
                if (method_exists($service, $method)) {
                    $this->dispatcher->addListener($attributes[$i]['event'], [$service, $method]);
                } else {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Failed to register "%s" listener, no "%s" method found on "%s".',
                            $event,
                            $method,
                            get_class($service)
                        )
                    );
                }
            }
        }
    }

    /**
     * Send a message.
     *
     * @param MessageInterface $message
     *
     * @return boolean
     */
    private function sendMessage(MessageInterface $message)
    {
        try {
            // Render the twig template.
            if ($message->hasHtmlContent()) {
                $message->setHtml($this->renderMessageTemplate($message, 'html'));
            }
            if ($message->hasTextContent()) {
                $message->setText($this->renderMessageTemplate($message, 'text'));
            }
            $finalMessage = $message->getInstance();
            if ($this->mailer->send($finalMessage) > 0) { // TODO: use the second parameter of the send() method to handle partial success.
                $this->dispatcher->dispatch(Events::spoolerOnSendSuccess, new SpoolerOnSendSuccessEvent($message));
                if ($message->webview()) {
                    $this->dispatcher->dispatch(Events::spoolerOnCreateWebViews, new SpoolerOnCreateWebViewsEvent($message));
                    $this->createWebView($message);
                }
                return true;
            } else {
                $this->dispatcher->dispatch(Events::spoolerOnSendFailure, new SpoolerOnSendFailureEvent($message, 'Mailer failed to send with no exception.'));
            }
        } catch (\Exception $e) {
            $this->dispatcher->dispatch(Events::spoolerOnSendFailure, new SpoolerOnSendFailureEvent($message, $e->getMessage()));
        }
        return false;
    }

    /**
     * Schedule the sending of a message.
     *
     * @param MessageInterface $message
     * @param integer          $try        (optional) send try count for this message
     *
     * @return boolean
     */
    private function scheduleMessage(MessageInterface $message, $try = 0)
    {
        if ($try >= $this->configuration['max_retry_count']) {
            $reason = sprintf('Max send retry count of "%d" reached.', $this->configuration['max_retry_count']);
            $this->dispatcher->dispatch(Events::spoolerOnSendAbandon, new SpoolerOnSendAbandonEvent($message, $reason));
            return false;
        }
        try {
            $serialized = serialize($message);
            $priority = $message->getSpoolerPriority();
            if (!$priority) {
                $priority = array_reverse($this->configuration['priorities'])[max(0, floor(count($this->configuration['priorities']) / 2) - 1)];
            }
            if (!in_array($priority, $this->configuration['priorities'])) {
                throw new InvalidConfigurationException(sprintf(
                    'There is no "%s" priority defined in the spooler configuration. Available priorities : "%s".',
                    $priority,
                    implode(', ', $this->configuration['priorities'])
                ));
            }
            $path = $this->configuration['save_path'] . '/queues/' . $priority . '/' . $message->getIdentifier() . '.message.r' . ($try + 1);
            if (@file_put_contents($path, $serialized) !== false) {
                $this->dispatcher->dispatch(Events::spoolerOnQueue, new SpoolerOnQueueEvent($message));
            } else {
                $reason = sprintf('Failed to write "%s".', $path);
                $this->dispatcher->dispatch(Events::spoolerOnSendFailure, new SpoolerOnSendFailureEvent($message, $reason));
                $this->dispatcher->dispatch(Events::spoolerOnSendAbandon, new SpoolerOnSendAbandonEvent($message, $reason));
            }
        } catch (\Exception | \Throwable $e) {
            $this->dispatcher->dispatch(Events::spoolerOnSendFailure, new SpoolerOnSendFailureEvent($message, $e->getMessage()));
        }
        return true;
    }

    /**
     * Creates a file for each mail and recipient that will be accessible by browser.
     *
     * @param MessageInterface $message
     */
    private function createWebView(MessageInterface $message)
    {
        $recipients = $message->getAllRecipients();
        foreach ($recipients as $address => $name) {
            $path = $this->configuration['webviews_save_path'].'/'.$message->getIdentifier();
            if (($fd = @fopen($path, 'x')) !== false) {
                $rendered = $this->renderMessageTemplate($message, 'html');
                if (@fwrite($fd, $rendered) === false) {
                    throw new IOException(sprintf('Failed to write in "%s".', $path));
                }
                @fclose($fd);
                return ;
            }
            throw new \RuntimeException('Failed to create a unique identifier.');
        }
    }

    /**
     * Ensure a path exists or throws an exception.
     *
     * @param $path
     *
     * @return string
     *
     * @throws IOException
     */
    private function ensurePathExists($path)
    {
        if (!file_exists($path)) {
            if (!mkdir($path, 0777, true)) {
                $path = null;
            }
        }
        if ($path && ($output = realpath($path)) !== false) {
            return $output;
        }
        throw new IOException(sprintf('Unable to create path "%s".', $path));
    }

    /**
     * Render a TWIG template by its source code.
     *
     * @param MessageInterface $message             message to render the template for
     * @param string           $type                type of content (html or text)
     *
     * @return string the rendered source code
     *
     * @throws
     */
    private function renderMessageTemplate(MessageInterface $message, $type)
    {
        $variables = $message->getVariables();
        $source = $type === 'html' ? $message->getHtml() : $message->getText();
        $template = $this->twig->createTemplate($source);
        ob_start();
        $result = $template->render($variables);
        ob_end_clean();
        return $result;
    }

    /**
     * Try to limit the script execution time in case an infinite loop occurs in a
     * template or anything preventing the script to stop.
     *
     * When flushing, the spooler checks if the time limit is reached and stops if so.
     * But in case the script is stuck before this check, the time limit should kill it.
     *
     * To the limit set in the configuration, 10 seconds + the sleeping time between batches is added
     * to ensure the script will not get killed in the middle of something legit.
     */
    private function setTimeLimit()
    {
        $scriptTimeLimit = intval($this->configuration['max_execution_time'] + ($this->configuration['send_delay_per_batch'] * 0.001)) + 10;
        set_time_limit($scriptTimeLimit);
    }

    /**
     * Prepare a message for sending.
     *
     * @param MessageInterface $message
     */
    private function prepareMessage(MessageInterface $message)
    {
        if ($message->webview()) {
            $message->addVariables([
                '_webViewLink' =>
                    $this->router->generate('wb_mail_webview', ['identifier' => $message->getIdentifier()], RouterInterface::ABSOLUTE_URL)
            ]);
        }
    }
}
