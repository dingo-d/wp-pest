# Change Log for WordPress integration tests with PestPHP

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](https://semver.org/) and [Keep a CHANGELOG](https://keepachangelog.com/).

## [Unreleased]

_No documentation available about unreleased changes as of yet._

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
[1.1.0]: https://github.com/https://github.com/dingo-d/wp-pest-integration-test-setup/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/https://github.com/dingo-d/wp-pest-integration-test-setup/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/https://github.com/dingo-d/wp-pest-integration-test-setup/compare/cadf3ac...1.0.0
