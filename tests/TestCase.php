<?php

namespace Stolt\Composer\Tests;

use Mockery;
use PHPUnit_Framework_TestCase as PHPUnit;
use ReflectionClass;

class TestCase extends PHPUnit
{
    /**
     * @var string
     */
    protected $temporaryDirectory;

    /**
     * @var string
     */
    protected $travisConfigurationFile;

    /**
     * @var string
     */
    protected $composerConfigurationFile;

    /**
     * @var string
     */
    protected $phpenvVersionFile;

    /**
     * Set up temporary directory.
     *
     * @return void
     */
    protected function setUpTemporaryDirectory()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            ini_set('sys_temp_dir', '/tmp/ctl');
            $this->temporaryDirectory = '/tmp/ctl';
        } else {
            $this->temporaryDirectory = sys_get_temp_dir()
                . DIRECTORY_SEPARATOR
                . 'ctl';
        }

        if (!file_exists($this->temporaryDirectory)) {
            mkdir($this->temporaryDirectory);
        }
    }

    /**
     * Remove directory and files in it.
     *
     * @return void
     */
    protected function removeDirectory($directory)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                @rmdir($fileinfo->getRealPath());
                continue;
            }
            @unlink($fileinfo->getRealPath());
        }

        @rmdir($directory);
    }

    /**
     * Overwrite an object property with given value.
     *
     * @param mixed $object        The object having the property to overwrite.
     * @param string $propertyName The name of the property.
     * @param string $value        The value to overwrite with.
     *
     * @return void
     */
    protected function forcePropertyValue($object, $propertyName, $value)
    {
        $reflectionClass = new ReflectionClass($object);

        $property = $reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
        $property->setAccessible(false);
    }

    /**
     * @return Symfony\Component\Process\Process
     */
    protected function getComposerScriptProcessMock()
    {
        $methodsToMock = [
            'enableOutput',
            'getCommandLine',
            'mustRun',
        ];

        $composerScriptMock = Mockery::mock(
            'Symfony\Component\Process\Process[' . implode(',', $methodsToMock) . ']',
            ['process-name']
        );

        $receives = [
            'enableOutput' => true,
            'getCommandLine' => 'composer validate',
            'mustRun' => true,
        ];

        $composerScriptMock->shouldReceive($receives)
            ->once()
            ->withAnyArgs();

        return $composerScriptMock;
    }

    /**
     * Create Travis CI configuration file.
     *
     * @param  string $content
     * @return void
     */
    protected function createTravisConfigurationFile($content)
    {
        $this->travisConfigurationFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . '.travis.yml';

        file_put_contents($this->travisConfigurationFile, $content);
    }

    /**
     * Custom assertion.
     *
     * @param string $message
     */
    protected function assertTravisConfigurationFileExists($message = '')
    {
        $this->assertFileExists($this->travisConfigurationFile, $message);
    }

    /**
     * Custom assertion.
     *
     * @param string $message
     */
    protected function assertTravisConfigurationFileNotExists($message = '')
    {
        $travisConfigurationFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . '.travis.yml';

        $this->assertFileNotExists($travisConfigurationFile, $message);
    }

    /**
     * Create Composer configuration file.
     *
     * @param  string $content
     * @return void
     */
    protected function createComposerConfigurationFile($content)
    {
        $this->composerConfigurationFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . 'composer.json';

        file_put_contents($this->composerConfigurationFile, $content);
    }

    /**
     * Custom assertion.
     *
     * @param string $message
     */
    protected function assertComposerConfigurationFileExists($message = '')
    {
        $this->assertFileExists($this->composerConfigurationFile, $message);
    }

    /**
     * Custom assertion.
     *
     * @param string $message
     */
    protected function assertComposerConfigurationFileNotExists($message = '')
    {
        $composerConfigurationFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . 'composer.json';

        $this->assertFileNotExists($composerConfigurationFile, $message);
    }

    /**
     * Create a phpenv .phpenv-version file.
     *
     * @param  string $content
     * @return void
     */
    protected function createPhpenvVersionFile($content)
    {
        $this->phpenvVersionFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . '.phpenv-version';

        file_put_contents($this->phpenvVersionFile, $content);
    }

    /**
     * Custom assertion.
     *
     * @param string $message
     */
    protected function assertPhpenvVersionFileExists($message = '')
    {
        $this->assertFileExists($this->phpenvVersionFile, $message);
    }

    /**
     * Custom assertion.
     *
     * @param string $version The version phpenv version file must contain.
     * @param string $message
     */
    protected function assertPhpenvVersionFileContains($version, $message = '')
    {
        $this->assertEquals(
            $version,
            trim(file_get_contents($this->phpenvVersionFile)),
            $message
        );
    }

    /**
     * Custom assertion.
     *
     * @param string $message
     */
    protected function assertPhpenvVersionFileNotExists($message = '')
    {
        $phpenvVersionFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . '.phpenv-version';

        $this->assertFileNotExists($phpenvVersionFile, $message);
    }
}
