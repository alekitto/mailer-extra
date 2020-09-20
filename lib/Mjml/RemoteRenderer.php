<?php

declare(strict_types=1);

namespace Kcs\MailerExtra\Mjml;

use Psr\Http\Message\UriFactoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function count;
use function Safe\dns_get_record;
use function Safe\parse_url;
use function Safe\preg_match;
use const DNS_SRV;

class RemoteRenderer implements RendererInterface
{
    private HttpClientInterface $httpClient;
    private UriFactoryInterface $uriFactory;
    private string $uri;

    public function __construct(HttpClientInterface $httpClient, UriFactoryInterface $uriFactory, string $uri)
    {
        $this->httpClient = $httpClient;
        $this->uriFactory = $uriFactory;
        $this->uri = $uri;
    }

    public function render(string $markup): string
    {
        if (preg_match('#^srv\+(https?)://#', $this->uri, $matches)) {
            $url = parse_url($this->uri);
            $host = $url['host'];
            $port = $url['port'] ?? null;

            $srvRecord = dns_get_record($host, DNS_SRV);
            if ($srvRecord !== false && count($srvRecord) !== 0) {
                $host = $srvRecord[0]['target'];
                $port = $srvRecord[0]['port'];
            }

            $uri = $this->uriFactory
                ->createUri($matches[1] . '://' . $host)
                ->withPort($port)
                ->withQuery($url['query'] ?? '');

            if (isset($url['path'])) {
                $uri = $uri->withPath($uri->getPath() . $url['path']);
            }

            $this->uri = (string) $uri;
        }

        $response = $this->httpClient->request('POST', $this->uri, ['body' => $markup]);

        return $response->getContent();
    }
}
