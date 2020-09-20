<?php declare(strict_types=1);

namespace Kcs\MailerExtra\Mjml;

use Symfony\Component\Process\Process;

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
        if (null === $command) {
            $command = [ shell_exec('which mjml') ];
        }

        if (null === $cwd) {
            $cwd = getcwd() ?: __DIR__;
        }

        $this->command = $command;
        $this->cwd = $cwd;
    }

    /**
     * {@inheritdoc}
     */
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
