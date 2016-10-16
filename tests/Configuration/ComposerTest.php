<?php
namespace Stolt\Composer\Tests\Configuration\Travis;

use Stolt\Composer\Configuration\Composer;
use Stolt\Composer\Configuration\Composer\Exceptions\ScriptNotResolvable;
use Stolt\Composer\Configuration\Exceptions\Blank;
use Stolt\Composer\Configuration\Exceptions\NonExistent;
use Stolt\Composer\Tests\TestCase;
use Symfony\Component\Process\Process;

class ComposerTest extends TestCase
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
    public function nonExistingComposerConfigurationThrowsAnException()
    {
        $this->expectException(NonExistent::class);
        $this->expectExceptionMessage("Couldn't find a composer.json.");

        (new Composer())->getTestOrSpecComposerScript();
    }

    /**
     * @test
     */
    public function emptyComposerConfigurationThrowsAnException()
    {
        $composerConfiguration = <<<CONTENT

CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $this->expectException(Blank::class);
        $this->expectExceptionMessage('The composer.json is empty.');

        (new Composer())->getTestOrSpecComposerScript();
    }

    /**
     * @test
     */
    public function nonDefinedComposerScriptsThrowsAnException()
    {
        $composerConfiguration = <<<CONTENT
{
    "type": "library"
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $this->expectException(ScriptNotResolvable::class);
        $this->expectExceptionMessage('There a no Composer scripts defined.');

        (new Composer())->getTestOrSpecComposerScript();
    }

    /**
     * @test
     */
    public function missingTestOrSpecComposerScriptThrowsAnException()
    {
        $composerConfiguration = <<<CONTENT
{
    "scripts": {
        "foo": "command",
        "bar": "command"
    }
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $this->expectException(ScriptNotResolvable::class);
        $this->expectExceptionMessage('Unable to resolve test or spec Composer script.');

        (new Composer())->getTestOrSpecComposerScript();
    }

    /**
     * @test
     */
    public function specScriptIsFound()
    {
        $composerConfiguration = <<<CONTENT
{
    "scripts": {
        "spec": "phpspec run"
    }
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $expectedComposerScript = new Process('composer spec');

        $this->assertEquals(
            $expectedComposerScript,
            (new Composer())->getTestOrSpecComposerScript()
        );
    }

    /**
     * @test
     */
    public function specScriptIsFoundWhenNamespaced()
    {
        $composerConfiguration = <<<CONTENT
{
    "scripts": {
        "cpe:spec": "phpspec run"
    }
}
CONTENT;

        $this->createComposerConfigurationFile($composerConfiguration);

        $expectedComposerScript = new Process('composer cpe:spec');

        $this->assertEquals(
            $expectedComposerScript,
            (new Composer())->getTestOrSpecComposerScript()
        );
    }

    /**
     * @test
     */
    public function testScriptIsFound()
    {
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

        $expectedComposerScript = new Process('composer cpe:test');

        $this->assertEquals(
            $expectedComposerScript,
            (new Composer())->getTestOrSpecComposerScript()
        );
    }
}
