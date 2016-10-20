# composer-multitest
[![Build Status](https://secure.travis-ci.org/raphaelstolt/composer-multitest.png)](http://travis-ci.org/raphaelstolt/composer-multitest)
[![Version](http://img.shields.io/packagist/v/stolt/composer-multitest.svg?style=flat)](https://packagist.org/packages/stolt/composer-multitest)
![PHP Version](http://img.shields.io/badge/php-5.6+-ff69b4.svg)
[![composer.lock available](https://poser.pugx.org/stolt/composer-multitest/composerlock)](https://packagist.org/packages/stolt/composer-multitest)

`composer-multitest` is a Composer script that runs a `test` or `spec` Composer script against multiple PHP versions managed by [PHPBrew](https://github.com/phpbrew) or [phpenv](https://github.com/phpenv/phpenv). Kind of a local [Travis CI](https://travis-ci.org/).

## Assumptions
As `composer-multitest` utilizes phpenv and PHPBrew it's assumed that at least one of them is installed and manages several PHP versions. It will first look for phpenv managed versions and when this fails it will subsequently look for PHPBrew managed ones.

The versions to test against are read from the local Travis CI configuration so it's assumed that one is present. Versions present in the Travis CI configuration not having a phpenv or PHPBrew managed version will __not__ be executed.

The Composer script `composer-multitest` will run __MUST__ be named `test` or `spec` and it __can__ be defined in a Composer script namespace like `library:test|spec`.

## Installation
The Composer script should be installed as a development dependency through Composer.

``` bash
composer require --dev stolt/composer-multitest
```

## Usage
Once installed add the Composer script to the existing `composer.json` and use it afterwards via `composer multitest`.

``` json
{
    "scripts": {
        "multitest":  "Stolt\\Composer\\Multitest::run"
    },
}
```

## Example output
The follow console output shows an example multitest run against two PHP versions.
``` bash
❯ composer multitest
> Stolt\Composer\Multitest::run
>> Switching to 'php-7.0.4'.
>> Running 'composer lpv:test'.
PHPUnit 5.6.1 by Sebastian Bergmann and contributors.

................................................................. 65 / 96 ( 67%)
...............................                                   96 / 96 (100%)

Time: 591 ms, Memory: 12.25MB

OK (96 tests, 150 assertions)

>> Switching to 'php-5.6.19'.
>> Running 'composer lpv:test'.
PHPUnit 5.6.1 by Sebastian Bergmann and contributors.

................................................................. 65 / 96 ( 67%)
...............................                                   96 / 96 (100%)

Time: 591 ms, Memory: 12.25MB

OK (96 tests, 150 assertions)

>> Switching back to 'php-5.6.19'.
❯ echo $?
0
❯
```

#### Running tests
``` bash
composer cm:test
```

#### License
This Composer script is licensed under the MIT license. Please see [LICENSE](LICENSE.md) for more details.

#### Changelog
Please see [CHANGELOG](CHANGELOG.md) for more details.

#### Contributing
Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for more details.
