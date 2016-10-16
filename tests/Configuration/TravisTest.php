<?php
namespace Stolt\Composer\Tests\Configuration\Travis;

use Stolt\Composer\Configuration\Exceptions\Blank;
use Stolt\Composer\Configuration\Exceptions\NonExistent;
use Stolt\Composer\Configuration\Travis;
use Stolt\Composer\Configuration\Travis\Exceptions\ConfigurationNotParseable;
use Stolt\Composer\Configuration\Travis\Exceptions\VersionsNotResolvable;
use Stolt\Composer\Tests\TestCase;

class TravisTest extends TestCase
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
    public function nonExistingTravisConfigurationThrowsAnException()
    {
        $this->expectException(NonExistent::class);
        $this->expectExceptionMessage("Couldn't find a .travis.yml.");

        (new Travis())->getPhpVersions();
    }

    /**
     * @test
     */
    public function emptyTravisConfigurationThrowsAnException()
    {
        $travisConfiguration = <<<CONTENT

CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $this->expectException(Blank::class);
        $this->expectExceptionMessage('The .travis.yml is empty.');

        (new Travis())->getPhpVersions();
    }

    /**
     * @test
     */
    public function undefinedVersionsThrowsAnException()
    {
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

        $this->expectException(VersionsNotResolvable::class);
        $this->expectExceptionMessage('Unable to resolve versions.');

        (new Travis())->getPhpVersions();
    }

    /**
     * @test
     */
    public function invalidYamlThrowsAnException()
    {
        $travisConfiguration = <<<CONTENT
{
    "type": "library"
}
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $this->expectException(ConfigurationNotParseable::class);
        $this->expectExceptionMessage('Unable to parse .travis.yml.');

        (new Travis())->getPhpVersions();
    }

    /**
     * @test
     */
    public function versionsAreFoundInMatrixInclude()
    {
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

        $this->assertTravisConfigurationFileExists();

        $expectedVersions = ['7.1.0', '7.0.0', '5.6.0'];

        $this->assertEquals(
            $expectedVersions,
            (new Travis())->getPhpVersions()
        );
    }

    /**
     * @test
     */
    public function versionsAreFoundInMinimalisticDefintion()
    {
        $travisConfiguration = <<<CONTENT
language: php
php:
  - '5.4'
  - '5.5'
  - '5.6'
  - '7.0'
  - hhvm
  - nightly

env:
  global:
    - EXCLUDEGROUP=travis-ci-exclude
    - DISABLE_XDEBUG=true

git:
  depth: 2

cache:
  directories:
    - \$HOME/.composer/cache
    - \$HOME/.php-cs-fixer

notifications:
  email: false

before_script:
  - if [[ \$DISABLE_XDEBUG = true ]]; then
      phpenv config-rm xdebug.ini;
    fi
  - travis_retry composer self-update
  - travis_retry composer install --no-interaction
  - travis_retry composer dump-autoload --optimize

script:
  # Use custom script to avoid the risk of terminating the build process
  - ./bin/travis/fail-non-feature-topic-branch-pull-request
  # Verify application version and Git tag match on tagged builds
  - if [[ ! -z "\$TRAVIS_TAG" ]]; then
      composer lpv:application-version-guard;
    fi
  # Verify coding standard compliance only once
  - if [[ \$LINT = true ]]; then
      composer lpv:cs-lint;
    fi
  - if [[ \$EXCLUDEGROUP = travis-ci-exclude ]]; then
      composer lpv:test -- --exclude-group travis-ci-exclude;
    fi
  - if [[ \$EXCLUDEGROUP = travis-ci-exclude-56 ]]; then
      composer lpv:test -- --exclude-group travis-ci-exclude-56;
    fi
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $expectedVersions = ['7.0.0', '5.6.0', '5.5.0', '5.4.0'];

        $this->assertEquals(
            $expectedVersions,
            (new Travis())->getPhpVersions()
        );
    }

    /**
     * @test
     */
    public function versionsAreFoundInCompleteTravisConfiguration()
    {
        $travisConfiguration = <<<CONTENT
language: php

env:
  global:
    - EXCLUDEGROUP=travis-ci-exclude
    - DISABLE_XDEBUG=true

git:
  depth: 2

matrix:
  include:
    - php: hhvm
      env: DISABLE_XDEBUG=false
    - php: nightly
      env: DISABLE_XDEBUG=false
    - php: 7.1
      env: DISABLE_XDEBUG=false LINT=true
    - php: 7.0.5
    - php: 5.6
      env: EXCLUDEGROUP=travis-ci-exclude-56

  fast_finish: true
  allow_failures:
    - php: nightly
    - php: hhvm

cache:
  directories:
    - \$HOME/.composer/cache
    - \$HOME/.php-cs-fixer

notifications:
  email: false

before_script:
  - if [[ \$DISABLE_XDEBUG = true ]]; then
      phpenv config-rm xdebug.ini;
    fi
  - travis_retry composer self-update
  - travis_retry composer install --no-interaction
  - travis_retry composer dump-autoload --optimize

script:
  # Use custom script to avoid the risk of terminating the build process
  - ./bin/travis/fail-non-feature-topic-branch-pull-request
  # Verify application version and Git tag match on tagged builds
  - if [[ ! -z "\$TRAVIS_TAG" ]]; then
      composer lpv:application-version-guard;
    fi
  # Verify coding standard compliance only once
  - if [[ \$LINT = true ]]; then
      composer lpv:cs-lint;
    fi
  - if [[ \$EXCLUDEGROUP = travis-ci-exclude ]]; then
      composer lpv:test -- --exclude-group travis-ci-exclude;
    fi
  - if [[ \$EXCLUDEGROUP = travis-ci-exclude-56 ]]; then
      composer lpv:test -- --exclude-group travis-ci-exclude-56;
    fi
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $expectedVersions = ['7.1.0', '7.0.5', '5.6.0'];

        $this->assertEquals(
            $expectedVersions,
            (new Travis())->getPhpVersions()
        );
    }
}
