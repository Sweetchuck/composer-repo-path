<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerRepoPath\Tests\Acceptance\Composer\Command;

use Sweetchuck\ComposerRepoPath\Test\AcceptanceTester;

class PrepareGlobalCest extends CestBase
{
    public function basic(AcceptanceTester $I)
    {
        $I->wantTo('use "repo-path" as a global Composer plugin');

        $this->fs->mkdir($this->composerHome);
        $homeJson = $this->basicComposerJson;
        $this->addSelfToComposerJson($homeJson);
        $chSafe = escapeshellarg($this->composerHome);
        $I->writeToFile("{$this->composerHome}/composer.json", $this->jsonString($homeJson));
        $I->runShellCommand("cd $chSafe && composer update", true);

        $envVars = sprintf(
            'COMPOSER_HOME=%s',
            escapeshellarg($this->composerHome),
        );
        $this->fs->mkdir($this->projectDir);
        $prSafe = escapeshellarg($this->projectDir);
        $projectJson = $this->basicComposerJson;
        $this->addLibAsDirectToComposerJson($projectJson);

        $I->writeToFile("{$this->projectDir}/composer.json", $this->jsonString($projectJson));
        $I->runShellCommand("cd $prSafe && $envVars composer update", true);
        $I->canSeeDirFound("{$this->packagesDir}/sweetchuck/utils-1.x/.git");
        $I->canSeeFileIsSymlink(
            "{$this->projectDir}/vendor/sweetchuck/utils",
            "../../../packages/sweetchuck/utils-1.x/",
        );
    }
}
