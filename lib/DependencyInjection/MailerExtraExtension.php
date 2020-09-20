<?php

declare(strict_types=1);

namespace Kcs\MailerExtra\DependencyInjection;

use Kcs\MailerExtra\Mjml\RendererInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class MailerExtraExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
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

        $container->findDefinition(RendererInterface::class)->replaceArgument(0, $config['renderer']);
    }
}
