# Events

When an email is sent, events are dispatched to inform the outside world of what is happening.

You can for example use this to log what happens for debugging purposes or for tracking.

## spooler:queue

Triggered when a message is added to a queue.

**Important note**: This event is **not** triggered when you do an instant send **BUT** can 
be triggered if the mail fails to send. In such a case it will be queued 
for retry and the event will trigger.

The callback method takes a `SpoolerOnQueueEvent` argument, defining:

| Visibility | Function |
|:-----------|:---------|
| public | <strong>getMessage()</strong> : <em>\Webeak\Bundle\MailBundle\MessageInterface</em><br /><em>Gets the message concerned.</em> |

## spooler:send

Triggered just before a message is sent.

**Important note**: This event is **not** triggered when you do an instant send.
For instant send, the `spooler:send-instant` event is fired.

The callback method takes a `SpoolerOnSendEvent` argument, defining:

| Visibility | Function |
|:-----------|:---------|
| public | <strong>getMessage()</strong> : <em>\Webeak\Bundle\MailBundle\MessageInterface</em><br /><em>Gets the message concerned.</em> |

## spooler:send-instant

Triggered when a mail is sent instantly.
If this event fires, `spooler:send` **will not trigger**.

If the mail fails to go, it will be added to the queue for retry.
This will trigger the `spooler:queue` event also so beware.

The callback method takes a `SpoolerOnInstantSendEvent` argument, defining:

| Visibility | Function |
|:-----------|:---------|
| public | <strong>getMessage()</strong> : <em>\Webeak\Bundle\MailBundle\MessageInterface</em><br /><em>Gets the message concerned.</em> |

## spooler:send-success

Triggered when a message has been successfully sent.

The callback method takes a `SpoolerOnSendSuccessEvent` argument, defining:

| Visibility | Function |
|:-----------|:---------|
| public | <strong>getMessage()</strong> : <em>\Webeak\Bundle\MailBundle\MessageInterface</em><br /><em>Gets the message concerned.</em> |


## spooler:send-failure

Triggered each time a message failed to send. 

**This can be triggered multiple times for each message.**

The callback method takes a `SpoolerOnSendFailureEvent` argument, defining:

| Visibility | Function |
|:-----------|:---------|
| public | <strong>getMessage()</strong> : <em>\Webeak\Bundle\MailBundle\MessageInterface</em><br /><em>Gets the message concerned.</em> |
| public | <strong>getReason()</strong> : <em>string</em><br /><em>Get the failure reason.</em> |

## spooler:retry

Triggered when a message that failed to send before is about to be queued for retry.

The callback method takes a `SpoolerOnRetryEvent` argument, defining:

| Visibility | Function |
|:-----------|:---------|
| public | <strong>getMessage()</strong> : <em>\Webeak\Bundle\MailBundle\MessageInterface</em><br /><em>Gets the message concerned.</em> |
| public | <strong>getRetryCount()</strong> : <em>integer</em><br /><em>Gets the number of tries consumed for this message.</em> |
| public | <strong>getTriesLeft()</strong> : <em>integer</em><br /><em>Gets the number of tries left for this message.</em> |

## spooler:send-abandon

Triggered when a new message has reached its maximum failure count.
The message is then lost forever.

The callback method takes a `SpoolerOnSendAbandonEvent` argument, defining:

| Visibility | Function |
|:-----------|:---------|
| public | <strong>getMessage()</strong> : <em>\Webeak\Bundle\MailBundle\MessageInterface</em><br /><em>Gets the message concerned.</em> |
| public | <strong>getReason()</strong> : <em>string</em><br /><em>Get the failure reason.</em> |

## spooler:batch-end

Triggered when `nb_message_per_batch` is reached, just before the spooler goes to sleep.

It could be a good time for flushing your database for example, if you have
tracking entities waiting to be flushed.

The callback method takes a `SpoolerOnBatchEndEvent` argument, defining:

| Visibility | Function |
|:-----------|:---------|
| public | <strong>getMessages()</strong> : <em>array/\Webeak\Bundle\MailBundle\Event\MessageInterface[]</em><br /><em>Get the array of messages sent by this batch.</em> |

## spooler:create-webview

Triggered when the webview of a message is about to be created.

You can alter the message if you needs to, it has already been successfully sent at this point.

The callback method takes a `SpoolerOnCreateWebViewsEvent` argument, defining:

| Visibility | Function |
|:-----------|:---------|
| public | <strong>getMessage()</strong> : <em>\Webeak\Bundle\MailBundle\MessageInterface</em><br /><em>Gets the message concerned.</em> |

## tracker:create-message-entity

Triggered when the tracker wants to create a new message entity.

You can set a message entity in the event object to use a custom entity or
let the tracker create the one defined in the configuration.

The callback method takes a `MessageTrackerOnCreateMessageEntityEvent` argument, defining:

| Visibility | Function |
|:-----------|:---------|
| public | <strong>getEntity()</strong> : <em>\Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedMessageEntityInterface</em><br /><em>Gets the message entity.</em> |
| public | <strong>getMessage()</strong> : <em>\Webeak\Bundle\MailBundle\MessageInterface</em><br /><em>Gets the message that has been queued.</em> |
| public | <strong>getAutoSave()</strong> : <em>boolean</em><br /><em>Get if the entity will be auto saved by the tracker.</em> |
| public | <strong>setEntity(</strong><em>\Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedMessageEntityInterface</em> <strong>$entity</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\Event\$this</em><br /><em>Sets the message entity.</em> |
| public | <strong>setAutoSave(</strong><em>boolean</em> <strong>$value</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\Event\$this</em><br /><em>Set if the entity is should be persisted by the tracker or not. By default, its false. If the entity is created outside of the tracker, the tracker will not persist/flush it. Setting this to true will add the entity to the list of scheduled persist/flush. This will probably fail for entities with relations. So set it to true only if no other entity depends on it.</em> |

## tracker:create-message-link

Triggered when the tracker wants to create a new tracked link.

You can set a new value for the link that will be sent to the message entity.
The value can be anything.

If no value is set, the target url string will be used.

The callback method takes a `MessageTrackerOnCreateMessageLinkEvent` argument, defining:

| Visibility | Function |
|:-----------|:---------|
| public | <strong>getIdentifier()</strong> : <em>mixed</em><br /><em>Gets the link identifier.</em> |
| public | <strong>getLink()</strong> : <em>mixed</em><br /><em>Gets the link value.</em> |
| public | <strong>getMessage()</strong> : <em>\Webeak\Bundle\MailBundle\MessageInterface</em><br /><em>Gets the message that has been queued.</em> |
| public | <strong>getMessageEntity()</strong> : <em>\Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedMessageEntityInterface</em><br /><em>Gets the message doctrine entity.</em> |
| public | <strong>getUrl()</strong> : <em>string</em><br /><em>Gets the link url.</em> |
| public | <strong>setLink(</strong><em>mixed</em> <strong>$link</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\Event\$this</em><br /><em>Sets the link value you want to use when building your message entity. If null (the default value) the link will be the target url.</em> |

