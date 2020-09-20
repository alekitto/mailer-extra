<?php declare(strict_types=1);

namespace Tests\Kcs\MailerExtra\Mime;

use Kcs\MailerExtra\Mime\MjmlBodyRenderer;
use Kcs\MailerExtra\Mime\MjmlEmail;
use Kcs\MailerExtra\Mjml\RendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Template;
use Twig\TemplateWrapper;

class MjmlBodyRendererTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|Environment */
    private ObjectProphecy $twig;

    /** ObjectProphecy|BodyRendererInterface */
    private ObjectProphecy $bodyRenderer;

    /** @var RendererInterface|ObjectProphecy */
    private ObjectProphecy $mjmlRenderer;
    private MjmlBodyRenderer $renderer;

    protected function setUp(): void
    {
        $this->twig = $this->prophesize(Environment::class);
        $this->twig->mergeGlobals(Argument::any())->will(fn ($args) => $args[0]);
        $this->twig->isDebug()->willReturn(true);

        $this->bodyRenderer = $this->prophesize(BodyRendererInterface::class);
        $this->mjmlRenderer = $this->prophesize(RendererInterface::class);

        $this->renderer = new MjmlBodyRenderer($this->twig->reveal(), $this->bodyRenderer->reveal(), $this->mjmlRenderer->reveal());
    }

    public function testRenderShouldForwardToInnerRendererIfNotAMjmlEmailObject(): void
    {
        $message = new Email();
        $this->bodyRenderer->render($message)->shouldBeCalled();
        $this->mjmlRenderer->render(Argument::any())->shouldNotBeCalled();

        $this->renderer->render($message);
    }

    public function testRenderShouldRenderTwigTemplateAndPassItToMjml(): void
    {
        $message = new MjmlEmail();
        $message->mjmlTemplate('email/foobar.mjml.twig', ['var_1' => 'foo']);

        $template = $this->prophesize(Template::class);
        $this->twig->load('email/foobar.mjml.twig')
            ->willReturn(new TemplateWrapper($this->twig->reveal(), $template->reveal()));

        $template->hasBlock('title', Argument::cetera())->willReturn(true);
        $template->displayBlock('title', Argument::cetera())->will(function (): void {
            echo 'Email subject';
        });
        $template->render(Argument::withEntry('var_1', 'foo'), Argument::any())->willReturn('<mjml>template</mjml>');

        $this->bodyRenderer->render($message)
            ->shouldBeCalled()
            ->will(function (array $args): void {
                [$email] = $args;
                MjmlBodyRendererTest::assertNull($email->getHtmlTemplate());
            });

        $this->mjmlRenderer->render('<mjml>template</mjml>')
            ->shouldBeCalled()
            ->willReturn('<html>markup</html>');

        $this->renderer->render($message);

        self::assertEquals('Email subject', $message->getSubject());
        self::assertEquals('<html>markup</html>', $message->getHtmlBody());
    }
}
