<?php declare(strict_types=1);

namespace Tests\Kcs\MailerExtra\DependencyInjection;

use Kcs\MailerExtra\DependencyInjection\MailerExtraExtension;
use Kcs\MailerExtra\Mjml\LambdaHttpRenderer;
use Kcs\MailerExtra\Mjml\LocalProcessRenderer;
use Kcs\MailerExtra\Mjml\RemoteRenderer;
use Kcs\MailerExtra\Mjml\RendererInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MailerExtraExtensionTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    public function testDefaultConfiguration(): void
    {
        $extension = new MailerExtraExtension();
        $extension->load([], $this->container);

        self::assertFalse($this->container->hasDefinition(LocalProcessRenderer::class));
        self::assertFalse($this->container->hasDefinition(RemoteRenderer::class));
        self::assertFalse($this->container->hasDefinition(LambdaHttpRenderer::class));
        self::assertFalse($this->container->hasAlias(RendererInterface::class));
    }

    public function testDefaultMjmlRenderer(): void
    {
        $extension = new MailerExtraExtension();
        $extension->load([
            [ 'mjml' => [ 'enabled' => true ] ],
        ], $this->container);

        self::assertTrue($this->container->hasDefinition(RendererInterface::class));
    }
}
