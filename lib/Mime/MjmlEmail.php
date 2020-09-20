<?php declare(strict_types=1);

namespace Kcs\MailerExtra\Mime;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class MjmlEmail extends TemplatedEmail
{
    private ?string $template = null;

    public function mjmlTemplate(?string $template, array $context = []): void
    {
        $this->template = $template;

        if ($context) {
            $this->context($context);
        }
    }

    public function getMjmlTemplate(): ?string
    {
        return $this->template;
    }
}
