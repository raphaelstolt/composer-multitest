<?php
namespace Stolt\Composer\Tests;

use Mockery;
use Stolt\Composer\Multitest;
use Stolt\Composer\PhpManager\PhpBrew;
use Stolt\Composer\PhpManager\Phpenv;
use Stolt\Composer\Tests\TestCase;

class MultitestTest extends TestCase
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
    public function errrosWhenNoPhpManagerPresent()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $phpenvManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\Phpenv[isInstalled]',
            [$ioMock]
        );
        $phpenvManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(false);

        $phpbrewManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\PhpBrew[isInstalled]',
            [$ioMock]
        );
        $phpbrewManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(false);

        Multitest::run(
            $this->getEventMockWithError('Neither phpenv nor PHPBrew installed.'),
            $phpenvManagerMock,
            $phpbrewManagerMock
        );
    }

    /**
     * @test
     */
    public function throwsExceptionOnMissingTravisConfiguration()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $phpenvManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\Phpenv[isInstalled]',
            [$ioMock]
        );
        $phpenvManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        $phpbrewManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\PhpBrew[isInstalled]',
            [$ioMock]
        );
        $phpbrewManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        $composerConfiguration = <<<CONTENT
{
    "scripts": {
        "cpe:test-all": "phpunit",
        "cpe:test": "phpunit --exclude-group integration",
        "cpe:test-with-coverage": "phpunit --coverage-html coverage-reports"
    }
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $this->assertTravisConfigurationFileNotExists();

        Multitest::run(
            $this->getEventMockWithError("Couldn't find a .travis.yml."),
            $phpenvManagerMock,
            $phpbrewManagerMock
        );
    }

    /**
     * @test
     */
    public function throwsExceptionWhenVersionsNotResolvable()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $phpenvManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\Phpenv[isInstalled]',
            [$ioMock]
        );
        $phpenvManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        $composerConfiguration = <<<CONTENT
{
    "scripts": {
        "cpe:test-all": "phpunit",
        "cpe:test": "phpunit --exclude-group integration",
        "cpe:test-with-coverage": "phpunit --coverage-html coverage-reports"
    }
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $travisConfiguration = <<<CONTENT
language: php

git:
  depth: 2

cache:
  directories:
    - \$HOME/.composer/cache
    - \$HOME/.php-cs-fixer

notifications:
  email: false
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $expectedErrorMessage = 'Unable to resolve versions.';

        Multitest::run(
            $this->getEventMockWithError($expectedErrorMessage),
            $phpenvManagerMock
        );
    }

    /**
     * @test
     */
    public function throwsExceptionWhenTravisConfigurationIsBlank()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $phpenvManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\Phpenv[isInstalled]',
            [$ioMock]
        );
        $phpenvManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        $travisConfiguration = <<<CONTENT

CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $composerConfiguration = <<<CONTENT
{
    "scripts": {
        "cpe:test-all": "phpunit",
        "cpe:test": "phpunit --exclude-group integration",
        "cpe:test-with-coverage": "phpunit --coverage-html coverage-reports"
    }
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $expectedErrorMessage = 'The .travis.yml is empty.';

        Multitest::run(
            $this->getEventMockWithError($expectedErrorMessage),
            $phpenvManagerMock
        );
    }

    /**
     * @test
     */
    public function throwsExceptionWhenTravisConfigurationIsNotParseable()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $phpenvManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\Phpenv[isInstalled]',
            [$ioMock]
        );
        $phpenvManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        $travisConfiguration = <<<CONTENT
{
    "type": "library"
}
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $composerConfiguration = <<<CONTENT
{
    "scripts": {
        "cpe:test-all": "phpunit",
        "cpe:test": "phpunit --exclude-group integration",
        "cpe:test-with-coverage": "phpunit --coverage-html coverage-reports"
    }
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $expectedErrorMessage = 'Unable to parse .travis.yml.';

        Multitest::run(
            $this->getEventMockWithError($expectedErrorMessage),
            $phpenvManagerMock
        );
    }

    /**
     * @test
     */
    public function throwsExceptionWhenComposerScriptNotResolvable()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $phpenvManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\Phpenv[isInstalled]',
            [$ioMock]
        );
        $phpenvManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        $phpbrewManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\PhpBrew[isInstalled]',
            [$ioMock]
        );
        $phpbrewManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        $composerConfiguration = <<<CONTENT
{
    "scripts": {
        "cpe:foo": "command a",
        "cpe:bar": "command b"
    }
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $travisConfiguration = <<<CONTENT
language: php

git:
  depth: 2

matrix:
  include:
    - php: hhvm
    - php: nightly
    - php: 7.1
      env:
      - LINT=true
    - php: 7
      env:
      - DISABLE_XDEBUG=true
    - php: 5.6
      env:
      - DISABLE_XDEBUG=true
  fast_finish: true
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $expectedErrorMessage = 'Unable to resolve test or spec Composer script.';

        Multitest::run(
            $this->getEventMockWithError($expectedErrorMessage),
            $phpenvManagerMock,
            $phpbrewManagerMock
        );
    }

    /**
     * @test
     */
    public function returnsTrueAfterSuccessfulMultiRun()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $phpenvManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\Phpenv[isInstalled,multiRun,getRunnableVersions]',
            [$ioMock]
        );
        $phpenvManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        $this->forcePropertyValue(
            $phpenvManagerMock,
            'managedVersions',
            ['7.1.0', '7.0.10']
        );
        $this->forcePropertyValue(
            $phpenvManagerMock,
            'defaultPhpVersion',
            '7.1.0'
        );

        $phpenvManagerMock->shouldReceive('getRunnableVersions')
            ->once()
            ->andReturn(['7.1.0', '7.0.10']);

        $phpenvManagerMock->shouldReceive('multiRun')
            ->once()
            ->andReturn(true);

        $composerConfiguration = <<<CONTENT
{
    "scripts": {
        "cpe:test": "command a",
        "cpe:bar": "command b"
    }
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $travisConfiguration = <<<CONTENT
language: php

git:
  depth: 2

matrix:
  include:
    - php: 7.1
      env:
      - LINT=true
    - php: 7
      env:
      - DISABLE_XDEBUG=true
  fast_finish: true
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $runStatus = Multitest::run(
            $this->getEventMock(),
            $phpenvManagerMock
        );

        $this->assertTrue($runStatus);
    }

    /**
     * @test
     */
    public function returnsFalseAfterFailingMultiRun()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $phpenvManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\Phpenv[isInstalled,multiRun,getRunnableVersions]',
            [$ioMock]
        );
        $phpenvManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        $this->forcePropertyValue(
            $phpenvManagerMock,
            'managedVersions',
            ['7.1.0', '7.0.10']
        );
        $this->forcePropertyValue(
            $phpenvManagerMock,
            'defaultPhpVersion',
            '7.1.0'
        );

        $phpenvManagerMock->shouldReceive('getRunnableVersions')
            ->once()
            ->andReturn(['7.1.0', '7.0.10']);

        $phpenvManagerMock->shouldReceive('multiRun')
            ->once()
            ->andReturn(false);

        $composerConfiguration = <<<CONTENT
{
    "scripts": {
        "cpe:test": "command a",
        "cpe:bar": "command b"
    }
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $travisConfiguration = <<<CONTENT
language: php

git:
  depth: 2

matrix:
  include:
    - php: 7.1
      env:
      - LINT=true
    - php: 7
      env:
      - DISABLE_XDEBUG=true
  fast_finish: true
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $runStatus = Multitest::run(
            $this->getEventMock(),
            $phpenvManagerMock
        );

        $this->assertFalse($runStatus);
    }

    /**
     * @test
     */
    public function returnsFalseForMissingPhpVersion()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $phpenvManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\Phpenv[isInstalled,multiRun,getRunnableVersions]',
            [$ioMock]
        );
        $phpenvManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        $this->forcePropertyValue(
            $phpenvManagerMock,
            'managedVersions',
            ['7.1.0', '7.0.10']
        );
        $this->forcePropertyValue(
            $phpenvManagerMock,
            'defaultPhpVersion',
            '7.1.0'
        );

        $phpenvManagerMock->shouldReceive('getRunnableVersions')
            ->once()
            ->andReturn(['7.1.0', '7.0.10']);

        $phpenvManagerMock->shouldReceive('multiRun')
            ->once()
            ->andReturn(false);

        $composerConfiguration = <<<CONTENT
{
    "scripts": {
        "cpe:test": "command a",
        "cpe:bar": "command b"
    }
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $travisConfiguration = <<<CONTENT
language: php

git:
  depth: 2

matrix:
  include:
    - php: 7.1
      env:
      - LINT=true
    - php: 7
      env:
      - DISABLE_XDEBUG=true
    - php: 5.6
      env:
      - DISABLE_XDEBUG=true
  fast_finish: true
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $skipMissingVersionsOption = Multitest::SKIP_MISSING_VERSIONS_OPTION;
        $expectedErrorMessage = <<<CONTENT
Unable to run 'composer cpe:test' against all PHP versions. Aborting.
This prerequisite can be disabled by setting the '{$skipMissingVersionsOption}' option.
CONTENT;

        $runStatus = Multitest::run(
            $this->getEventMockWithError($expectedErrorMessage),
            $phpenvManagerMock
        );

        $this->assertFalse($runStatus);
    }

    /**
     * @test
     */
    public function allVersionsPrerequisiteCanBeDisabled()
    {
        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $phpenvManagerMock = Mockery::mock(
            'Stolt\Composer\PhpManager\Phpenv[isInstalled,multiRun,getRunnableVersions]',
            [$ioMock]
        );
        $phpenvManagerMock->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        $this->forcePropertyValue(
            $phpenvManagerMock,
            'managedVersions',
            ['7.1.0', '7.0.10']
        );
        $this->forcePropertyValue(
            $phpenvManagerMock,
            'defaultPhpVersion',
            '7.1.0'
        );

        $phpenvManagerMock->shouldReceive('getRunnableVersions')
            ->once()
            ->andReturn(['7.1.0', '7.0.10']);

        $phpenvManagerMock->shouldReceive('multiRun')
            ->once()
            ->andReturn(true);

        $composerConfiguration = <<<CONTENT
{
    "scripts": {
        "cpe:test": "command a",
        "cpe:bar": "command b"
    }
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $travisConfiguration = <<<CONTENT
language: php

git:
  depth: 2

matrix:
  include:
    - php: 7.1
      env:
      - LINT=true
    - php: 7
      env:
      - DISABLE_XDEBUG=true
    - php: 5.6
      env:
      - DISABLE_XDEBUG=true
  fast_finish: true
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $runStatus = Multitest::run(
            $this->getEventMockWithArguments(
                [Multitest::SKIP_MISSING_VERSIONS_OPTION]
            ),
            $phpenvManagerMock
        );

        $this->assertTrue($runStatus);
    }

    /**
     * @param  string $expectedErrorMessage The expected error message
     *                                      to check against.
     *
     * @return Composer\Script\Event
     */
    protected function getEventMockWithError($expectedErrorMessage)
    {
        $composerMock = Mockery::mock(
            'Composer\Composer'
        );

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[writeError]'
        );

        $ioMock->shouldReceive('writeError')
            ->once()
            ->with($expectedErrorMessage);

        $eventMock = Mockery::mock(
            'Composer\Script\Event[getIO]',
            ['event-name', $composerMock, $ioMock]
        );

        $eventMock->shouldReceive('getIO')
            ->once()
            ->withNoArgs()
            ->andReturn($ioMock);

        return $eventMock;
    }

    /**
     * @param  array $arguments The Composer script arguments.
     *
     * @return Composer\Script\Event
     */
    protected function getEventMockWithArguments(array $arguments)
    {
        $composerMock = Mockery::mock(
            'Composer\Composer'
        );

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $eventMock = Mockery::mock(
            'Composer\Script\Event[getIO,getArguments]',
            ['event-name', $composerMock, $ioMock]
        );

        $eventMock->shouldReceive('getIO')
            ->once()
            ->withNoArgs()
            ->andReturn($ioMock);

        $eventMock->shouldReceive('getArguments')
            ->once()
            ->withNoArgs()
            ->andReturn($arguments);

        return $eventMock;
    }

    /**
     * @return Composer\Script\Event
     */
    protected function getEventMock()
    {
        $composerMock = Mockery::mock(
            'Composer\Composer'
        );

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface'
        );

        $eventMock = Mockery::mock(
            'Composer\Script\Event[getIO]',
            ['event-name', $composerMock, $ioMock]
        );

        $eventMock->shouldReceive('getIO')
            ->once()
            ->withNoArgs()
            ->andReturn($ioMock);

        return $eventMock;
    }
}
