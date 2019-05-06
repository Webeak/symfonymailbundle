# Usage

Everytime you need to send an email, you should use the `Mailer` service and
create messages using a message builder.

The builder will construct a `MessageInterface` instance for you.

You then just have to give this object to the `send` or `sendNow` method to send your message:

```php
<?php
namespace App\Controller;

use Webeak\Bundle\MailBundle\Mailer;

class ExampleController
{
    public function sendEmail(Mailer $mailer)
    {
        $message = $mailer->createMessageBuilder()
            ->subject('A test email')
            ->to('john.doe@gmail.com')
            ->from('no-reply@example.com')
            ->html('<h1>Hello world!</h1>')
            ->text('Hello world!')
            ->getMessage();
        $mailer->send($message);
        ...
    }
}
```

## Using templates

In the example above, the content is written directly in the builder, which is not very realistic.

You can use twig templates to design your emails. To do so, simply change the content of the `html` and/or `text` methods 
by a path to a twig template:

```php
<?php
namespace App\Controller;

use Webeak\Bundle\MailBundle\Mailer;

class ExampleController
{
    public function sendEmail(Mailer $mailer)
    {
        $message = $mailer->createMessageBuilder()
            ->subject('A test email with a twig content')
            ->to('john.doe@gmail.com')
            ->from('no-reply@example.com')
            ->html('emails/example.html.twig') // path is relative to the 'templates' directory
            ->text('emails/example.txt.twig')  // path is relative to the 'templates' directory
            ->getMessage();
        $mailer->send($message);
        ...
    }
}
```

**Note**: templates are relative to the `templates` directory and a different template must be created
for the **txt** version. But both can use twig.

## Using variables

You can set variables that will be sent to your twig template in the message builder by calling
the `addVariable()` or `addVariables()` method.

```php
$builder = $mailer->createMessageBuilder();

// Add a single variable
$builder->addVariable('variableName', 'value');

// Add multiple variables
$builder->addVariables([
    'var_1' => 'value 1',
    'var_2' => 'value 2'
]);

// Replace all variables
$builder->variables([
    'new_1' => 'value 1',
    'new_2' => 'value 2'
]);

// Get all registered variables
$variables = $builder->variables();
```

>**IMPORTANT NOTE**: Your variables must be serializable to be used in a mail template because the process
>that will send your emails is not necessarily the same as the one that created the message.
> 
> If for some reason you can't make your variables serializable, use the `sendNow()` method so the
> email is sent immediately and thus does not require any serialization.
>
> BUT BEWARE, if your email fails to send, it will have to be queued for future retry, and if your variable
> cannot be serialized, it will fail and your email will be lost.

## Tracking messages

If `Doctrine` is installed you can ask the manager to track certain messages and links.

When tracked, a message will have an entry in the tracking table (by default `wb_mail_tracked_message`).
It contains info about the status of the message (has it been sent? When? If not, why? etc.).

To track a message simple call:

```php
$mailer->trackMessage($message);
```

You can also track for clicks. Do do so, wrap your url using the `trackLink()` method, like so:

```php
$mailer->trackLink('https://your-url.com');
```

The method returns a new url containing a unique identifier for your link. This identifier will be used to get the target url and redirect the user.

A complete example:

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
            ->addVariable('url', $mailer->trackLink('https://google.com', ['toto' => 'value']))
            ->addExtras(['toto' => 'tata'])
            ->addExtra('titi', 2)
            ->getMessage();
        $mailer->trackMessage($message);
        $mailer->send($message);
        ...
    }
}
```

More details in the [Tracking](tracking.md) section.

## Message builder reference

The builder offer you the following methods:

| Visibility | Function |
|:-----------|:---------|
| public | <strong>addVariable(</strong><em>string</em> <strong>$name</strong>, <em>mixed</em> <strong>$value</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this</em><br /><em>Add a variable accessible from the templates. This method is ONLY a setter.</em> |
| public | <strong>addVariables(</strong><em>array</em> <strong>$variables</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this</em><br /><em>Add new variables to the set of variables accessible from the templates. This method is ONLY a setter.</em> |
| public | <strong>andBcc(</strong><em>string/array</em> <strong>$addresses</strong>, <em>string</em> <strong>$name=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/array</em><br /><em>Add Bcc addresses to this message. If $name is passed and the first parameter is a string, this name will be associated with the address. This method is ONLY a setter.</em> |
| public | <strong>andCc(</strong><em>string/array</em> <strong>$addresses</strong>, <em>string</em> <strong>$name=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/array</em><br /><em>Add Cc addresses to this message. If $name is passed and the first parameter is a string, this name will be associated with the address. This method is ONLY a setter.</em> |
| public | <strong>andTo(</strong><em>string/array</em> <strong>$addresses</strong>, <em>string</em> <strong>$name=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this</em><br /><em>Same as to() but do not remove existing recipients. If a recipient email has already been added it will be overridden. This method is ONLY a setter.</em> |
| public | <strong>attachByData(</strong><em>string</em> <strong>$data</strong>, <em>string</em> <strong>$filename</strong>, <em>string</em> <strong>$contentType=null</strong>, <em>bool/boolean</em> <strong>$inline=false</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this</em><br /><em>Attach a file by its data. Use this method if you want to attach a file not yet persisted to the hard drive.</em> |
| public | <strong>attachByPath(</strong><em>string</em> <strong>$path</strong>, <em>string</em> <strong>$filename=null</strong>, <em>string</em> <strong>$contentType=null</strong>, <em>bool/boolean</em> <strong>$inline=false</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this</em><br /><em>Attach a file by path. If no filename is specified, the last component of the path will be used as filename.</em> |
| public | <strong>bcc(</strong><em>string/array</em> <strong>$addresses=null</strong>, <em>string</em> <strong>$name=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/array</em><br /><em>Get/Set the Bcc addresses of this message. If $name is passed and the first parameter is a string, this name will be associated with the address.</em> |
| public | <strong>cc(</strong><em>string/array</em> <strong>$addresses=null</strong>, <em>string</em> <strong>$name=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/array</em><br /><em>Get/Set the Cc addresses of this message. If $name is passed and the first parameter is a string, this name will be associated with the address.</em> |
| public | <strong>from(</strong><em>string/array</em> <strong>$addresses=null</strong>, <em>string</em> <strong>$name=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/mixed</em><br /><em>Get/Set the from address of the message. You may pass an array of addresses if this message is from multiple people.</em> |
| public | <strong>getMessage()</strong> : <em>[\Webeak\Bundle\MailBundle\MessageInterface](#interface-webeakbundlemailbundlemessageinterface)</em><br /><em>Get the message behind the builder.</em> |
| public | <strong>html(</strong><em>string</em> <strong>$template=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/string</em><br /><em>Get/Set HTML content of the message. This can either be a template path (twig) or an HTML string. In case of a template path, this method understand what a render() call do. So you can pass shorthand syntax path like: "@App/Emails/my-email".</em> |
| public | <strong>priority(</strong><em>string</em> <strong>$priority=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/integer</em><br /><em>Get/Set the priority header of the message.</em> |
| public | <strong>returnPath(</strong><em>string</em> <strong>$address=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/string</em><br /><em>Get/Set the return-path (the bounce address) of this message.</em> |
| public | <strong>sender(</strong><em>string</em> <strong>$address=null</strong>, <em>string</em> <strong>$name=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/string</em><br /><em>Get/Set the sender of this message.</em> |
| public | <strong>spoolerPriority(</strong><em>string</em> <strong>$priority=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/string</em><br /><em>Get/Set priority of the message in the spooler.</em> |
| public | <strong>subject(</strong><em>string</em> <strong>$subject=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/string</em><br /><em>Get/Set the subject of the message.</em> |
| public | <strong>text(</strong><em>string</em> <strong>$template=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/string</em><br /><em>Get/Set the text content of the message. In case of a template path, this method understand what a render() call do. So you can pass shorthand syntax path like: "@App/Emails/my-email".</em> |
| public | <strong>to(</strong><em>string/array</em> <strong>$addresses=null</strong>, <em>string</em> <strong>$name=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/array</em><br /><em>Get/Set the to addresses of this message. If multiple recipients will receive the message an array should be used. Example: array('receiver@domain.org', 'other@domain.org' => 'A name') If $name is passed and the first parameter is a string, this name will be associated with the address. Variables are local to recipients in other parameters. If you add multiple recipients in a single call they will share the variables.</em> |
| public | <strong>variables(</strong><em>array</em> <strong>$variables=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this/array</em><br /><em>Get/Set variables accessible from the templates. This methods overrides any other variables set previously. Use addVariables() to keep existing ones.</em> |
| public | <strong>webview(</strong><em>boolean</em> <strong>$value=null</strong>)</strong> : <em>\Webeak\Bundle\MailBundle\$this</em><br /><em>Get/Set if the email should be accessible by a browser. If set to true a persist copy of the email will be kept on the server hard drive FOR EACH RECIPIENT. A '_webviewLink' containing the url to the webview will be added to each render so you can add a link in the template. By default no webview is generated.</em> |

