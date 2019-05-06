<?php
namespace Webeak\Bundle\MailBundle;

/**
 * Represent a message.
 *
 * @package Webeak\Bundle\MailBundle
 */
interface MessageInterface
{
    /**
     * Get the unique id of the message.
     *
     * @return string|null
     */
    public function getIdentifier(): ?string;

    /**
     * Set the subject of this message.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject);

    /**
     * Get the subject of this message.
     *
     * @return string
     */
    public function getSubject();

    /**
     * Set the return-path (the bounce address) of this message.
     *
     * @param string $address
     *
     * @return $this
     */
    public function setReturnPath($address);
    
    /**
     * Get the return-path (bounce address) of this message.
     *
     * @return string
     */
    public function getReturnPath();

    /**
     * Set the sender of this message.
     *
     * @param string $address
     * @param string $name    optional
     *
     * @return $this
     */
    public function setSender($address, $name = null);

    /**
     * Get the sender of this message.
     *
     * @return string
     */
    public function getSender();

    /**
     * Add a From: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name    optional
     *
     * @return $this
     */
    public function addFrom($address, $name = null);
    
    /**
     * Set the from address of this message.
     *
     * You may pass an array of addresses if this message is from multiple people.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param string|array $addresses
     * @param string       $name      optional
     *
     * @return $this
     */
    public function setFrom($addresses, $name = null);

    /**
     * Get the from address of this message.
     *
     * @return mixed
     */
    public function getFrom();

    /**
     * Add a Reply-To: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name    optional
     *
     * @return $this
     */
    public function addReplyTo($address, $name = null);
    
    /**
     * Set the reply-to address of this message.
     *
     * You may pass an array of addresses if replies will go to multiple people.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param mixed  $addresses
     * @param string $name      optional
     *
     * @return $this
     */
    public function setReplyTo($addresses, $name = null);

    /**
     * Get the reply-to address of this message.
     *
     * @return string
     */
    public function getReplyTo();

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
    public function setTo($addresses, $name = null);

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
    public function addTo($addresses, $name = null);

    /**
     * Get the To addresses of this message.
     *
     * @return array
     */
    public function getTo();
    
    /**
     * Add a Cc: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name    optional
     *
     * @return $this
     */
    public function addCc($address, $name = null);

    /**
     * Set the Cc addresses of this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param mixed  $addresses
     * @param string $name      optional
     *
     * @return $this
     */
    public function setCc($addresses, $name = null);

    /**
     * Get the Cc address of this message.
     *
     * @return array
     */
    public function getCc();

    /**
     * Add a Bcc: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name    optional
     *
     * @return $this
     */
    public function addBcc($address, $name = null);

    /**
     * Set the Bcc addresses of this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param mixed  $addresses
     * @param string $name      optional
     *
     * @return $this
     */
    public function setBcc($addresses, $name = null);

    /**
     * Get the Bcc addresses of this message.
     *
     * @return array
     */
    public function getBcc();

    /**
     * Get the whole list of recipients.
     * It's a merge of getTo(), getCc() and getBcc().
     *
     * @return array
     */
    public function getAllRecipients();

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
     */
    public function setHtml($template = null);

    /**
     * Gets the HTML content of the message.
     * Even if a template path has been given to the setHtml() method, the source code will be returned here.
     *
     * @return string
     */
    public function getHtml();

    /**
     * Test if the message has a HTML content.
     *
     * @return boolean
     */
    public function hasHtmlContent();

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
     */
    public function setText($template = null);

    /**
     * Gets the text content of the message.
     * Even if a template path has been given to the setText() method, the actual source text will be returned here.
     *
     * @return string
     */
    public function getText();

    /**
     * Test if the message has a text content.
     *
     * @return boolean
     */
    public function hasTextContent();

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
    public function setVariables(array $variables);

    /**
     * Add new variables to the set of variables accessible from the templates.
     *
     * @param array $variables
     *
     * @return $this
     */
    public function addVariables(array $variables);

    /**
     * Get the array of variables accessible from the templates.
     *
     * @return array
     */
    public function getVariables();

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
    public function setExtras(array $extras);

    /**
     * Add extra private data that will not be part of the email.
     *
     * @param array $extras
     *
     * @return $this
     */
    public function addExtras(array $extras);

    /**
     * Get extra private data associated with the message.
     *
     * @return array
     */
    public function getExtras();

    /**
     * Add an attachment by path.
     *
     * @param string  $path        file's path
     * @param string  $filename    (optional) new file name
     * @param string  $contentType (optional) content type
     * @param boolean $inline      if true, content disposition will be changed to 'inline'
     *
     * @return $this
     */
    public function addAttachmentByPath($path, $filename = null, $contentType = null, $inline = false);

    /**
     * Add an attachment using raw file data.
     *
     * @param mixed   $data        file's data
     * @param string  $filename    (optional) new file name
     * @param string  $contentType (optional) content type
     * @param boolean $inline      if true, content disposition will be changed to 'inline'
     *
     * @return $this
     */
    public function addAttachmentByData($data, $filename = null, $contentType = null, $inline = false);

    /**
     * Sets the priority of a message in the spooler.
     *
     * @param string $priority
     *
     * @return $this
     */
    public function setSpoolerPriority($priority);

    /**
     * Gets the priority of a message in the spooler.
     *
     * @return string
     */
    public function getSpoolerPriority();

    /**
     * Sets the priority header of a message.
     *
     * @param integer $priority
     *
     * @return $this
     */
    public function setPriority($priority);

    /**
     * Gets the priority header of a message.
     *
     * @return integer
     */
    public function getPriority();

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
    public function webview($value = null);

    /**
     * Called just before the message is about to be sent.
     *
     * Get the real message instance ready to be sent to the mailer.
     * You can simply return $this if you have no underlying instance to work with.
     *
     * This should ONLY be done by the spooler.
     *
     * @return mixed
     */
    public function getInstance();
}
