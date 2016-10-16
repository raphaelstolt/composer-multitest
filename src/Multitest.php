<?php

namespace Stolt\Composer;

use Composer\Script\Event;
use Stolt\Composer\Configuration\Composer;
use Stolt\Composer\Configuration\Composer\Exceptions\ScriptNotResolvable;
use Stolt\Composer\Configuration\Exceptions\Blank;
use Stolt\Composer\Configuration\Exceptions\NonExistent;
use Stolt\Composer\Configuration\Travis;
use Stolt\Composer\Configuration\Travis\Exceptions\ConfigurationNotParseable;
use Stolt\Composer\Configuration\Travis\Exceptions\VersionsNotResolvable;
use Stolt\Composer\PhpManager\PHPBrew;
use Stolt\Composer\PhpManager\Phpenv;

class Multitest
{
    /**
     * Run a Composer script against multiple versions managed by
     * phpenv or PHPBrew.
     *
     * @param  Composer\Script\Event     $event
     * @param  Stolt\Composer\PhpManager $pyenvManager
     * @param  Stolt\Composer\PhpManager $phpbrewManager
     *
     * @return boolean
     */
    public static function run(Event $event, PhpManager $pyenvManager = null, PhpManager $phpbrewManager = null)
    {
        $io = $event->getIO();

        try {
            $manager = (isset($pyenvManager)) ? $pyenvManager : new Phpenv($io);

            if ($manager->isInstalled() === false) {
                $manager = (isset($phpbrewManager)) ? $phpbrewManager : new PHPBrew($io);

                if ($manager->isInstalled() === false) {
                    $error = 'Neither phpenv nor PHPBrew installed.';
                    $io->writeError($error);
                    return false;
                }
            }
            $composerScript = (new Composer())->getTestOrSpecComposerScript();
            $phpVersions = (new Travis())->getPhpVersions();

            return $manager->multiRun(
                $composerScript,
                $manager->getRunnableVersions($phpVersions)
            );
        } catch (Blank $b) {
            $io->writeError($b->getMessage());
            return false;
        } catch (NonExistent $ne) {
            $io->writeError($ne->getMessage());
            return false;
        } catch (ConfigurationNotParseable $snr) {
            $io->writeError($snr->getMessage());
            return false;
        } catch (ScriptNotResolvable $snr) {
            $io->writeError($snr->getMessage());
            return false;
        } catch (VersionsNotResolvable $vnr) {
            $io->writeError($vnr->getMessage());
            return false;
        }
    }
}
