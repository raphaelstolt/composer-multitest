<?php

namespace Stolt\Composer\Tests\PhpManager;

use Mockery;
use Stolt\Composer\PhpManager\Exceptions\DefaultVersionNotResolvable;
use Stolt\Composer\PhpManager\Exceptions\SwitchBackToDefaultPhpVersionFailed;
use Stolt\Composer\PhpManager\Phpenv;
use Stolt\Composer\Tests\TestCase;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PhpenvTest extends TestCase
{
    /**
     * @string
     */
    private $originalWorkingDirectory;

    public function setUp()
    {
        $this->originalWorkingDirectory = getcwd();
        $this->setUpTemporaryDirectory();
        chdir($this->temporaryDirectory);
    }

    public function tearDown()
    {
        $this->removeDirectory($this->temporaryDirectory);
        chdir($this->originalWorkingDirectory);
    }

    /**
     * @test
     */
    public function switchsBackToLocalDefaultPhpVersion()
    {
        $this->createPhpenvVersionFile('5.6.1');

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[mustRun,getOutput]',
            ['process-name']
        );

        $processMock->shouldReceive('mustRun')
            ->once()
            ->withAnyArgs();

        $manager = new Phpenv($ioMock, $processMock);

        $this->assertPhpenvVersionFileExists();
        $this->assertTrue($manager->switchBackToDefaultPhpVersion());
        $this->assertPhpenvVersionFileContains('5.6.1');
    }

    /**
     * @test
     */
    public function switchsBackToGlobalDefaultPhpVersion()
    {
        $this->assertPhpenvVersionFileNotExists();

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[mustRun,getOutput]',
            ['process-name']
        );

        $processMock->shouldReceive('mustRun')
            ->once()
            ->withAnyArgs();

        $manager = new Phpenv($ioMock, $processMock);
        $this->assertTrue($manager->switchBackToDefaultPhpVersion());
    }

    /**
     * @test
     */
    public function failingSwitchBackToDefaultPhpVersionThrowsAnException()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[mustRun,getOutput]',
            ['process-name']
        );

        $methodsToMock = [
            'isSuccessful',
            'getCommandLine',
            'getExitCode',
            'getExitCodeText',
            'getWorkingDirectory',
            'isOutputDisabled',
        ];

        $exceptionProcessMock = Mockery::mock(
            'Symfony\Component\Process\Process[' . implode(',', $methodsToMock) . ']',
            ['process-name']
        );

        $receives = [
            'isSuccessful' => false,
            'getCommandLine' => 'cl',
            'getExitCode' => 17,
            'getExitCodeText' => '17_text',
            'getWorkingDirectory' => 'test/dir',
            'isOutputDisabled' => true,
        ];

        $exceptionProcessMock->shouldReceive($receives)
            ->once()
            ->withAnyArgs();

        $processMock->shouldReceive('mustRun')
            ->once()
            ->withAnyArgs()
            ->andThrow(new ProcessFailedException($exceptionProcessMock));

        $this->expectException(SwitchBackToDefaultPhpVersionFailed::class);

        (new Phpenv($ioMock, $processMock))->switchBackToDefaultPhpVersion();
    }

    /**
     * @test
     */
    public function getRunnableVersionsReturnsExpectedVersions()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[mustRun,getOutput]',
            ['process-name']
        );

        $processMock->shouldReceive('mustRun')
            ->once()
            ->withAnyArgs();

        $processOutput = <<<CONTENT
  5.5.0
  5.6.1
* 7.0.1 (set by /YOUR-USERNAME/.phpenv/global)
CONTENT;

        $processMock->shouldReceive('getOutput')
            ->once()
            ->withAnyArgs()
            ->andReturn($processOutput);

        $manager = new Phpenv($ioMock, $processMock);

        $this->assertEquals(
            ['7.0.1', '5.6.1', '5.5.0'],
            $manager->getManagedVersions()
        );

        $this->assertEquals(
            '7.0.1',
            $manager->getDefaultPhpVersion()
        );

        $travisPhpVersions = ['7.0.10', '5.6.13'];

        $this->assertEquals(
            ['7.0.1', '5.6.1'],
            $manager->getRunnableVersions($travisPhpVersions)
        );
    }

    /**
     * @test
     */
    public function managedVersionsAreCached()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[mustRun,getOutput]',
            ['process-name']
        );

        $processMock->shouldReceive('mustRun')
            ->once()
            ->withAnyArgs();

        $processOutput = <<<CONTENT
  5.5.0
  5.6.1
* 7.0.1 (set by /YOUR-USERNAME/.phpenv/global)
CONTENT;

        $processMock->shouldReceive('getOutput')
            ->once()
            ->withAnyArgs()
            ->andReturn($processOutput);

        $manager = new Phpenv($ioMock, $processMock);

        $this->assertEquals(
            ['7.0.1', '5.6.1', '5.5.0'],
            $manager->getManagedVersions()
        );

        $processMock->shouldNotReceive('mustRun');

        $this->assertEquals(
            ['7.0.1', '5.6.1', '5.5.0'],
            $manager->getManagedVersions()
        );
    }

    /**
     * @test
     */
    public function hasLocalPhpenvVersionFileReturnsTrueOnExistence()
    {
        $method = new \ReflectionMethod(
          'Stolt\Composer\PhpManager\Phpenv', 'hasLocalPhpenvVersionFile'
        );
        $method->setAccessible(true);

        $this->createPhpenvVersionFile('5.6.1');

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[mustRun,getOutput]',
            ['process-name']
        );

        $processMock->shouldReceive('mustRun')
            ->once()
            ->withAnyArgs();

        $manager = new Phpenv($ioMock, $processMock);

        $this->assertPhpenvVersionFileExists();

        $this->assertTrue($method->invoke($manager));
    }

    /**
     * @test
     */
    public function isInstalledReturnsTrueWhenInstalled()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[mustRun,getOutput]',
            ['process-name']
        );

        $processMock->shouldReceive('mustRun')
            ->once()
            ->withAnyArgs();

        $manager = new Phpenv($ioMock, $processMock);

        $this->assertTrue($manager->isInstalled());
    }

    /**
     * @test
     */
    public function isInstalledReturnsFalseWhenNotInstalled()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[mustRun,getOutput]',
            ['process-name']
        );

        $methodsToMock = [
            'isSuccessful',
            'getCommandLine',
            'getExitCode',
            'getExitCodeText',
            'getWorkingDirectory',
            'isOutputDisabled',
        ];

        $exceptionProcessMock = Mockery::mock(
            'Symfony\Component\Process\Process[' . implode(',', $methodsToMock) . ']',
            ['process-name']
        );

        $receives = [
            'isSuccessful' => false,
            'getCommandLine' => 'cl',
            'getExitCode' => 17,
            'getExitCodeText' => '17_text',
            'getWorkingDirectory' => 'test/dir',
            'isOutputDisabled' => true,
        ];

        $exceptionProcessMock->shouldReceive($receives)
            ->once()
            ->withAnyArgs();

        $processMock->shouldReceive('mustRun')
            ->once()
            ->withAnyArgs()
            ->andThrow(new ProcessFailedException($exceptionProcessMock));

        $manager = new Phpenv($ioMock, $processMock);

        $this->assertFalse($manager->isInstalled());
    }

    /**
     * @test
     */
    public function hasLocalPhpenvVersionFileReturnsFalseOnNonExistence()
    {
        $method = new \ReflectionMethod(
          'Stolt\Composer\PhpManager\Phpenv', 'hasLocalPhpenvVersionFile'
        );
        $method->setAccessible(true);

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[mustRun,getOutput]',
            ['process-name']
        );

        $processMock->shouldReceive('mustRun')
            ->once()
            ->withAnyArgs();

        $manager = new Phpenv($ioMock, $processMock);

        $this->assertPhpenvVersionFileNotExists();
        $this->assertFalse($method->invoke($manager));
    }

    /**
     * @test
     */
    public function singleRunReturnsTrueWhenSuccessful()
    {
        $method = new \ReflectionMethod(
          'Stolt\Composer\PhpManager\Phpenv', 'singleRun'
        );
        $method->setAccessible(true);

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );
        $ioMock->shouldReceive('write')
            ->once()
            ->with(">> Running 'composer validate'.");

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[enableOutput,mustRun]',
            ['process-name']
        );
        $processMock->shouldReceive('enableOutput', 'mustRun')
            ->once()
            ->withAnyArgs();

        $manager = new Phpenv($ioMock, $processMock);

        $this->assertTrue(
            $method->invoke($manager, $this->getComposerScriptProcessMock())
        );
    }

    /**
     * @test
     */
    public function multiRunDelegatesToSingleRun()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );
        $ioMock->shouldReceive('write')
            ->once()
            ->with(">> Running 'composer validate'.");

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[enableOutput,mustRun]',
            ['process-name']
        );
        $processMock->shouldReceive('enableOutput', 'mustRun')
            ->once()
            ->withAnyArgs();

        $manager = new Phpenv($ioMock, $processMock);

        $manager->multiRun($this->getComposerScriptProcessMock(), ['7.0.1']);
    }

    /**
     * @test
     */
    public function multipleRunsAgainstThreeVersions()
    {
        $runnableVersions = ['7.1.0', '7.0.9', '5.6.17'];

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );
        $ioMock->shouldReceive('write')
            ->times(7)
            ->withAnyArgs();

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[mustRun]',
            ['process-name']
        );

        $processMock->shouldReceive('mustRun')
            ->once()
            ->withAnyArgs();

        $manager = new Phpenv($ioMock, $processMock);

        $this->forcePropertyValue($manager, 'managedVersions', $runnableVersions);
        $this->forcePropertyValue($manager, 'defaultPhpVersion', '7.1.0');

        $methodsToMock = [
            'enableOutput',
            'getCommandLine',
            'mustRun',
            'getIncrementalOutput',
        ];

        $composerScriptMock = Mockery::mock(
            'Symfony\Component\Process\Process[' . implode(',', $methodsToMock) . ']',
            ['process-name']
        );

        $receives = [
            'enableOutput' => true,
            'mustRun' => true,
            'getIncrementalOutput' => true,
        ];

        $composerScriptMock->shouldReceive('getCommandLine')
            ->once()
            ->withAnyArgs()
            ->andReturn('composer validate');

        $composerScriptMock->shouldReceive($receives)
            ->times(3)
            ->withAnyArgs();

        $this->assertTrue(
            $manager->multiRun($composerScriptMock, $runnableVersions)
        );
    }

    /**
     * @test
     */
    public function isSwitchBackToDefaultPhpVersionRequiredGuardReturnsFalse()
    {
        $method = new \ReflectionMethod(
          'Stolt\Composer\PhpManager\Phpenv', 'isSwitchBackToDefaultPhpVersionRequired'
        );
        $method->setAccessible(true);

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $phpenvManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\Phpenv',
            [$ioMock]
        );

        $this->forcePropertyValue(
            $phpenvManagerMock,
            'defaultPhpVersion',
            '7.1.0'
        );

        $this->assertFalse($method->invoke($phpenvManagerMock, '7.1.0'));
    }

    /**
     * @test
     */
    public function isSwitchBackToDefaultPhpVersionRequiredGuardReturnsTrue()
    {
        $method = new \ReflectionMethod(
          'Stolt\Composer\PhpManager\Phpenv', 'isSwitchBackToDefaultPhpVersionRequired'
        );
        $method->setAccessible(true);

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $phpenvManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\Phpenv',
            [$ioMock]
        );

        $this->forcePropertyValue(
            $phpenvManagerMock,
            'defaultPhpVersion',
            '7.1.0'
        );

        $this->assertTrue($method->invoke($phpenvManagerMock, '5.6.0'));
    }
}
