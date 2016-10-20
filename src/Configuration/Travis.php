<?php

namespace Stolt\Composer\Configuration;

use Stolt\Composer\Configuration\Exceptions\Blank;
use Stolt\Composer\Configuration\Exceptions\NonExistent;
use Stolt\Composer\Configuration\Travis\Exceptions\ConfigurationNotParseable;
use Stolt\Composer\Configuration\Travis\Exceptions\VersionsNotResolvable;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Travis
{
    const CONFIGURATION = '.travis.yml';

    /**
     * Versions to exclude.
     *
     * @var array
     */
    private $excludes = ['hhvm', 'nightly'];

    /**
     * Get the configured PHP versions.
     *
     * @throws Stolt\Composer\Configuration\Exceptions\Blank
     * @throws Stolt\Composer\Configuration\Exceptions\NonExistent
     * @throws Stolt\Composer\Configuration\Travis\Exceptions\ConfigurationNotParseable
     * @throws Stolt\Composer\Configuration\Travis\Exceptions\VersionsNotResolvable
     * @return array
     */
    public function getPhpVersions()
    {
        try {
            $travisConfiguration = getcwd()
                . DIRECTORY_SEPARATOR
                . self::CONFIGURATION;

            if (!file_exists(self::CONFIGURATION)) {
                $message = "Couldn't find a " . self::CONFIGURATION. ".";
                throw new NonExistent($message);
            }

            $configuration = trim(
                str_replace(';', '', file_get_contents($travisConfiguration))
            );

            if ($configuration === '') {
                $message = 'The ' . self::CONFIGURATION . ' is empty.';
                throw new Blank($message);
            }

            return $this->resolveVersions(Yaml::parse($configuration));
        } catch (ParseException $e) {
            try {
                $configuration = $this->reduceToMatrixOrBasicPhpVersionsConfiguration();

                return $this->resolveVersions(Yaml::parse($configuration));
            } catch (ParseException $e) {
                $message = 'Unable to parse ' . self::CONFIGURATION . '.';
                throw new ConfigurationNotParseable($message);
            }
        }
    }

    /**
     * Reduce the Travis CI configuration to reduce the chance of
     * YAML ParseExceptions.
     *
     * @return array
     */
    protected function reduceToMatrixOrBasicPhpVersionsConfiguration()
    {
        $travisConfiguration = getcwd()
            . DIRECTORY_SEPARATOR
            . self::CONFIGURATION;

        $file = new \SplFileObject($travisConfiguration);

        $endline = $startline = null;

        foreach ($file as $line) {
            if (preg_match('/^matrix:/s', $line)) {
                $startline = $file->key();
                continue;
            }

            if (preg_match('/^php:/s', $line) && $startline === null) {
                $startline = $file->key();
                continue;
            }

            if ($startline !== null && $endline === null) {
                if (preg_match('/^\S.*:/s', $line)) {
                    $endline = $file->key() - 1;
                    break;
                } else {
                    continue;
                }
            }
        }

        if ($endline === null) {
            $endline = count($file);
        }

        $matrix = '';

        foreach ($file as $line) {
            if ($file->key() >= $startline && $file->key() <= $endline) {
                $matrix.= $line . "\n";
            }
        }

        return $matrix;
    }

    /**
     * Resolve versions from parsed Travis CI configuration.
     *
     * @param  array $configuration The parsed Travis CI configuration.
     * @throws Stolt\Composer\Configuration\Travis\Exceptions\VersionsNotResolvable
     * @return array
     *
     */
    protected function resolveVersions(array $configuration)
    {
        $versions = [];

        if (isset($configuration['matrix']['include'])) {
            foreach ($configuration['matrix']['include'] as $include) {
                $version = (string) $include['php'];
                if (!in_array($version, $this->excludes)) {
                    $versions[] = $this->semverVersion($version);
                }
            }
        }

        if (isset($configuration['php'])) {
            foreach ($configuration['php'] as $version) {
                $version = (string) $version;
                if (!in_array($version, $this->excludes)) {
                    $versions[] = $this->semverVersion($version);
                }
            }
        }

        rsort($versions);

        if ($versions === []) {
            $message = 'Unable to resolve versions.';
            throw new VersionsNotResolvable($message);
        }

        return $versions;
    }

    /**
     * Creates a semver version number.
     *
     * @param  string $version The version to semver.
     * @return string
     */
    protected function semverVersion($version)
    {
        $versionParts = explode('.', $version);
        if (count($versionParts) === 1) {
            $version.= '.0.0';
        } elseif (count($versionParts) === 2) {
            $version.= '.0';
        }

        return $version;
    }
}
