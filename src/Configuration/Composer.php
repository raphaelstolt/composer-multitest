<?php

namespace Stolt\Composer\Configuration;

use Stolt\Composer\Configuration\Composer\Exceptions\ScriptNotResolvable;
use Stolt\Composer\Configuration\Exceptions\Blank;
use Stolt\Composer\Configuration\Exceptions\NonExistent;
use Symfony\Component\Process\Process;

class Composer
{
    const CONFIGURATION = 'composer.json';

    /**
     * @var array
     */
    private $expectedScriptNames = ['test', 'spec'];

    /**
     * Get the configured test or spec Composer script.
     *
     * @throws Stolt\Composer\Configuration\Exceptions\Blank
     * @throws Stolt\Composer\Configuration\Exceptions\NonExistent
     * @throws Stolt\Composer\Configuration\Composer\Exceptions\ScriptNotResolvable
     * @return Symfony\Component\Process\Process
     */
    public function getTestOrSpecComposerScript()
    {
        $composerConfiguration = getcwd()
            . DIRECTORY_SEPARATOR
            . self::CONFIGURATION;

        if (!file_exists(self::CONFIGURATION)) {
            $message = "Couldn't find a " . self::CONFIGURATION . ".";
            throw new NonExistent($message);
        }

        $configuration = file_get_contents($composerConfiguration);

        if (trim($configuration) === '') {
            $message = 'The ' . self::CONFIGURATION . ' is empty.';
            throw new Blank($message);
        }

        $composerConfiguration = json_decode($configuration, true);

        if (isset($composerConfiguration['scripts'])) {
            foreach ($composerConfiguration['scripts'] as $name => $script) {
                if (strstr($name, 'test') && substr($name, -4) === 'test') {
                    return new Process('composer ' . $name);
                }
                if (strstr($name, 'spec') && substr($name, -4) === 'spec') {
                    return new Process('composer ' . $name);
                }
            }

            $message = 'Unable to resolve test or spec Composer script.';
            throw new ScriptNotResolvable($message);
        }

        $message = 'There a no Composer scripts defined.';
        throw new ScriptNotResolvable($message);
    }
}
