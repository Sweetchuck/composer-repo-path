<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerRepoPath\Composer;

use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;
use Sweetchuck\ComposerRepoPath\Composer\Command\DownloadCommand;
use Sweetchuck\ComposerRepoPath\Composer\Command\ListCommand;

class CommandProvider implements ComposerCommandProvider
{
    /**
     * {@inheritDoc}
     */
    public function getCommands()
    {
        return [
            new DownloadCommand(),
            new ListCommand(),
        ];
    }
}
