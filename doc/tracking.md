# Tracking

Tracking is useful if you want to log what happens to your messages after you call `send()`.

Is your email delivered? Opened? Clicked?

**Important note:** You need `Doctrine` and a database to use this feature. 
The bundle needs two table to work (a table to store messages data, and one table for links). More on this later.

## Basic usage

To use the tracking, you must tell the `Mailer` to track your message. You do it by calling the `trackMessage` method:

```php
$mailer->trackMessage($message);
```

Same logic for links. If you want to know if a user clicked a link in your message, you must tell the `Mailer` to track your link:

```php
$mailer->trackLink('https://your-url.com');
```

The method returns a new url containing a unique identifier for your link. This identifier will be used to get the target url and redirect the user.

A complete example could be:


```php
<?php
namespace App\Controller;

use Webeak\Bundle\MailBundle\Mailer;

class ExampleController
{
    public function sendTracked(Mailer $mailer)
    {
        $message = $mailer->createMessageBuilder()
            ->subject('A tracked email')
            ->to('john.doe@gmail.com')
            ->from('no-reply@example.com')
            ->html('emails/example.html.twig')
            ->text('emails/example.txt.twig')
            ->addVariable('url', $mailer->trackLink('https://google.com'))
            ->getMessage();
        $mailer->trackMessage($message);
        $mailer->send($message);
        ...
    }
}
```

The mailer will populate two tables, by default: `wb_mail_tracked_message' and `wb_mail_tracked_links`.

## Track opening

To track when emails are opened, the tracker uses a transparent gif that query a special route.

This traker is only an img tag with the correct source url.

You have to put it in your email for the tracker to see when emails are opened.

A special variable called `_tracker` is added to your message when you track it.

Put this in your twig template to use it:

```twig
{% if _tracker is defined %}
    {{ _tracker | raw }}
{% endif %}
```

## Using your own entities

By default, tracking is handled by two internal services :
  - A listener listening for events from the `spooler`
  - A tracker that create and populate tracking entities when asked by the event listener

The tracker expect two entities:

  - An entity implemeting **TrackedMessageEntityInterface** for messages
  - An entity implemeting **TrackedLinkEntityInterface** for links
  
Two entities are built-in for this:
  - `Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedMessage`
  - `Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedLink`

If you do nothing else than calling `trackMessage` and `trackLink` in your app, everything
should work as expected. You'll find the data about your emails in the `wb_mail_tracked_message` and `wb_mail_tracked_link` tables.

But if you want to use you own entities you can by changing the following configuration:

```yaml
# config/packages/wb_mail.yaml

wb_mail:
    tracker:
        # FQCN of the database entity storing tracked messages data.
        message_entity_class: Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedMessage

        # Name of the attribute holding the message string identifier in the database entity.
        message_entity_identifier_attr: identifier

        # FQCN of the database entity storing tracked links.
        link_entity_class: Webeak\Bundle\MailBundle\Bridge\Doctrine\Orm\Entity\TrackedLink

        # Name of the attribute holding the link string identifier in the database entity.
        link_entity_identifier_attr: identifier
```

This gives you the ability to change the table name (by simply inheriting from the base classes defined above) or 
to define your own entities with custom properties if you want.

But if your entities have external relations the tracker cannot know about, you will have to listen for one or both of the following events:

  - `tracker:create-message-entity`: ask for the creation of a **TrackedMessageEntityInterface** for a message.
  - `tracker:create-message-link`: ask for the creation of a **TrackedLinkEntityInterface** for a link
  
Both of these events will wait for a result (more on this in the [Events](events.md) section).

The event object given as parameter for these events contains a `setAutoSave()` method you can use to tell
the tracker not to persist your entity. In this case you'll have to persist it yourself.

## Your own tracker

If you want to tracker messages yourself, you can create a custom service listener for spooler events:

```php
<?php
namespace App\EventListener;

use Webeak\Bundle\MailBundle\Event\SpoolerOnBatchEndEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnInstantSendEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnQueueEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendAbandonEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendFailureEvent;
use Webeak\Bundle\MailBundle\Event\SpoolerOnSendSuccessEvent;

class MessageTrackerListener
{
    /**
     * Spooler 'spooler:queue' listener.
     *
     * @param SpoolerOnQueueEvent $event
     */
    public function onQueue(SpoolerOnQueueEvent $event)
    {
        $event->stopPropagation();
        // Do your stuff
    }

    /**
     * Spooler 'spooler:send-instant' listener.
     *
     * @param SpoolerOnInstantSendEvent $event
     */
    public function onInstantSend(SpoolerOnInstantSendEvent $event)
    {
        $event->stopPropagation();
        // Do your stuff
    }

    /**
     * Spooler 'spooler:send' listener.
     *
     * @param SpoolerOnSendEvent $event
     */
    public function onSend(SpoolerOnSendEvent $event)
    {
        $event->stopPropagation();
        // Do your stuff
    }

    /**
     * Spooler 'spooler:send-success' listener.
     *
     * @param SpoolerOnSendSuccessEvent $event
     *
     * @throws
     */
    public function onSendSuccess(SpoolerOnSendSuccessEvent $event)
    {
        $event->stopPropagation();
        // Do your stuff
    }

    /**
     * Spooler 'spooler:send-failure' listener.
     *
     * @param SpoolerOnSendFailureEvent $event
     */
    public function onSendFailure(SpoolerOnSendFailureEvent $event)
    {
        $event->stopPropagation();
        // Do your stuff
    }

    /**
     * Spooler 'spooler:send-abandon' listener.
     *
     * @param SpoolerOnSendAbandonEvent $event
     */
    public function onSendAbandon(SpoolerOnSendAbandonEvent $event)
    {
        $event->stopPropagation();
        // Do your stuff
    }

    /**
     * Spooler 'spooler:batch-end' listener.
     *
     * @param SpoolerOnBatchEndEvent $event
     */
    public function onBatchEnd(SpoolerOnBatchEndEvent $event)
    {
        $event->stopPropagation();
        // Do your stuff
    }
}
```

It's very important to stop the propagation of the events otherwise the original tracker 
will still run and you will have duplicate data.

Then you register your service in `services.yaml`:

```yaml
# config/services.yaml

App\EventListener\MessageTrackerListener:
    arguments: []
    tags:
        - { name: 'wb.mail.spooler_event_listener', event: 'spooler:queue', method: onQueue }
        - { name: 'wb.mail.spooler_event_listener', event: 'spooler:send-instant', method: onInstantSend }
        - { name: 'wb.mail.spooler_event_listener', event: 'spooler:send', method: onSend }
        - { name: 'wb.mail.spooler_event_listener', event: 'spooler:send-success', method: onSendSuccess }
        - { name: 'wb.mail.spooler_event_listener', event: 'spooler:send-failure', method: onSendFailure }
        - { name: 'wb.mail.spooler_event_listener', event: 'spooler:send-abandon', method: onSendAbandon }
        - { name: 'wb.mail.spooler_event_listener', event: 'spooler:batch-end', method: onBatchEnd }
```

The complete list of events is available in the [Events](events.md) section.
