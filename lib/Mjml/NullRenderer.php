<?php

declare(strict_types=1);

namespace Kcs\MailerExtra\Mjml;

class NullRenderer implements RendererInterface
{
    public function render(string $markup): string
    {
        return $markup;
    }
}
