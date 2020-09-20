<?php declare(strict_types=1);

namespace Kcs\MailerExtra\Mjml;

use Aws\Lambda\LambdaClient;

class LambdaHttpRenderer implements RendererInterface
{
    private LambdaClient $client;
    private string $functionArn;
    private string $path;

    public function __construct(LambdaClient $client, string $functionArn, string $path = '/mjml-render')
    {
        $this->client = $client;
        $this->functionArn = $functionArn;
        $this->path = $path;
    }

    public function render(string $markup): string
    {
        if (\preg_match('#^txt\+lambda://#', $this->functionArn)) {
            $url = \parse_url($this->functionArn);
            $host = $url['host'];

            $record = \dns_get_record($host, DNS_TXT);
            if (false !== $record && 0 !== \count($record)) {
                $host = $record[0]['txt'];
            }

            $this->functionArn = $host;
            $this->path = $url['path'];
        }

        $response = $this->client->invoke([
            'FunctionName' => $this->functionArn,
            'Payload' => \json_encode([
                'headers' => [
                    'content-type' => 'text/mjml',
                    'content-length' => \strlen($markup),
                    'X-Forwarded-Port' => 443,
                    'X-Forwarded-Proto' => 'https',
                ],
                'queryStringParameters' => null,
                'isBase64Encoded' => false,
                'body' => $markup,
                'httpMethod' => 'POST',
                'path' => $this->path,
                'requestContext' => [
                    'identity' => ['sourceIp' => '127.0.0.1'],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $payload = \json_decode((string) $response['Payload'], true, 512, JSON_THROW_ON_ERROR);
        $statusCode = $payload['statusCode'];
        if (200 !== $statusCode) {
            throw new \RuntimeException('Response not OK');
        }

        return $payload['body'];
    }
}
