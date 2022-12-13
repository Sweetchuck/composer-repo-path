<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerRepoPath;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

class Handler implements LoggerAwareInterface
{

    protected string $logEntryPrefix = 'local path repository';

    protected string $baseDir = '.';

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    public function setBaseDir(string $dir)
    {
        $this->baseDir = $dir;

        return $this;
    }

    protected LoggerInterface $logger;

    public function getLogger(): LoggerInterface
    {
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    public function setLogger(LoggerInterface $value)
    {
        $this->logger = $value;

        return $this;
    }

    protected ?ProcessHelper $processHelper;

    public function getProcessHelper(): ?ProcessHelper
    {
        return $this->processHelper;
    }

    public function setProcessHelper(?ProcessHelper $value)
    {
        $this->processHelper = $value;

        return $this;
    }

    protected OutputInterface $output;

    public function getOutput(): OutputInterface
    {
        if (!$this->output) {
            $this->output = new NullOutput();
        }

        return $this->output;
    }

    public function setOutput(OutputInterface $value)
    {
        $this->output = $value;

        return $this;
    }

    /**
     * @todo Configurable.
     */
    protected string $gitExecutable = 'git';

    public function __construct(?ProcessHelper $processHelper = null)
    {
        $this->processHelper = $processHelper ?: $this->createProcessHelper();
    }

    protected function createProcessHelper(): ProcessHelper
    {
        $processHelper = new ProcessHelper();
        $processHelper->setHelperSet(new HelperSet());
        $processHelper->getHelperSet()->set(new DebugFormatterHelper());

        return $processHelper;
    }

    public function getRepositories(string $composerFile): array
    {
        $composerContent = file_get_contents($composerFile) ?: '{}';
        $composerData = json_decode($composerContent, true);

        return $composerData['repositories'] ?? [];
    }

    public function downloadRepositories(array $repositories)
    {
        $repositories = array_filter(
            $repositories,
            $this->getRepositoryTypeFilter(['path']),
        );

        if (!$repositories) {
            $this->getLogger()->info("{$this->logEntryPrefix}: there are no 'path' repositories");

            return $this;
        }

        foreach ($repositories as $repository) {
            $this->downloadRepository($repository);
        }

        return $this;
    }

    public function downloadRepository(array $repository)
    {
        assert($repository['type'] === 'path', "{$repository['type']} is not my type");
        $logger = $this->getLogger();

        $repository = array_replace_recursive(
            $this->getDefaultRepositoryDefinition(),
            $repository,
        );

        if (empty($repository['options']['repo-path']['url'])) {
            $logger->info("{$this->logEntryPrefix}: Git URL is empty: <comment>{$repository['url']}</comment>");

            return $this;
        }

        if (file_exists($repository['url'])) {
            $logger->info("{$this->logEntryPrefix}: already exists: <comment>{$repository['url']}</comment>");

            // @todo Is it a Git repository?
            // @todo Actual vs expected branch name.
            return $this;
        }

        $logger->info("{$this->logEntryPrefix}: Git clone: <comment>{$repository['url']}</comment>");
        try {
            $this->gitClone($repository);
        } catch (\Exception $e) {
            $logger->error(implode(
                \PHP_EOL,
                [
                    "{$this->logEntryPrefix}: Git clone failed: <comment>{$repository['url']}</comment>",
                    $e->getMessage(),
                ],
            ));
        }

        return $this;
    }

    public function getPackageNames(array $repositories): array
    {
        $names = [];
        foreach ($repositories as $key => $repository) {
            $names[$key] = $this->getPackageName(
                $repository,
                is_numeric($key) ? null : $key,
            );
        }

        return $names;
    }

    public function getPackageName(array $repository, ?string $default = null): string
    {
        assert($repository['type'] === 'path', "{$repository['type']} is not my type");

        $composerFileName = Path::join(
            $this->getBaseDir(),
            $repository['url'],
            'composer.json',
        );
        $composerData = file_exists($composerFileName) ?
            (file_get_contents($composerFileName) ?: '{}')
            : json_encode(['name' => $default]);
        $package = json_decode($composerData, true);
        settype($package['name'], 'string');

        return $package['name'] ?: $default;
    }

    protected function gitClone(array $repository)
    {
        $process = $this->getProcessHelper()->run(
            $this->getOutput(),
            $this->getGitCloneCommand($repository),
        );

        if ($process->getExitCode() !== 0) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $revision = $repository['options']['repo-path']['revision'] ?? null;
        if (!$revision) {
            return $this;
        }

        $process = $this->getProcessHelper()->run(
            $this->getOutput(),
            $this->getGitSwitchCommand($repository),
        );

        if ($process->getExitCode() !== 0) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $this;
    }

    protected function getGitCloneCommand(array $repository): array
    {
        $source =& $repository['options']['repo-path'];

        $command = [
            $this->gitExecutable,
            'clone',
        ];

        if (!empty($source['branch'])) {
            $command[] = "--branch={$source['branch']}";
        }

        $command[] = $source['url'];
        $command[] = $repository['url'];

        return $command;
    }

    protected function getGitSwitchCommand(array $repository): array
    {
        $source =& $repository['options']['repo-path'];

        return [
            $this->gitExecutable,
            "--git-dir={$repository['url']}/.git",
            'checkout',
            $source['revision'],
        ];
    }

    protected function getDefaultRepositoryDefinition(): array
    {
        // @todo Rename.
        return [
            'url' => '',
            'options' => [
                'repo-path' => [
                    'url' => '',
                    'remote' => 'upstream',
                    'branch' => '',
                    'revision' => '',
                ],
            ],
        ];
    }

    public function getRepositoryTypeFilter(array $allowedTypes = ['path']): \Closure
    {
        return function (array $repository) use ($allowedTypes) {
            return in_array($repository['type'] ?? '', $allowedTypes);
        };
    }
}
