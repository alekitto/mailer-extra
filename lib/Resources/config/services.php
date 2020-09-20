<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Aws\Lambda\LambdaClient;
use Kcs\MailerExtra\Mjml\RendererFactory;
use Kcs\MailerExtra\Mjml\RendererInterface;
use Symfony\Component\DependencyInjection\Reference;
use function class_exists;

return static function (ContainerConfigurator $container) {
    if (class_exists(LambdaClient::class)) {
        $container->services()
            ->defaults()
            ->private()

            ->set('kcs.mailer-extra.mjml.aws_lambda_client', LambdaClient::class);
    }

    $container->services()
        ->defaults()
        ->private()

        ->set(RendererFactory::class)
            ->args([
                service('http_client')->nullOnInvalid(),
                service('kcs.mailer-extra.mjml.aws_lambda_client')->nullOnInvalid(),
            ])

        ->set(RendererInterface::class)
            ->factory([ new Reference(RendererFactory::class), 'factory' ])
            ->args([ null ]);
};
