<?php

namespace Stolt\Composer\PhpManager;

use Composer\IO\IOInterface;
use Stolt\Composer\PhpManager;
use Stolt\Composer\PhpManager\Exceptions\DefaultVersionNotResolvable;
use Stolt\Composer\PhpManager\Exceptions\SwitchBackToDefaultPhpVersionFailed;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PhpBrew extends PhpManager
{
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

        $process = (isset($this->process)) ? $this->process : new Process('phpbrew list');
        $process->mustRun();
        $output = $process->getOutput();
        $versions = explode("\n", $output);
        $managedVersions = [];

        foreach ($versions as $version) {
            $version = trim($version);
            if ($version !== '') {
                if (strstr($version, '*')) {
                    $version = str_replace('* ', '', $version);
                    $this->defaultPhpVersion =  $version;
                }
                $managedVersions[] = $version;
            }
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
            $process = (isset($this->process)) ? $this->process : new Process('phpbrew');
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
                $process = (isset($this->process)) ? $this->process : new Process('phpbrew use ' . $runnableVersion);
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
     * {@inheritDoc}
     */
    public function switchBackToDefaultPhpVersion()
    {
        try {
            $process = (isset($this->process)) ? $this->process : new Process('phpbrew use ' . $this->getDefaultPhpVersion());
            $process->mustRun();

            return true;
        } catch (ProcessFailedException $pfe) {
            throw new SwitchBackToDefaultPhpVersionFailed;
        }
    }
}
