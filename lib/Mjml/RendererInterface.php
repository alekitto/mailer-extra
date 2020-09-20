<?php declare(strict_types=1);

namespace Kcs\MailerExtra\Mjml;

interface RendererInterface
{
    /**
     * Renders a mjml markup converting it into HTML.
     */
    public function render(string $markup): string;
}
