<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerRepoPath\Composer\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class CommandBase extends BaseCommand implements InputAwareInterface
{
    protected array $result = [];

    protected InputInterface $input;

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    protected OutputInterface $output;

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function setOutput(OutputInterface $output): static
    {
        $this->output = $output;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->setInput($input);
            $this
                ->setOutput($output)
                ->doIt();
        } catch (\Exception $e) {
            $this->getIO()->error($e->getMessage());

            return 1;
        }

        return $this->result['exitCode'];
    }

    abstract protected function doIt(): static;
}
