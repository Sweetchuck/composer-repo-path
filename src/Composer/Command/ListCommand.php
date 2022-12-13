<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerRepoPath\Composer\Command;

use Composer\Factory as ComposerFactory;
use Sweetchuck\ComposerRepoPath\Handler;

class ListCommand extends CommandBase
{

    protected Handler $handler;

    public function __construct(
        string $name = null,
        ?Handler $handler = null
    ) {
        $this->handler = $handler ?: new Handler();
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        if (!$this->getName()) {
            $this->setName('repo-path:list');
        }

        // @todo Better description and help.
        $this->setDescription('Lists "path" repositories.');
        $this->setHelp('Lists "path" repositories.');
    }

    protected function doIt()
    {
        $this->result = [
            'exitCode' => 0,
        ];

        $this->handler->setLogger($this->getIO());
        $this->handler->setOutput($this->getOutput());
        $this->handler->setProcessHelper($this->getHelper('process'));

        $repositories = array_filter(
            $this->handler->getRepositories(ComposerFactory::getComposerFile()),
            $this->handler->getRepositoryTypeFilter(),
        );

        $packageNames = $this->handler->getPackageNames($repositories);
        $output = $this->getOutput();
        foreach ($packageNames as $packageName) {
            $output->writeln($packageName);
        }

        return $this;
    }
}
