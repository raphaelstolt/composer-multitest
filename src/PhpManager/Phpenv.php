<?php

namespace Stolt\Composer\PhpManager;

use Composer\IO\IOInterface;
use Stolt\Composer\PhpManager;
use Stolt\Composer\PhpManager\Exceptions\DefaultVersionNotResolvable;
use Stolt\Composer\PhpManager\Exceptions\SwitchBackToDefaultPhpVersionFailed;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Phpenv extends PhpManager
{
    const PHPENV_VERSION_FILE = '.phpenv-version';

    /**
     * {@inheritDoc}
     */
    public function __construct(IOInterface $io, Process $process = null)
    {
        parent::__construct($io, $process);
    }

    /**
     * {@inheritDoc}
     */
    public function getManagedVersions()
    {
        if ($this->managedVersions !== []) {
            return $this->managedVersions;
        }

        $process = (isset($this->process)) ? $this->process : new Process('phpenv versions');

        $process->mustRun();
        $output = $process->getOutput();

        $versions = explode("\n", $output);
        $managedVersions = [];

        foreach ($versions as $version) {
            $version = trim($version);
            if (strstr($version, '*')) {
                $version = str_replace('* ', '', $version);
                list($version, $void) = explode(' (', $version);
                $this->defaultPhpVersion =  $version;
            }
            $managedVersions[] = $version;
        }

        rsort($managedVersions);

        $this->managedVersions = $managedVersions;

        return $managedVersions;
    }

    /**
     * {@inheritDoc}
     */
    public function isInstalled()
    {
        try {
            $process = (isset($this->process)) ? $this->process : new Process('phpenv');
            $process->mustRun();

            return true;
        } catch (ProcessFailedException $pfe) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function multiRun(Process $composerScript, array $runnableVersions)
    {
        if (count($runnableVersions) <= 1) {
            return $this->singleRun($composerScript);
        }

        $composerMessage = ">> Running '" . $composerScript->getCommandLine() . "'.";
        $lastRunnableVersion = array_pop($runnableVersions);

        foreach ($runnableVersions as $runnableVersion) {
            try {
                if ($this->process === null) {
                    if ($this->hasLocalPhpenvVersionFile()) {
                        $process = new Process(
                            'phpenv local ' . $runnableVersion
                        );
                    } else {
                        $process = new Process(
                            'phpenv global ' . $runnableVersion
                        );
                    }
                } else {
                    $process = $this->process;
                }

                $process->mustRun();

                $this->io->write(">> Switching to '$runnableVersion'.");
                $this->io->write($composerMessage);

                $composerScript->enableOutput();
                $composerScript->mustRun();

                $this->io->write($composerScript->getIncrementalOutput());
            } catch (ProcessFailedException $pfe) {
                $this->io->write($composerScript->getOutput());
                $this->io->write(">> Running '" . $composerScript->getCommandLine() . "' failed.");

                if ($this->isSwitchBackToDefaultPhpVersionRequired($lastRunnableVersion)) {
                    $this->switchBackToDefaultPhpVersion();
                    $defaultPhpVersion = $this->getDefaultPhpVersion();
                    $this->io->write(">> Switching back to '$defaultPhpVersion'.");
                }

                return false;
            }
        }

        if ($this->isSwitchBackToDefaultPhpVersionRequired($lastRunnableVersion)) {
            $this->switchBackToDefaultPhpVersion();
            $defaultPhpVersion = $this->getDefaultPhpVersion();
            $this->io->write(">> Switching back to '$defaultPhpVersion'.");
        }

        return true;
    }

    /**
     * Check if a local .phpenv-version file is present.
     *
     * @return boolean
     */
    private function hasLocalPhpenvVersionFile()
    {
        $localPhpenvVersionFile = getcwd()
            . DIRECTORY_SEPARATOR
            . self::PHPENV_VERSION_FILE;

        if (file_exists($localPhpenvVersionFile)
            && trim(file_get_contents($localPhpenvVersionFile)) !== ''
        ) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function switchBackToDefaultPhpVersion()
    {
        if ($this->process === null) {
            if ($this->hasLocalPhpenvVersionFile()) {
                $process = new Process(
                    'phpenv local ' . $this->getDefaultPhpVersion()
                );
            } else {
                $process = new Process(
                    'phpenv global ' . $this->getDefaultPhpVersion()
                );
            }
        } else {
            $process = $this->process;
        }

        try {
            $process->mustRun();

            return true;
        } catch (ProcessFailedException $pfe) {
            throw new SwitchBackToDefaultPhpVersionFailed;
        }
    }
}
