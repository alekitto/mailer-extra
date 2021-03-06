<?php

declare(strict_types=1);

namespace Kcs\MailerExtra\Mime;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class MjmlEmail extends TemplatedEmail
{
    private ?string $template = null;

    /**
     * @param array<string, mixed> $context
     */
    public function mjmlTemplate(?string $template, array $context = []): void
    {
        $this->template = $template;

        if (! $context) {
            return;
        }

        $this->context($context);
    }

    public function getMjmlTemplate(): ?string
    {
        return $this->template;
    }
}
