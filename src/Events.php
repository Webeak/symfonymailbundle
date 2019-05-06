<?php
namespace Webeak\Bundle\MailBundle;

/**
 * Centralize events names.
 */
final class Events {

    /**
     * Triggered when a message is added to a queue.
     */
    const spoolerOnQueue = 'spooler:queue';

    /**
     * Triggered just before a message is sent.
     */
    const spoolerOnSend = 'spooler:send';

    /**
     * Triggered when a message that failed to send before is about to be queued for retry.
     */
    const spoolerOnRetry = 'spooler:retry';

    /**
     * Triggered when a message has been successfully sent.
     */
    const spoolerOnSendSuccess = 'spooler:send-success';

    /**
     * Triggered each time a message failed to send. This can be triggered multiple times for each message.
     */
    const spoolerOnSendFailure = 'spooler:send-failure';

    /**
     * Triggered when a new message has reached its maximum failure count.
     * The message is then lost forever.
     */
    const spoolerOnSendAbandon = 'spooler:send-abandon';

    /**
     * Triggered when the webview of a message is about to be created.
     * You can alter the message if you needs to, it has already been successfully sent at this point.
     */
    const spoolerOnCreateWebViews = 'spooler:create-webview';

    /**
     * Triggered when a mail is sent instantly.
     * If this event fires, 'spooler:send' will not trigger.
     *
     * If the mail fails to go, it will be added to the queue for retry.
     * This will trigger the 'spooler:queue' event also so beware.
     */
    const spoolerOnInstantSend = 'spooler:send-instant';

    /**
     * Triggered when 'nb_message_per_batch' is reached, just before the spooler goes to sleep.
     *
     * It could be a good time for flushing your database for example, if you have
     * tracking entities waiting to be flushed.
     */
    const spoolerOnBatchEnd = 'spooler:batch-end';

    /**
     * Triggered when the tracker wants to create a new message entity.
     *
     * You can set a message entity in the event object to use a custom entity or
     * let the tracker create the one defined in the configuration.
     */
    const messageTrackerOnCreateMessageEntity = 'tracker:create-message-entity';

    /**
     * Triggered when the tracker wants to create a new tracked link.
     *
     * You can set a new value for the link that will be sent to the message entity.
     * The value can be anything.
     *
     * If no value is set, the target url string will be used.
     */
    const messageTrackerOnCreateLink = 'tracker:create-message-link';
}
