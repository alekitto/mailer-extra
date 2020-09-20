<?php

declare(strict_types=1);

namespace Kcs\MailerExtra\Mime;

use Kcs\MailerExtra\Mjml\RendererInterface;
use Symfony\Bridge\Twig\Mime\WrappedTemplatedEmail;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Message;
use Twig\Environment;
use function array_merge;
use function assert;
use function get_class;
use function Safe\sprintf;
use function trim;

class MjmlBodyRenderer implements BodyRendererInterface
{
    private Environment $twig;
    private BodyRendererInterface $bodyRenderer;
    private RendererInterface $mjmlRenderer;

    public function __construct(Environment $twig, BodyRendererInterface $bodyRenderer, RendererInterface $mjmlRenderer)
    {
        $this->twig = $twig;
        $this->bodyRenderer = $bodyRenderer;
        $this->mjmlRenderer = $mjmlRenderer;
    }

    public function render(Message $message): void
    {
        $template = $message instanceof MjmlEmail ? $message->getMjmlTemplate() : null;
        if (! empty($template)) {
            assert($message instanceof MjmlEmail);

            $messageContext = $message->getContext();
            if (isset($messageContext['email'])) {
                throw new InvalidArgumentException(sprintf('A "%s" context cannot have an "email" entry as this is a reserved variable.', get_class($message)));
            }

            $vars = array_merge($messageContext, [
                'email' => new WrappedTemplatedEmail($this->twig, $message),
            ]);

            $twigTemplate = $this->twig->load($template);
            $html = $this->mjmlRenderer->render($twigTemplate->render($vars));

            if ($twigTemplate->hasBlock('title', $vars)) {
                $title = $twigTemplate->renderBlock('title', $vars);
                $message->subject(trim($title));
            }

            $message
                ->html($html)
                ->htmlTemplate(null);
        }

        $this->bodyRenderer->render($message);
    }
}
