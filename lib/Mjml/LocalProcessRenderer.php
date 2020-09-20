<?php

declare(strict_types=1);

namespace Kcs\MailerExtra\Mjml;

use Symfony\Component\Process\Process;
use function Safe\getcwd;
use function shell_exec;

class LocalProcessRenderer implements RendererInterface
{
    /** @var string[] */
    private array $command;

    /** @var string */
    private $cwd;

    /**
     * @param string[]|null $command
     */
    public function __construct(?array $command = null, ?string $cwd = null)
    {
        if ($command === null) {
            $command = [ (string) shell_exec('which mjml') ];
        }

        if ($cwd === null) {
            $cwd = getcwd() ?: __DIR__;
        }

        $this->command = $command;
        $this->cwd = $cwd;
    }

    public function render(string $markup): string
    {
        $command = $this->command;
        $command[] = '--stdin';
        $command[] = '--stdout';

        $process = new Process($command, $this->cwd);
        $process->setInput($markup);
        $process->run();

        return $process->getOutput();
    }
}
