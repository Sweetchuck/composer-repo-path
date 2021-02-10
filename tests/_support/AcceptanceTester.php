<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerRepoPath\Test;

use Codeception\Actor;
use Codeception\PHPUnit\TestCase as Assert;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends Actor
{

    use _generated\AcceptanceTesterActions;

    public function canSeeDirFound(string $path)
    {
        Assert::assertTrue(is_dir($path), "path '$path' exists and it is a directory");
    }

    public function cantSeeDirFound(string $path)
    {
        Assert::assertFileNotExists($path);
    }

    public function canSeeFileIsSymlink(
        string $filename,
        ?string $pointsTo = null
    ) {
        Assert::assertFileExists($filename);
        $actualPointsTo = readlink($filename);
        Assert::assertNotFalse($actualPointsTo);
        if ($pointsTo !== null) {
            Assert::assertSame(
                $actualPointsTo,
                $pointsTo,
                "$filename symlink points to $pointsTo",
            );
        }
    }

    public function canSeeFileIsNotSymlink(string $filename)
    {
        Assert::assertFileExists($filename);
        Assert::assertFalse(is_link($filename), "$filename is not a symlink");
    }
}
