<?php

declare(strict_types=1);

namespace Kcs\MailerExtra\Mjml;

use Aws\Lambda\LambdaClient;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function array_filter;
use function class_exists;
use function is_numeric;
use function ltrim;
use function parse_str;
use function Safe\getcwd;
use function Safe\preg_match;
use function Safe\sprintf;
use const ARRAY_FILTER_USE_KEY;

class RendererFactory
{
    private const URL_PATTERN = '~^
        (?P<scheme>[\_\.\pL\pN\-\+]++)://                                 # protocol
        (?P<authority>((?:(?P<username>[\_\.\pL\pN-]|%%[0-9A-Fa-f]{2})+):?)?((?P<password>[\_\.\pL\pN-]|%%[0-9A-Fa-f]{2})+)@)?  # basic auth
        (?P<hostname>
            (
                (([\pL\pN\_\-%]|xn\-\-[\pL\pN-]+)+\.?)+ # a domain name
                    |                                                 # or
                \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                    # an IP address
                    |                                                 # or
                \[
                    (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                \]  # an IPv6 address
            )
            (:[0-9]+)?                              # a port (optional)
        )?
        (?P<path>(/ (?:[\pL\pN\-._\~!$&\'()*+,;=:@]|%%[0-9A-Fa-f]{2})* )*)          # a path
        (?P<querystring>(\? (?:[\pL\pN\-._\~!$&\'\[\]()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* ))?   # a query (optional)
        (?P<fragment>(\# (?:[\pL\pN\-._\~!$&\'()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* ))?       # a fragment (optional)
    $~ixu';

    private ?HttpClientInterface $httpClient;
    private ?LambdaClient $lambdaClient;

    public function __construct(?HttpClientInterface $httpClient = null, ?LambdaClient $lambdaClient = null)
    {
        if ($httpClient === null && class_exists(HttpClient::class)) {
            $httpClient = HttpClient::create();
        }

        if ($lambdaClient === null && class_exists(LambdaClient::class)) {
            $lambdaClient = new LambdaClient([]);
        }

        $this->httpClient = $httpClient;
        $this->lambdaClient = $lambdaClient;
    }

    public function factory(string $uri): RendererInterface
    {
        if (! preg_match(self::URL_PATTERN, $uri, $match)) {
            throw new InvalidConfigurationException(sprintf('Cannot parse renderer URI "%s"', $uri));
        }

        $url = array_filter($match, static fn ($key) => ! is_numeric($key) && (bool) $match[$key], ARRAY_FILTER_USE_KEY);
        parse_str(ltrim($url['querystring'], '?'), $qs);

        switch ($url['scheme'] ?? 'local') {
            case 'local':
                if (! class_exists(Process::class)) {
                    throw new InvalidConfigurationException('Local renderer needs symfony/process component to work correctly');
                }

                $path = ($url['hostname'] ?? '') . $url['path'] ?? '';
                $command = $qs['command'] ?? null;

                return new LocalProcessRenderer($command ? [ $command ] : null, $path ?: getcwd());
            case 'http':
            case 'https':
                if ($this->httpClient === null) {
                    throw new InvalidConfigurationException('Remote renderer (HTTP/HTTPS) needs symfony/http-client component to work correctly');
                }

                return new RemoteRenderer($this->httpClient, new Psr18Client($this->httpClient), $uri);
            case 'lambda+http':
                if ($this->lambdaClient === null) {
                    throw new InvalidConfigurationException('Lambda renderer needs aws sdk to be installed');
                }

                return new LambdaHttpRenderer($this->lambdaClient, $url['host'], $url['path']);
            case 'null':
                return new NullRenderer();
        }

        throw new InvalidConfigurationException(sprintf('Cannot create MJML renderer for URL "%s"', $uri));
    }
}
