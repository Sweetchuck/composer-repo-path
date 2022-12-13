<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerRepoPath\Tests\Acceptance\Composer\Command;

use Sweetchuck\ComposerRepoPath\Test\AcceptanceTester;

class PrepareProjectCest extends CestBase
{
    public function basic(AcceptanceTester $I)
    {
        $this->fs->mkdir($this->projectDir);
        $projectJson = $this->basicComposerJson;
        $this->addSelfToComposerJson($projectJson);
        $this->addLibAsSuiteToComposerJson($projectJson);
        $projectJson['require-dev'] = $this->libRequire('1.x-dev') + $projectJson['require-dev'];

        $prSafe = escapeshellarg($this->projectDir);

        $envVars = sprintf(
            'COMPOSER_HOME=%s COMPOSER=%s',
            escapeshellarg($this->composerHome),
            escapeshellarg('composer.local.json'),
        );

        $I->wantTo('test the "composer repo-path:download" command');
        $I->writeToFile("{$this->projectDir}/composer.json", $this->jsonString($projectJson));
        $I->runShellCommand("cd $prSafe && composer update", true);
        $I->canSeeFileIsNotSymlink("{$this->projectDir}/vendor/sweetchuck/utils");
        $I->cantSeeDirFound("{$this->packagesDir}/sweetchuck/utils-1.x");

        $I->runShellCommand("cd $prSafe && composer suite:generate 2>&1", true);
        $I->runShellCommand("cd $prSafe && $envVars composer repo-path:download 2>&1", true);
        $I->canSeeDirFound("{$this->packagesDir}/sweetchuck/utils-1.x");

        $I->runShellCommand("cd $prSafe && $envVars composer update", true);
        $I->canSeeFileIsSymlink(
            "{$this->projectDir}/vendor/sweetchuck/utils",
            "../../../packages/sweetchuck/utils-1.x/",
        );

        $I->deleteDir($this->packagesDir);
        $I->runShellCommand("cd $prSafe && $envVars composer update", true);
        $I->canSeeDirFound("{$this->packagesDir}/sweetchuck/utils-1.x/.git");
        $I->canSeeFileIsSymlink(
            "{$this->projectDir}/vendor/sweetchuck/utils",
            "../../../packages/sweetchuck/utils-1.x/",
        );
    }
}
