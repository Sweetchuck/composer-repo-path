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

    /**
     * @return $this
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    protected OutputInterface $output;

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @return $this
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this
                ->setInput($input)
                ->setOutput($output)
                ->doIt();
        } catch (\Exception $e) {
            $this->getIO()->error($e->getMessage());

            return 1;
        }

        return $this->result['exitCode'];
    }

    /**
     * @return $this
     */
    abstract protected function doIt();
}
