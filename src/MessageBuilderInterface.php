<?php
namespace Webeak\Bundle\MailBundle;

interface MessageBuilderInterface
{
    /**
     * Get/Set the subject of the message.
     *
     * @param string $subject
     *
     * @return $this|string
     */
    public function subject($subject = null);

    /**
     * Get/Set the from address of the message.
     * You may pass an array of addresses if this message is from multiple people.
     *
     * @param string|array $addresses
     * @param string       $name
     * @return $this|mixed
     */
    public function from($addresses = null, $name = null);

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
    public function to($addresses = null, $name = null);

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
    public function andTo($addresses, $name = null);

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
    public function cc($addresses = null, $name = null);

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
    public function andCc($addresses, $name = null);

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
    public function bcc($addresses = null, $name = null);

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
    public function andBcc($addresses, $name = null);

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
    public function html($template = null);

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
    public function text($template = null);

    /**
     * Get/Set the priority header of the message.
     *
     * @param string $priority
     *
     * @return $this|integer
     */
    public function priority($priority = null);

    /**
     * Get/Set global variables (accessible for all recipients).
     *
     * This methods overrides any other global variables set previously.
     * Use addVariables() to keep existing ones.
     *
     * @param array $variables
     *
     * @return $this|array
     */
    public function variables(array $variables = null);

    /**
     * Add a global variable(accessible to all recipients).
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function addVariable($name, $value);

    /**
     * Add new variables to the set of global variables (accessible to all recipients).
     *
     * @param array $variables
     *
     * @return $this
     */
    public function addVariables(array $variables);

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
    public function extras(array $extras = null);

    /**
     * Add an extra value that will not be part of the email.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function addExtra($key, $value);

    /**
     * Add multiple extra values that will not be part of the email.
     *
     * @param array $extras
     *
     * @return $this
     */
    public function addExtras(array $extras);

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
    public function attachByPath($path, $filename = null, $contentType = null, $inline = false);

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
    public function attachByData($data, $filename, $contentType = null, $inline = false);

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
     * Add a custom text header.
     *
     * @param string $name
     * @param string $value
     *
     * @return mixed
     */
    public function addTextHeader(string $name, string $value);
    
    /**
     * Get the message behind the builder.
     *
     * @return MessageInterface
     */
    public function getMessage();

    /**
     * Get/Set priority of the message in the spooler.
     *
     * @param string $priority
     *
     * @return $this|string
     */
    public function spoolerPriority($priority = null);
}
