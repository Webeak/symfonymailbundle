<?php
namespace Webeak\Bundle\MailBundle\Bridge\SwiftMailer;

use Symfony\Component\HttpFoundation\RequestStack;
use Webeak\Bundle\DoctrineExtensionsBundle\Utils\UniqueIdGenerator;
use Webeak\Bundle\MailBundle\MessageInterface;
use Webeak\Component\Utils\ArrayUtils;

/**
 * SwiftMailer message proxy.
 *
 * @package Webeak\Bundle\MailBundle\Bridge\SwiftMailer
 *
 * @service
 */
class SwiftMessage implements MessageInterface, \Serializable
{
    /** @var \Swift_Message */
    private $instance;

    /** @var string */
    private $identifier;

    /**
     * HTML content of the message.
     * It is stored here because \Swift_Message doesn't seem to allow retrieve an
     * exact body part after being added.
     *
     * To avoid storing the text twice, its only stored here and set in the Swift_Message at
     * the last second.
     *
     * @var string
     */
    private $html;

    /**
     * Text content of the message.
     * Same as $html.
     *
     * @var string
     */
    private $text;

    /**
     * Variables sent with all templates rendering.
     *
     * @var array
     */
    private $variables;

    /** @var array */
    private $extras;

    /**
     * Hold the priority of the message.
     * Used by the spooler to order the sending.
     *
     * @var string
     */
    private $spoolerPriority;

    /**
     * Hold if the message should have a webview or not.
     *
     * Default: false.
     *
     * @var boolean
     */
    private $hasWebview;

    /** @var string */
    private $baseUrl;

    /** @var array */
    private $attachments;

    public function __construct(RequestStack $requestStack, UniqueIdGenerator $uniqueIdGenerator)
    {
        $currentRequest = $requestStack->getCurrentRequest();
        $this->instance = new \Swift_Message();
        $this->identifier = $uniqueIdGenerator->generateId(16);
        $this->html = null;
        $this->text = null;
        $this->hasWebview = false;
        $this->variables = [];
        $this->extras = [];
        $this->attachments = [];
        $this->baseUrl = $currentRequest ? $currentRequest->getSchemeAndHttpHost() : '/';
    }

    /**
     * Get the unique id of the message.
     *
     * @return string|null
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * Set the subject of this message.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->instance->setSubject($subject);
        return $this;
    }

    /**
     * Get the subject of this message.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->instance->getSubject();
    }

    /**
     * Set the return-path (the bounce address) of this message.
     *
     * @param string $address
     *
     * @return $this
     */
    public function setReturnPath($address)
    {
        $this->instance->setReturnPath($address);
        return $this;
    }

    /**
     * Get the return-path (bounce address) of this message.
     *
     * @return string
     */
    public function getReturnPath()
    {
        return $this->instance->getReturnPath();
    }

    /**
     * Set the sender of this message.
     *
     * @param string $address
     * @param string $name optional
     *
     * @return $this
     */
    public function setSender($address, $name = null)
    {
        $this->instance->setSender($address, $name);
        return $this;
    }

    /**
     * Get the sender of this message.
     *
     * @return string
     */
    public function getSender()
    {
        return $this->instance->getSender();
    }

    /**
     * Add a From: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name optional
     *
     * @return $this
     */
    public function addFrom($address, $name = null)
    {
        $this->instance->addFrom($address, $name);
        return $this;
    }

    /**
     * Set the from address of this message.
     *
     * You may pass an array of addresses if this message is from multiple people.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param string|array $addresses
     * @param string $name optional
     *
     * @return $this
     */
    public function setFrom($addresses, $name = null)
    {
        $this->instance->setFrom($addresses, $name);
        return $this;
    }

    /**
     * Get the from address of this message.
     *
     * @return mixed
     */
    public function getFrom()
    {
        return $this->instance->getFrom();
    }

    /**
     * Add a Reply-To: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name optional
     *
     * @return $this
     */
    public function addReplyTo($address, $name = null)
    {
        $this->instance->addReplyTo($address, $name);
        return $this;
    }

    /**
     * Set the reply-to address of this message.
     *
     * You may pass an array of addresses if replies will go to multiple people.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param mixed $addresses
     * @param string $name optional
     *
     * @return $this
     */
    public function setReplyTo($addresses, $name = null)
    {
        $this->instance->setReplyTo($addresses, $name);
        return $this;
    }

    /**
     * Get the reply-to address of this message.
     *
     * @return string
     */
    public function getReplyTo()
    {
        return $this->instance->getReplyTo();
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
     * @param string $name
     *
     * @return $this|array
     */
    public function setTo($addresses, $name = null)
    {
        $this->instance->setTo($addresses, $name);
        return $this;
    }

    /**
     * Same as to() but do not remove existing recipients.
     * If a recipient email has already been added it will be overridden.
     *
     * This method is ONLY a setter.
     *
     * @param string|array $addresses
     * @param string $name
     *
     * @return $this
     */
    public function addTo($addresses, $name = null)
    {
        $this->instance->addTo($addresses, $name);
        return $this;
    }

    /**
     * Get the To addresses of this message.
     *
     * @return array
     */
    public function getTo()
    {
        return $this->instance->getTo();
    }

    /**
     * Add a Cc: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name optional
     *
     * @return $this
     */
    public function addCc($address, $name = null)
    {
        $this->instance->addCc($address, $name);
        return $this;
    }

    /**
     * Set the Cc addresses of this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param mixed $addresses
     * @param string $name optional
     *
     * @return $this
     */
    public function setCc($addresses, $name = null)
    {
        $this->instance->setCc($addresses, $name);
        return $this;
    }

    /**
     * Get the Cc address of this message.
     *
     * @return array
     */
    public function getCc()
    {
        return $this->instance->getCc();
    }

    /**
     * Add a Bcc: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name optional
     *
     * @return $this
     */
    public function addBcc($address, $name = null)
    {
        $this->instance->addBcc($address, $name);
        return $this;
    }

    /**
     * Set the Bcc addresses of this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param mixed $addresses
     * @param string $name optional
     *
     * @return $this
     */
    public function setBcc($addresses, $name = null)
    {
        $this->instance->setBcc($addresses, $name);
        return $this;
    }

    /**
     * Get the Bcc addresses of this message.
     *
     * @return array
     */
    public function getBcc()
    {
        return $this->instance->getBcc();
    }

    /**
     * Get the whole list of recipients.
     * It's a merge of getTo(), getCc() and getBcc().
     *
     * @return array
     */
    public function getAllRecipients()
    {
        return array_merge(
            (array)$this->getTo(),
            (array)$this->getCc(),
            (array)$this->getBcc()
        );
    }

    /**
     * Set HTML content of the message.
     * This can either be a template path (twig) or an HTML string.
     *
     * Is case of a template path, this method understand what a render() call do.
     * So you can pass shorthand syntax path like: "@App/Emails/my-email.html.twig".
     *
     * @param string $template
     *
     * @return $this|string
     *
     * @throws
     */
    public function setHtml($template = null)
    {
        $this->html = $template;
        return $this;
    }

    /**
     * Gets the HTML content of the message.
     * Even if a template path has been given to the setHtml() method, the source code will be returned here.
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * Test if the message has a HTML content.
     *
     * @return boolean
     */
    public function hasHtmlContent()
    {
        return $this->html !== null;
    }

    /**
     * Set text content of the message.
     * This can either be a template path (twig) or the actual text content.
     *
     * Is case of a template path, this method understand what a render() call do.
     * So you can pass shorthand syntax path like: "@App/Emails/my-email.text.twig".
     *
     * @param string $template
     *
     * @return $this|string
     *
     * @throws
     */
    public function setText($template = null)
    {
        $this->text = $template;
        return $this;
    }

    /**
     * Gets the text content of the message.
     * Even if a template path has been given to the setText() method, the actual source text will be returned here.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Test if the message has a text content.
     *
     * @return boolean
     */
    public function hasTextContent()
    {
        return $this->text !== null;
    }

    /**
     * Set variables accessible from the templates.
     *
     * This methods overrides any other variables set previously.
     * Use addVariables() to keep existing ones.
     *
     * @param array $variables
     *
     * @return $this
     */
    public function setVariables(array $variables)
    {
        $this->variables = [];
        return $this->addvariables($variables);
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
        $this->variables = ArrayUtils::mergeRecursiveDistinct($this->variables, $variables);
        return $this;
    }

    /**
     * Get the array of variables accessible from the templates.
     *
     * @return array
     */
    public function getVariables()
    {
        return array_merge(['_baseUrl' => $this->baseUrl], $this->variables);
    }

    /**
     * Set extra private data that will not be part of the email.
     *
     * This methods overrides any other extra set previously.
     * Use addExtras() to keep existing ones.
     *
     * @param array $extras
     *
     * @return $this
     */
    public function setExtras(array $extras)
    {
        $this->extras = $extras;
    }

    /**
     * Add extra private data that will not be part of the email.
     *
     * @param array $extras
     *
     * @return $this
     */
    public function addExtras(array $extras)
    {
        $this->extras = ArrayUtils::mergeRecursiveDistinct($this->extras, $extras);
        return $this;
    }

    /**
     * Get extra private data associated with the message.
     *
     * @return array
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * Sets the priority header of a message.
     *
     * @param integer $priority
     *
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->instance->setPriority($priority);
        return $this;
    }

    /**
     * Gets the priority header of a message.
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->instance->getPriority();
    }

    /**
     * Add an attachment by path.
     *
     * @param string $path file's path
     * @param string $filename (optional) new file name
     * @param string $contentType (optional) content type
     * @param boolean $inline if true, content disposition will be changed to 'inline'
     *
     * @return $this
     */
    public function addAttachmentByPath($path, $filename = null, $contentType = null, $inline = false)
    {
        $attachment = \Swift_Attachment::fromPath($path, $contentType);
        if ($filename !== null) {
            $attachment->setFilename($filename);
        }
        if ($inline === true) {
            $attachment->setDisposition('inline');
        }
        $this->instance->attach($attachment);
        $this->attachments[] = ['path', $path, $filename, $contentType, $inline];
        return $this;
    }

    /**
     * Add an attachment using raw file data.
     *
     * @param mixed $data file's data
     * @param string $filename (optional) new file name
     * @param string $contentType (optional) content type
     * @param boolean $inline if true, content disposition will be changed to 'inline'
     *
     * @return $this
     */
    public function addAttachmentByData($data, $filename = null, $contentType = null, $inline = false)
    {
        $attachment = new \Swift_Attachment($data, $filename, $contentType);
        if ($filename !== null) {
            $attachment->setFilename($filename);
        }
        if ($inline === true) {
            $attachment->setDisposition('inline');
        }
        $this->instance->attach($attachment);
        $this->attachments[] = ['data', $data, $filename, $contentType, $inline];
        return $this;
    }

    /**
     * Sets the priority of a message in the spooler.
     *
     * @param string $priority
     *
     * @return $this
     */
    public function setSpoolerPriority($priority)
    {
        $this->spoolerPriority = $priority;
        return $this;
    }

    /**
     * Gets the priority of a message in the spooler.
     *
     * @return string
     */
    public function getSpoolerPriority()
    {
        return $this->spoolerPriority;
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
     * @return $this|boolean
     */
    public function webview($value = null)
    {
        if ($value === null) {
            return $this->hasWebview;
        }
        $this->hasWebview = !!$value;
        return $this;
    }

    /**
     * Get the real message instance ready to be sent to the mailer.
     * You can simply return $this if you have no underlying instance to work with.
     *
     * @return mixed
     */
    public function getInstance()
    {
        if ($this->html) {
            $this->instance->setBody($this->html, 'text/html');
            if ($this->text) {
                $this->instance->addPart($this->text, 'text/plain');
            }
        } else if ($this->text) {
            $this->instance->setBody($this->text, 'text/plain');
        }
        return $this->instance;
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize([
            'identifier' => $this->identifier,
            'subject' => $this->getSubject(),
            'returnPath' => $this->getReturnPath(),
            'sender' => $this->getSender(),
            'from' => $this->getFrom(),
            'replyTo' => $this->getReplyTo(),
            'to' => $this->getTo(),
            'cc' => $this->getCc(),
            'bcc' => $this->getBcc(),
            'html' => $this->getHtml(),
            'text' => $this->getText(),
            'variables' => $this->getVariables(),
            'attachments' => $this->attachments,
            'spoolerPriority' => $this->getSpoolerPriority(),
            'webview' => $this->hasWebview
        ]);
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        $decoded = unserialize($serialized);
        if ($this->instance === null) {
            $this->instance = new \Swift_Message();
        }
        $this->identifier = $decoded['identifier'];
        $this->setSubject($decoded['subject']);
        $this->setReturnPath($decoded['returnPath']);
        $this->setSender($decoded['sender']);
        $this->setFrom($decoded['from']);
        $this->setReplyTo($decoded['replyTo']);
        $this->setTo($decoded['to']);
        $this->setCc($decoded['cc']);
        $this->setBcc($decoded['bcc']);
        $this->setVariables($decoded['variables']);
        $this->setSpoolerPriority($decoded['spoolerPriority']);
        $this->webview(!!$decoded['webview']);
        $this->html = $decoded['html'];
        $this->text = $decoded['text'];
        foreach ($decoded['attachments'] as $data) {
            if (is_array($data) && $data) {
                call_user_func_array([$this, 'addAttachmentBy' . ucfirst($data[0])], array_slice($data, 1));
            }
        }
    }
}
