<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerRepoPath\Tests\Unit;

use org\bovigo\vfs\vfsStream;
use Sweetchuck\ComposerRepoPath\Handler;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\Process\Process;

/**
 * @covers \Sweetchuck\ComposerRepoPath\Handler
 */
class HandlerTest extends TestBase
{
    public function testGetRepositories()
    {
        $expected = [
            'a' => [
                'type' => 'git',
                'url' => 'a_url',
            ],
            'b' => [
                'type' => 'path',
                'url' => 'b_url',
            ],
        ];
        $composerFile = 'composer.json';
        $vfsStructure = [
            'composer.json' => json_encode([
                'repositories' => [
                    'a' => [
                        'type' => 'git',
                        'url' => 'a_url',
                    ],
                    'b' => [
                        'type' => 'path',
                        'url' => 'b_url',
                    ],
                ],
            ]),
        ];

        $vfs = vfsStream::setup(
            __FUNCTION__,
            0777,
            $vfsStructure,
        );

        $composerFile = $vfs->url() . '/' . $composerFile;
        $handler = new Handler();
        $this->tester->assertSame($expected, $handler->getRepositories($composerFile));
    }

    public function casesPrepareRepositories(): array
    {
        return [
            'no repositories' => [
                'expected' => [
                    'commands' => [],
                    'logEntries' => [
                        [
                            'info',
                            "local path repository: there are no 'path' repositories",
                            [],
                        ],
                    ],
                ],
                'repositories' => [
                    'zzz/aaa' => [
                        'type' => 'git',
                        'url' => './zzz/aaa-1.x',
                    ],
                ],
            ],
            'basic' => [
                'expected' => [
                    'commands' => [
                        [
                            'command' => [
                                'git',
                                'clone',
                                "--branch=1.x",
                                'https://zzz/aaa.git',
                                './zzz/aaa-1.x',
                            ],
                            'exitCode' => 0,
                            'stdOutput' => '',
                            'stdError' => '',
                        ],
                    ],
                    'logEntries' => [
                        [
                            'info',
                            "local path repository: Git URL is empty: <comment>./zzz/aaa-1.x</comment>",
                            [],
                        ],
                        [
                            'info',
                            "local path repository: already exists: <comment>./composer.json</comment>",
                            [],
                        ],
                        [
                            'info',
                            'local path repository: Git clone: <comment>./zzz/aaa-1.x</comment>',
                            [],
                        ],
                    ],
                ],
                'repositories' => [
                    'empty/url' => [
                        'type' => 'path',
                        'url' => './zzz/aaa-1.x',
                        'options' => [
                            'repo-path' => [],
                        ],
                    ],
                    'already/url' => [
                        'type' => 'path',
                        'url' => './composer.json',
                        'options' => [
                            'repo-path' => [
                                'url' =>  'https://zzz/aaa.git',
                            ],
                        ],
                    ],
                    'zzz/aaa' => [
                        'type' => 'path',
                        'url' => './zzz/aaa-1.x',
                        'options' => [
                            'repo-path' => [
                                'url' =>  'https://zzz/aaa.git',
                                'branch' => '1.x',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesPrepareRepositories
     */
    public function testPrepareRepositories(array $expected, array $repositories)
    {
        $logger = new BufferingLogger();
        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $processHelper = $this->createMockProcessHelper($output, $expected['commands']);

        $handler = (new Handler($processHelper))
            ->setProcessHelper($processHelper)
            ->setLogger($logger)
            ->setOutput($output);

        $handler->downloadRepositories($repositories);

        $this->tester->assertSame($expected['logEntries'], $logger->cleanLogs());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Console\Helper\ProcessHelper
     */
    protected function createMockProcessHelper(OutputInterface $output, array $commands)
    {
        $runReturnMap = [];
        foreach ($commands as $command) {
            $runReturnMap[] = [
                $output,
                $command['command'],
                $command['error'] ?? null,
                $command['callback'] ?? null,
                $command['verbosity'] ?? OutputInterface::VERBOSITY_VERY_VERBOSE,
                $this->createMockProcess(
                    $command['exitCode'] ?? 0,
                    $command['stdOutput'] ?? '',
                    $command['stdError'] ?? '',
                ),
            ];
        }

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Console\Helper\ProcessHelper $processHelper */
        $processHelper = $this
            ->getMockBuilder(ProcessHelper::class)
            ->onlyMethods(['run'])
            ->getMock();
        $processHelper
            ->expects($this->exactly(count($commands)))
            ->method('run')
            ->willReturnMap($runReturnMap);

        return $processHelper;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Process\Process
     */
    protected function createMockProcess(
        int $exitCode = 0,
        string $stdOutput = '',
        string $stdError = ''
    ) {
        /** @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Process\Process $process */
        $process = $this
            ->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getExitCode',
                'getOutput',
                'getErrorOutput',
            ])
            ->getMock();
        $process
            ->method('getExitCode')
            ->willReturn($exitCode);
        $process
            ->method('getOutput')
            ->willReturn($stdOutput);
        $process
            ->method('getErrorOutput')
            ->willReturn($stdError);

        return $process;
    }
}
