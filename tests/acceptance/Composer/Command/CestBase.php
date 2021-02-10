<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerRepoPath\Tests\Acceptance\Composer\Command;

use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

abstract class CestBase
{
    protected string $caseDir = '';

    protected string $packagesDir = '';

    protected string $composerHome = '';

    protected string $projectDir = '';

    protected int $jsonEncodeFlags = \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT;

    protected Filesystem $fs;

    protected array $basicComposerJson = [
        'type' =>'library',
        'name' =>'my/p01',
        'description' =>'@todo project description',
        'license' =>'GPL-3.0-or-later',
        'minimum-stability' => 'dev',
        'prefer-stable' => true,
        'config' => [
            'notify-on-install' => false,
            'optimize-autoloader' => true,
            'preferred-install' => "dist",
            'sort-packages' => true,
        ],
        'repositories' => [],
        'require' => [],
        'require-dev' => [],
    ];

    protected string $selfVendor = 'sweetchuck';

    protected string $selfName = 'composer-repo-path';

    protected string $selfDistFile = 'sweetchuck-composer-repo-path-dev';

    protected string $selfDistFormat = 'zip';

    public function _before()
    {
        $this->jsonEncodeFlags = \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT;
        $this->selfVendor = 'sweetchuck';
        $this->selfName = 'composer-repo-path';
        $this->selfDistFile = "{$this->selfVendor}-{$this->selfName}-dev-";
        $this->selfDistFormat = 'zip';
        $this->caseDir = tempnam(sys_get_temp_dir(), "{$this->selfName}-");
        $this->fs = new Filesystem();
        $this->fs->remove($this->caseDir);
        $this->fs->mkdir($this->caseDir);
        $this->composerHome = Path::join($this->caseDir, 'composer-home');
        $this->packagesDir = Path::join($this->caseDir, 'packages');
        $this->projectDir = Path::join($this->caseDir, 'project-01');

        $this->createSelfArchive();
    }

    protected function createSelfArchive()
    {
        $command = sprintf(
            '%s archive --file=%s --format=%s',
            escapeshellcmd('composer'),
            escapeshellarg($this->selfDistFile),
            escapeshellarg($this->selfDistFormat),
        );
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        if ($exitCode !== 0) {
            throw new \Exception(implode(\PHP_EOL, $output));
        }

        return $this;
    }

    public function _after()
    {
        $this->fs->remove($this->caseDir);
    }

    protected function jsonString(array $json): string
    {
        if (empty($json['require'])) {
            unset($json['require']);
        }

        if (empty($json['require-dev'])) {
            unset($json['require-dev']);
        }

        return json_encode($json, $this->jsonEncodeFlags) . "\n";
    }

    protected function selfProjectRoot(): string
    {
        return dirname(__DIR__, 4);
    }

    protected function addSelfToComposerJson(array &$composerJson)
    {
        $selfProjectRoot = $this->selfProjectRoot();
        $repo = [
            'type' => 'package',
            'package' => json_decode(file_get_contents('./composer.json'), true),
        ];
        $repo['package']['version'] = '1.0.0';
        $repo['package']['dist'] = [
            'type' => $this->selfDistFormat,
            'url' => "file://$selfProjectRoot/{$this->selfDistFile}.{$this->selfDistFormat}",
        ];

        $composerJson += ['repositories' => []];
        unset($composerJson['repositories']['self']);
        $composerJson['repositories'] = ['self' => $repo] + $composerJson['repositories'];
        $composerJson['require']["{$this->selfVendor}/{$this->selfName}"] = '*';

        return $this;
    }

    protected function addLibAsDirectToComposerJson(array &$composerJson)
    {
        $composerJson['repositories'] = $this->libRepositories() + $composerJson['repositories'];
        $composerJson['require-dev'] = $this->libRequire() + $composerJson['require-dev'];
    }

    protected function addLibAsSuiteToComposerJson(array &$composerJson)
    {
        $composerJson['require-dev']['sweetchuck/composer-suite'] = '1.x-dev';
        $composerJson['extra']['composer-suite']['local'] = [
            [
                'type' => 'prepend',
                'config' => [
                    'parents' => ['repositories'],
                    'items' => $this->libRepositories(),
                ],
            ],
            [
                'type' => 'replaceRecursive',
                'config' => [
                    'parents' => ['require-dev'],
                    'items' => $this->libRequire(),
                ],
            ],
        ];
    }

    protected function libRepositories(): array
    {
        return [
            'sweetchuck/utils' => [
                'type' => 'path',
                'url' => '../packages/sweetchuck/utils-1.x',
                'options' => [
                    'repo-path' => [
                        'url' => 'https://github.com/Sweetchuck/utils.git',
                        'remote' => 'upstream',
                        'branch' => '1.x',
                    ],
                ],
            ],
        ];
    }

    protected function libRequire(string $versionConstraint = '*'): array
    {
        return [
            'sweetchuck/utils' => $versionConstraint,
        ];
    }
}
