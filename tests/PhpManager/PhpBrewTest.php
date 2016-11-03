<?php

namespace Stolt\Composer\Tests\PhpManager;

use Mockery;
use Stolt\Composer\PhpManager\Exceptions\DefaultVersionNotResolvable;
use Stolt\Composer\PhpManager\Exceptions\SwitchBackToDefaultPhpVersionFailed;
use Stolt\Composer\PhpManager\PhpBrew;
use Stolt\Composer\Tests\TestCase;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PhpBrewTest extends TestCase
{
    /**
     * @test
     */
    public function accessingNullDefaultPhpVersionThrowsAnException()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[mustRun,getOutput]',
            ['process-name']
        );

        $processMock->shouldReceive('mustRun')
            ->once()
            ->withAnyArgs();

        $processOutput = <<<CONTENT
  php-7.1.0
  php-7.0.4
  php-5.6.19
CONTENT;

        $processMock->shouldReceive('getOutput')
            ->once()
            ->withAnyArgs()
            ->andReturn($processOutput);

        $this->expectException(DefaultVersionNotResolvable::class);

        $manager = new PhpBrew($ioMock, $processMock);
        $manager->getDefaultPhpVersion();
    }

    /**
     * @test
     */
    public function multipleVersionsAreReturned()
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
  php-7.1.0
  php-7.0.4
* php-5.6.19
CONTENT;

        $processMock->shouldReceive('getOutput')
            ->once()
            ->withAnyArgs()
            ->andReturn($processOutput);

        $manager = new PhpBrew($ioMock, $processMock);

        $expectedVersions = ['php-7.1.0', 'php-7.0.4', 'php-5.6.19'];
        $expectedDefaultVersion = 'php-5.6.19';

        $managedVersions = $manager->getManagedVersions();

        $this->assertEquals($expectedVersions, $managedVersions);
        $this->assertEquals($expectedDefaultVersion, $manager->getDefaultPhpVersion());
    }

    /**
     * @test
     */
    public function singleVersionIsReturned()
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
* php-5.6.19
CONTENT;

        $processMock->shouldReceive('getOutput')
            ->once()
            ->withAnyArgs()
            ->andReturn($processOutput);

        $manager = new PhpBrew($ioMock, $processMock);

        $expectedVersions = ['php-5.6.19'];
        $expectedDefaultVersion = 'php-5.6.19';

        $managedVersions = $manager->getManagedVersions();

        $this->assertEquals($expectedVersions, $managedVersions);
        $this->assertEquals($expectedDefaultVersion, $manager->getDefaultPhpVersion());
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

        $manager = new PhpBrew($ioMock, $processMock);

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


        $manager = new PhpBrew($ioMock, $processMock);

        $this->assertFalse($manager->isInstalled());
    }

    /**
     * @test
     */
    public function managesMultipleVersionsReturnsTrueWhenMultipleVersionsPhpBrewManaged()
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
  php-7.1.0
  php-7.0.4
* php-5.6.19
CONTENT;

        $processMock->shouldReceive('getOutput')
            ->once()
            ->withAnyArgs()
            ->andReturn($processOutput);

        $manager = new PhpBrew($ioMock, $processMock);

        $this->assertTrue($manager->managesMultipleVersions());
    }

    /**
     * @test
     */
    public function managesMultipleVersionsReturnsFalseWhenSingleVersionPhpBrewManaged()
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
* php-5.6.19
CONTENT;

        $processMock->shouldReceive('getOutput')
            ->once()
            ->withAnyArgs()
            ->andReturn($processOutput);

        $manager = new PhpBrew($ioMock, $processMock);

        $this->assertFalse($manager->managesMultipleVersions());
    }

    /**
     * @test
     */
    public function successfulSwitchBackToDefaultPhpVersionReturnsTrue()
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

        $manager = new PhpBrew($ioMock, $processMock);

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

        (new PhpBrew($ioMock, $processMock))->switchBackToDefaultPhpVersion();
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
  php-7.1.0
  php-7.0.4
* php-5.6.19
CONTENT;

        $processMock->shouldReceive('getOutput')
            ->once()
            ->withAnyArgs()
            ->andReturn($processOutput);

        $manager = new PhpBrew($ioMock, $processMock);

        $this->assertEquals(
            ['php-7.1.0', 'php-7.0.4', 'php-5.6.19'],
            $manager->getManagedVersions()
        );
        $this->assertEquals(
            'php-5.6.19',
            $manager->getDefaultPhpVersion()
        );

        $travisPhpVersions = ['7.1.14', '7.0.10'];

        $this->assertEquals(
            ['php-7.1.0', 'php-7.0.4'],
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
  php-5.5.0
  php-5.6.1
* php-7.0.1
CONTENT;

        $processMock->shouldReceive('getOutput')
            ->once()
            ->withAnyArgs()
            ->andReturn($processOutput);

        $manager = new PhpBrew($ioMock, $processMock);

        $this->assertEquals(
            ['php-7.0.1', 'php-5.6.1', 'php-5.5.0'],
            $manager->getManagedVersions()
        );

        $processMock->shouldNotReceive('mustRun');

        $this->assertEquals(
            ['php-7.0.1', 'php-5.6.1', 'php-5.5.0'],
            $manager->getManagedVersions()
        );
    }

    /**
     * @test
     */
    public function getRunnableReturnsDefaultVersionWhenManagingSingleVersion()
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
* php-7.1.6
CONTENT;

        $processMock->shouldReceive('getOutput')
            ->once()
            ->withAnyArgs()
            ->andReturn($processOutput);

        $manager = new PhpBrew($ioMock, $processMock);

        $this->assertEquals(
            ['php-7.1.6'],
            $manager->getManagedVersions()
        );
        $this->assertEquals(
            'php-7.1.6',
            $manager->getDefaultPhpVersion()
        );

        $travisPhpVersions = ['7.1.14', '7.0.10'];

        $this->assertEquals(
            ['php-7.1.6'],
            $manager->getRunnableVersions($travisPhpVersions)
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
            'Symfony\Component\Process\Process[enableOutput,mustRun,getIncrementalOutput]',
            ['process-name']
        );
        $processMock->shouldReceive('enableOutput', 'mustRun', 'getIncrementalOutput')
            ->once()
            ->withAnyArgs();

        $manager = new PhpBrew($ioMock, $processMock);

        $manager->multiRun($this->getComposerScriptProcessMock(), ['7.0.1']);
    }

    /**
     * @test
     */
    public function multipleRunsAgainstTwoVersions()
    {
        $runnableVersions = ['7.1.0', '7.0.9'];

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );
        $ioMock->shouldReceive('write')
            ->times(5)
            ->withAnyArgs();

        $processMock = Mockery::mock(
            'Symfony\Component\Process\Process[mustRun]',
            ['process-name']
        );

        $processMock->shouldReceive('mustRun')
            ->once()
            ->withAnyArgs();

        $manager = new PhpBrew($ioMock, $processMock);

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
            ->twice()
            ->withAnyArgs();

        $this->assertTrue(
            $manager->multiRun($composerScriptMock, $runnableVersions)
        );
    }
}
