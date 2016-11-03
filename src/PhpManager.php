<?php

namespace Stolt\Composer;

use Composer\IO\IOInterface;
use Stolt\Composer\PhpManager\Exceptions\DefaultVersionNotResolvable;
use Stolt\Composer\PhpManager\Exceptions\SwitchBackToDefaultPhpVersionFailed;
use Symfony\Component\Process\Process;

abstract class PhpManager
{

    /**
     * @var string
     */
    protected $defaultPhpVersion;

    /**
     * @var array
     */
    protected $managedVersions = [];


    /**
     * @var Composer\IO\IOInterface
     */
    protected $io;

    /**
     * @var Symfony\Component\Process\Process
     */
    protected $process;

    /**
     * @param IOInterface  $io      The io interface for user feedback.
     * @param Process|null $process The process to interact with the PHP manager.
     */
    public function __construct(IOInterface $io, Process $process = null)
    {
        $this->io = $io;
        $this->process = $process;
    }

    /**
     * Get the runnable versions.
     *
     * @param  array $travisVersions The versions defined in .travis.yml.
     * @return array
     */
    public function getRunnableVersions(array $travisVersions)
    {
        if ($this->managesMultipleVersions() === false) {
            return [$this->getDefaultPhpVersion()];
        }

        $patchlessTravisVersions = [];
        array_filter($travisVersions, function ($version) use (&$patchlessTravisVersions) {
            list($major, $minor, $patch) = explode('.', $version);
            $patchlessTravisVersions[] = $major . '.' . $minor;
        });

        $runnables = [];
        array_filter($this->managedVersions, function ($version) use (&$runnables, &$patchlessTravisVersions) {
            list($major, $minor, $patch) = explode('.', str_replace('php-', '', $version));
            $patchlessManagedVersion =  $major . '.' . $minor;

            if (in_array($patchlessManagedVersion, $patchlessTravisVersions)) {
                $runnables[] = $version;
            }
        });

        return $runnables;
    }

    /**
     * Check if the PHP manager manages multiple PHP versions.
     *
     * @return boolean
     */
    public function managesMultipleVersions()
    {
        if ($this->managedVersions === []) {
            $this->getManagedVersions();
        }

        return count($this->managedVersions) > 1;
    }

    /**
     * Accessor for the default PHP version.
     *
     * @throws Stolt\Composer\PhpManager\Exceptions\DefaultVersionNotResolvable
     * @return string
     */
    public function getDefaultPhpVersion()
    {
        if ($this->managedVersions === []) {
            $this->getManagedVersions();
        }

        if ($this->defaultPhpVersion === null) {
            throw new DefaultVersionNotResolvable;
        }

        return $this->defaultPhpVersion;
    }

    /**
     * Guard if switch back to default PHP version is required.
     *
     * @param  string $currentActiveVersion The current active version.
     * @return boolean
     */
    protected function isSwitchBackToDefaultPhpVersionRequired($currentActiveVersion)
    {
        if ($this->defaultPhpVersion === trim($currentActiveVersion)) {
            return false;
        }

        return true;
    }

    /**
     * Runs the Composer script when only single
     * runnable version available.
     *
     * @param  Process $composerScript The Composer script to run.
     *
     * @throws RuntimeException
     * @throws ProcessFailedException
     * @return boolean
     *
     */
    protected function singleRun(Process $composerScript)
    {
        $message = ">> Running '" . $composerScript->getCommandLine() . "'.";
        $this->io->write($message);

        $composerScript->enableOutput();
        $composerScript->mustRun();

        return true;
    }

    /**
     * Multi run the Composer script against the managed verions.
     *
     * @param Process $composerScript   The Composer script to run.
     * @param array   $runnableVersions The versions to run against.
     *
     * @throws RuntimeException
     * @throws ProcessFailedException
     * @return boolean
     *
     */
    abstract public function multiRun(Process $composerScript, array $runnableVersions);

    /**
     * Check if the PHP manager is installed.
     *
     * @return boolean
     */
    abstract public function isInstalled();

    /**
     * Return the managed PHP versions.
     *
     * @return array
     */
    abstract public function getManagedVersions();

    /**
     * Switch back to the default PHP version.
     *
     * @throws Stolt\Composer\PhpManager\Exceptions\SwitchBackToDefaultPhpVersionFailed
     * @return boolean
     */
    abstract public function switchBackToDefaultPhpVersion();
}
