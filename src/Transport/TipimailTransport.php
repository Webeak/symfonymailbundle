<?php
namespace Webeak\Bundle\MailBundle\Transport;

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TipimailTransport extends AbstractTransport
{
    private ?string $username;
    private ?string $apiKey;

    public function __construct(private readonly HttpClientInterface $client, Dsn $dsn)
    {
        parent::__construct();
        $this->username = $dsn->getUser();
        $this->apiKey = $dsn->getPassword();
        if (!$this->username || !$this->apiKey) {
            throw new \InvalidArgumentException('The "dsn" argument must include username and API key.');
        }
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();
        if (!$email instanceof Email) {
            throw new \InvalidArgumentException(sprintf('The message must be an instance of "%s".', Email::class));
        }
        $payload = $this->getPayload($email);

        $response = $this->client->request('POST', 'https://api.tipimail.com/v1/messages/send', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Tipimail-ApiUser' => $this->username,
                'X-Tipimail-ApiKey' => $this->apiKey,
            ],
            'body' => json_encode($payload),
        ]);
        if ($response->getStatusCode() !== 200) {
            throw new TransportException('Error sending email via Tipimail: '.$response->getContent(false));
        }
    }

    private function getPayload(Email $email): array
    {
        $payload = [
            'to' => array_map(function ($address) {
                return array_filter([
                    'address' => $address->getAddress(),
                    'personalName' => $address->getName() ?? null,
                ]);
            }, array_merge($email->getTo(), $email->getCc(), $email->getBcc())),
            'msg' => [
                'from' => array_filter([
                    'personalName' => $email->getFrom()[0]->getName() ?? null,
                    'address' => $email->getFrom()[0]->getAddress(),
                ]),
                'subject' => $email->getSubject(),
                'text' => $email->getTextBody(),
                'html' => $email->getHtmlBody()
            ],
            'apiKey' => $this->apiKey
        ];

        if (count($email->getReplyTo()) > 0) {
            $payload['msg']['replyTo'] = array_map(function ($address) {
                return array_filter([
                    'personalName' => $address->getName() ?? null,
                    'address' => $address->getAddress(),
                ]);
            }, $email->getReplyTo())[0];
        }
        $headers = $email->getHeaders()->all();
        if ($headers) {
            $customHeaders = [];
            foreach ($headers as $name => $header) {
                // Exclude headers that are not custom, like 'From', 'To', 'Subject', etc.
                if (!in_array(strtolower($name), ['from', 'to', 'subject', 'cc', 'bcc', 'reply-to', 'x-priority', 'x-tracked-ref'], true)) {
                    $customHeaders[$name] = $header->getBodyAsString();
                }
            }
            if ($customHeaders) {
                $payload['headers'] = $customHeaders;
            }
        }

        $attachments = $email->getAttachments();
        if (count($attachments) > 0) {
            $payload['msg']['attachments'] = [];
            foreach ($attachments as $attachment) {
                $content = $attachment->getBody();
                $payload['msg']['attachments'][] = [
                    'contentType' => $attachment->getMediaType() . '/' . $attachment->getMediaSubtype(),
                    'filename' => $attachment->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename'),
                    'content' => base64_encode($content)
                ];
            }
        }
        return $payload;
    }

    public function __toString(): string
    {
        return 'tipimail';
    }
}
