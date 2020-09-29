<?php

declare(strict_types=1);

namespace Kcs\MailerExtra\DependencyInjection;

use Aws\Lambda\LambdaClient;
use Kcs\MailerExtra\Mjml\RendererInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use function assert;
use function class_exists;

class MailerExtraExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->getConfiguration($configs, $container);
        assert($config instanceof Configuration);

        $configuration = $this->processConfiguration($config, $configs);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.php');
        $this->configureMjml($container, $configuration['mjml']);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @phpstan-param array{enabled: bool, renderer: string} $config
     */
    private function configureMjml(ContainerBuilder $container, array $config): void
    {
        $config = $container->resolveEnvPlaceholders($config);
        if (! $config['enabled']) {
            return;
        }

        if (class_exists(LambdaClient::class)) {
            $container->register('kcs.mailer-extra.mjml.aws_lambda_client', LambdaClient::class)
                ->addArgument($config['aws_lambda_client_options']);
        }

        $container->findDefinition(RendererInterface::class)->replaceArgument(0, $config['renderer']);
    }
}
