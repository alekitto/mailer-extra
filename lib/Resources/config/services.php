<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Kcs\MailerExtra\Mjml\RendererFactory;
use Kcs\MailerExtra\Mjml\RendererInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $container) {
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
