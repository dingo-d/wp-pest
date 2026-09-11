# Change Log for WordPress integration tests with PestPHP

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](https://semver.org/) and [Keep a CHANGELOG](https://keepachangelog.com/).

## [Unreleased]

_No documentation available about unreleased changes as of yet._

## [1.8.0] Modernize dependencies and require PHP 8.2+

### Changed
- Raised the minimum PHP version to **8.2** (dropped support for 7.4, 8.0 and 8.1).
- Switched the SQLite drop-in to the renamed `dingo-d/wp-sqlite-db` fork (`^1.4.1`), which carries the PHP 8.2+ dynamic property deprecation fixes.
- Upgraded Symfony components to `^6.4` and migrated the command from the removed `static $defaultName` property to the `#[AsCommand]` attribute.
- Upgraded PHPStan to `^2.0` and `phpstan/phpstan-symfony` to `^2.0`, and updated `phpstan.neon` for the removed configuration keys.
- Switched Composer to stable stability (`minimum-stability: stable` + `prefer-stable`) so the lock resolves tagged releases instead of `dev` snapshots, and pinned `config.platform.php` to `8.2` for reproducible resolution.
- Bumped `guzzlehttp/guzzle` (`^7.4 || ^8.0`), `squizlabs/php_codesniffer` (`^3.13`), `dealerdirect/phpcodesniffer-composer-installer` (`^1.0`) and `zenstruck/console-test` (`^1.8`).
- CI now runs on PHP 8.2–8.4 (unit tests and static analysis), with updated `ramsey/composer-install@v3` and `codecov/codecov-action@v4`.

### Fixed
- The `test:unit`/`test:coverage` scripts and the scaffolded `phpunit.xml` now silence `E_DEPRECATED`, so Pest 1 boots and runs on PHP 8.4/8.5 (see the README FAQ for running Pest directly).

### Note
- Pest stays on `^1` (PHPUnit 9). WordPress core, `yoast/wp-test-utils` and the PHPUnit Polyfills are still capped at PHPUnit 9 (see [Trac #62004](https://core.trac.wordpress.org/ticket/62004)), so Pest 2/3 cannot boot the WordPress integration suite yet.

## [1.6.1] Add PHP 8.2 test matrix

### Added
- PHP 8.2 to the test matrix to ensure nothing is failing on PHP 8.2

## [1.6.0] Update integration tests and package name

### Changed
- Fix the integration test base class
  - Previously, both unit and integration tests used the same base test class. While this worked, integration tests weren't using the polyfilled `WP_UnitTestCase` case from the wp-test-utils package.
  
    This, in turn, meant that the tests weren't properly cleaned up, and that some usefull features, like WordPress test factories couldn't be used easily in the integration tests.

    The caveat is that, because of how Pest works, we cannot just define the `uses` statement in the `Pest.php` file, because the WordPress unit test class becomes available after the bootstrap process. For more details see the issue https://github.com/pestphp/pest/issues/623.
- Change the name of the package to `dingo-d/wp-pest`

None of this is a BC break, as your tests will work. This jsut makes it work a bit better.

## [1.5.0] Updates

### Added
- Add a `force` parameter to force download WordPress files
- Add additional tests
  - Some tests are skipped because they cannot be run in isolation, or the underlying component does type casting.

### Fixed
- Fix the issue with WP core not being included in the development version
  - By default, the `wordpress-develop` git repo doesn't contain all the WP Core files,
     so now we have to download development files for tests and the core separately.
- Remove unnecessary error checks

### Updated
- Stubs are updated to mimic WP 6.1.1 version

## [1.4.1] Fix slug validation

### Fixed
- Fixed issue with the plugin slug validation #15

## [1.4.0] Update command for CI/CD runs

### Added
- Add option to avoid the prompt at the end of the setup command
  - This caused issue in CI/CD pipelines where the setup command would just hang without confirmation. 

## [1.3.0] Tags check update

### Fixed
- Fixed the #10 issue - tags not correctly fetched from the API

### Changed
- Updated the method for getting tags, and verifying against the correct one

## [1.2.0] Update bootstrap

### Changed
- Fixed the `--group=integration` check in the bootstrap
  - Before it depended on the position of the argument, so in PhpStorm running tests
    failed because the argument wasn't in the second place. 

## [1.1.0] Update base test case

### Changed

- Add Yoast's TestCase as the base test case to both unit and integration tests in Pest.php 

## [1.0.0] Initial release

- Added the functionality for the WordPress integration tests with PestPHP package.

[Unreleased]: https://github.com/dingo-d/wp-pest-integration-test-setup/compare/main...HEAD
[1.6.0]: https://github.com/https://github.com/dingo-d/wp-pest-integration-test-setup/compare/1.6.0...1.6.1
[1.6.0]: https://github.com/https://github.com/dingo-d/wp-pest-integration-test-setup/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/https://github.com/dingo-d/wp-pest-integration-test-setup/compare/1.4.1...1.5.0
[1.4.1]: https://github.com/https://github.com/dingo-d/wp-pest-integration-test-setup/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/https://github.com/dingo-d/wp-pest-integration-test-setup/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/https://github.com/dingo-d/wp-pest-integration-test-setup/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/https://github.com/dingo-d/wp-pest-integration-test-setup/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/https://github.com/dingo-d/wp-pest-integration-test-setup/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/https://github.com/dingo-d/wp-pest-integration-test-setup/compare/cadf3ac...1.0.0
