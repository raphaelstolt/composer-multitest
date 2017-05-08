# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [v1.1.0] - 2016-11-03
#### Added
- New prerequisite that the tests or specs have to be run against all PHP versions defined in the Travis CI configuration. Can be disabled via the `--skip-missing-versions` option. Closes [#2](https://github.com/raphaelstolt/composer-multitest/issues/2).

## [v1.0.2] - 2016-11-03
#### Fixed
- Align output for single version runs.

## [v1.0.1] - 2016-10-24
#### Fixed
- Only switch back to default PHP version when necessary. Closes [#1](https://github.com/raphaelstolt/composer-multitest/issues/1).

## v1.0.0 - 2016-10-20
- Initial release.

[Unreleased]: https://github.com/raphaelstolt/composer-multitest/compare/v1.1.0...HEAD
[v1.1.0]: https://github.com/raphaelstolt/composer-multitest/compare/v1.0.2...v1.1.0
[v1.0.2]: https://github.com/raphaelstolt/composer-multitest/compare/v1.0.1...v1.0.2
[v1.0.1]: https://github.com/raphaelstolt/composer-multitest/compare/v1.0.0...v1.0.1
