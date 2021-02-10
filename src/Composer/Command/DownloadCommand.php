<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerRepoPath\Composer\Command;

use Composer\Factory as ComposerFactory;
use Sweetchuck\ComposerRepoPath\Handler;

class DownloadCommand extends CommandBase
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
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();
        if (!$this->getName()) {
            $this->setName('repo-path:download');
        }

        // @todo Better description and help.
        $this->setDescription('Prepares the missing "path" repositories.');
        $this->setHelp('Prepares the missing "path" repositories.');
    }

    protected function doIt()
    {
        $this->result = [
            'exitCode' => 0,
        ];

        $repositories = $this->handler->getRepositories(ComposerFactory::getComposerFile());
        $this->handler->setLogger($this->getIO());
        $this->handler->setOutput($this->getOutput());
        $this->handler->setProcessHelper($this->getHelper('process'));
        $this->handler->downloadRepositories($repositories);

        return $this;
    }
}
