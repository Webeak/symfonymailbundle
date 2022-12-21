<?php
namespace Webeak\Bundle\MailBundle;

use Webeak\Component\Utils\ArrayUtils;

/**
 * Offers an handy way to construct a message.
 */
abstract class AbstractMessageBuilder implements MessageBuilderInterface
{
    /** @var MessageInterface */
    protected $message;

    /** @var array */
    protected $deferredCalls;

    public function __construct(MessageInterface $message)
    {
        $this->message = $message;
    }

    /**
     * Get/Set the subject of the message.
     *
     * @param string $subject
     *
     * @return $this|string
     */
    public function subject($subject = null)
    {
        if ($subject === null) {
            return $this->message->getSubject();
        }
        $this->deferredCalls[] = function() use ($subject) {
            $this->message->setSubject($subject);
        };
        return $this;
    }

    /**
     * Get/Set the from address of the message.
     * You may pass an array of addresses if this message is from multiple people.
     *
     * @param string|array $addresses
     * @param string       $name
     * @return $this|mixed
     */
    public function from($addresses = null, $name = null)
    {
        if ($addresses === null) {
            return $this->message->getFrom();
        }
        $this->deferredCalls[] = function() use ($addresses, $name) {
            $this->message->setFrom($addresses, $name);
        };
        return $this;
    }

    /**
     * Get/Set the to addresses of this message.
     *
     * If multiple recipients will receive the message an array should be used.
     * Example: array('receiver@domain.org', 'other@domain.org' => 'A name')
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * Variables are local to recipients in other parameters.
     * If you add multiple recipients in a single call they will share the variables.
     *
     * @param string|array $addresses
     * @param string       $name
     *
     * @return $this|array
     */
    public function to($addresses = null, $name = null)
    {
        if ($addresses === null) {
            return $this->message->getTo();
        }
        $this->deferredCalls[] = function() use ($addresses, $name) {
            $this->message->setTo($addresses, $name);
        };
        return $this;
    }

    /**
     * Same as to() but do not remove existing recipients.
     * If a recipient email has already been added it will be overridden.
     *
     * This method is ONLY a setter.
     *
     * @param string|array $addresses
     * @param string       $name
     *
     * @return $this
     */
    public function andTo($addresses, $name = null)
    {
        $this->deferredCalls[] = function() use ($addresses, $name) {
            $this->message->addTo($addresses, $name);
        };
        return $this;
    }

    /**
     * Get/Set the Cc addresses of this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param string|array $addresses
     * @param string       $name
     *
     * @return $this|array
     */
    public function cc($addresses = null, $name = null)
    {
        if ($addresses === null) {
            return $this->message->getCc();
        }
        $this->deferredCalls[] = function() use ($addresses, $name) {
            $this->message->setCc($addresses, $name);
        };
        return $this;
    }

    /**
     * Add Cc addresses to this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * This method is ONLY a setter.
     *
     * @param string|array $addresses
     * @param string       $name
     *
     * @return $this|array
     */
    public function andCc($addresses, $name = null)
    {
        $this->deferredCalls[] = function() use ($addresses, $name) {
            $this->message->addCc($addresses, $name);
        };
        return $this;
    }

    /**
     * Get/Set the Bcc addresses of this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param string|array $addresses
     * @param string       $name
     *
     * @return $this|array
     */
    public function bcc($addresses = null, $name = null)
    {
        if ($addresses === null) {
            return $this->message->getBcc();
        }
        $this->deferredCalls[] = function() use ($addresses, $name) {
            $this->message->setBcc($addresses, $name);
        };
        return $this;
    }

    /**
     * Add Bcc addresses to this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * This method is ONLY a setter.
     *
     * @param string|array $addresses
     * @param string       $name
     *
     * @return $this|array
     */
    public function andBcc($addresses, $name = null)
    {
        $this->deferredCalls[] = function() use ($addresses, $name) {
            $this->message->addBcc($addresses, $name);
        };
        return $this;
    }

    /**
     * Get/Set the return-path (the bounce address) of this message.
     *
     * @param string $address
     *
     * @return $this|string
     */
    public function returnPath($address = null)
    {
        if ($address === null) {
            return $this->message->getReturnPath();
        }
        $this->deferredCalls[] = function() use ($address) {
            $this->message->setReturnPath($address);
        };
        return $this;
    }

    /**
     * Get/Set the sender of this message.
     *
     * @param string $address
     * @param string $name    optional
     *
     * @return $this|string
     */
    public function sender($address = null, $name = null)
    {
        if ($address === null) {
            return $this->message->getSender();
        }
        $this->deferredCalls[] = function() use ($address, $name) {
            $this->message->setSender($address, $name);
        };
        return $this;
    }

    /**
     * Get/Set HTML content of the message.
     * This can either be a template path (twig) or an HTML string.
     *
     * In case of a template path, this method understand what a render() call do.
     * So you can pass shorthand syntax path like: "@App/Emails/my-email".
     *
     * @param string $template template path or code
     *
     * @return $this|string
     */
    public function html($template = null)
    {
        if ($template === null) {
            return $this->message->getHtml();
        }
        $this->deferredCalls[] = function() use ($template) {
            $this->message->setHtml($template);
        };
        return $this;
    }

    /**
     * Get/Set the text content of the message.
     *
     * In case of a template path, this method understand what a render() call do.
     * So you can pass shorthand syntax path like: "@App/Emails/my-email".
     *
     * @param string $template template path or code
     *
     * @return $this|string
     */
    public function text($template = null)
    {
        if ($template === null) {
            return $this->message->getText();
        }
        $this->deferredCalls[] = function() use ($template) {
            $this->message->setText($template);
        };
        return $this;
    }

    /**
     * Get/Set variables accessible from the templates.
     *
     * This methods overrides any other variables set previously.
     * Use addVariables() to keep existing ones.
     *
     * @param array $variables
     *
     * @return $this|array
     */
    public function variables(array $variables = null)
    {
        if ($variables === null) {
            return $this->message->getVariables();
        }
        $this->deferredCalls[] = function() use ($variables) {
            $this->message->setVariables($variables);
        };
        return $this;
    }

    /**
     * Add a variable accessible from the templates.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function addVariable($name, $value)
    {
        $this->deferredCalls[] = function() use ($name, $value) {
            $this->message->addVariables([$name => $value]);
        };
        return $this;
    }

    /**
     * Add new variables to the set of variables accessible from the templates.
     *
     * @param array $variables
     *
     * @return $this
     */
    public function addVariables(array $variables)
    {
        $this->deferredCalls[] = function() use ($variables) {
            $this->message->addVariables($variables);
        };
        return $this;
    }

    /**
     * Get/Set all extra data associated with the message.
     *
     * This methods overrides any other extra set previously.
     * Use addExtras() to keep existing ones.
     *
     * @param array $extras
     *
     * @return $this|array
     */
    public function extras(array $extras = null)
    {
        if ($extras === null) {
            return $this->message->getExtras();
        }
        $this->deferredCalls[] = function() use ($extras) {
            $this->message->setExtras($extras);
        };
        return $this;
    }

    /**
     * Add an extra value that will not be part of the email.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function addExtra($key, $value)
    {
        $this->deferredCalls[] = function() use ($key, $value) {
            $this->message->addExtras([$key => $value]);
        };
        return $this;
    }

    /**
     * Add multiple extra values that will not be part of the email.
     *
     * @param array $extras
     *
     * @return $this
     */
    public function addExtras(array $extras)
    {
        $this->deferredCalls[] = function() use ($extras) {
            $this->message->addVariables($extras);
        };
        return $this;
    }

    /**
     * Attach a file by path.
     * If no filename is specified, the last component of the path will be used as filename.
     *
     * @param string  $path        file's path
     * @param string  $filename    optional new name for the file
     * @param string  $contentType (optional) content type
     * @param boolean $inline      (optional)if true, the file disposition will be changed to be inlined (if possible) in the email (default: false)
     *
     * @return $this
     */
    public function attachByPath($path, $filename = null, $contentType = null, $inline = false)
    {
        $this->deferredCalls[] = function() use ($data, $filename, $contentType, $inline) {
            $this->message->addAttachmentByPath($path, $filename, $contentType, $inline);
        };
        return $this;
    }

    /**
     * Attach a file by its data.
     * Use this method if you want to attach a file not yet persisted to the hard drive.
     *
     * @param string  $data        file's data
     * @param string  $filename    file's name
     * @param string  $contentType (optional) content type
     * @param boolean $inline      (optional) if true, the file disposition will be changed to be inlined (if possible) in the email (default: false)
     *
     * @return $this
     */
    public function attachByData($data, $filename, $contentType = null, $inline = false)
    {
        $this->deferredCalls[] = function() use ($data, $filename, $contentType, $inline) {
            $this->message->addAttachmentByData($data, $filename, $contentType, $inline);
        };
        return $this;
    }

    /**
     * Get/Set priority of the message in the spooler.
     *
     * @param string $priority
     *
     * @return $this|string
     */
    public function spoolerPriority($priority = null)
    {
        if ($priority === null) {
            return $this->message->getSpoolerPriority();
        }
        $this->deferredCalls[] = function() use ($priority) {
            $this->message->setSpoolerPriority($priority);
        };
        return $this;
    }

    /**
     * Get/Set the priority header of the message.
     *
     * @param string $priority
     *
     * @return $this|integer
     */
    public function priority($priority = null)
    {
        if ($priority === null) {
            return $this->message->getPriority();
        }
        $this->deferredCalls[] = function() use($priority) {
            $this->message->setPriority($priority);
        };
        return $this;
    }

    /**
     * Get/Set if the email should be accessible by a browser.
     *
     * If set to true a persist copy of the email will be kept on the server hard drive FOR EACH RECIPIENT.
     * A '_webviewLink' containing the url to the webview will be added to each render so you can add a link in the template.
     *
     * By default no webview is generated.
     *
     * @param boolean $value
     *
     * @return $this
     */
    public function webview($value = null)
    {
        $this->deferredCalls[] = function() use ($value) {
            $this->message->webview($value);
        };
        return $this;
    }

    /**
     * Get the message behind the builder.
     *
     * @return MessageInterface
     */
    public function getMessage()
    {
        foreach ($this->deferredCalls as $call) {
            try {
                $call();
            } catch (\Exception | \Throwable $e) {
                $extras = ArrayUtils::ensureArray($this->message->getExtras());
                if (!array_key_exists('builderErrors', $extras)) {
                    $extras['builderErrors'] = [];
                }
                $extras['builderErrors'][] = $e->getMessage();
                $this->message->setExtras($extras);
            }
        }
        $this->deferredCalls = [];
        return $this->message;
    }
}
