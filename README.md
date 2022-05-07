# WordPress integration tests with PestPHP

A package that will add WordPress integration test suite with [Pest PHP](https://pestphp.com/) testing framework.

## Why bother?

When somebody mentions automated testing, WordPress doesn't really come to mind, right? Hopefully, this package will help break the stigma of testing in WordPress.

This package will enable you to get up and running in no time with easy and readable testing setup using Pest PHP framework.

## Requirements

1. PHP > 7.4
2. Composer

This package will only work with Composer, I don't plan on supporting alternative ways of installations.

## Setup

In your project run

```bash
composer require dingo-d/wp-pest-integration-test-setup --dev
```

After that you can run the following command

```bash
vendor/bin/wp-pest theme
```

This will set up the `tests` folder, download the latest version of [WordPress develop](https://github.com/WordPress/wordpress-develop/) repo and place it in `wp` folder. It will also set up your integration test suite with an example that you can run in your theme.

There are other options you can choose from by typing

```bash
vendor/bin/wp-pest --help
```

```bash
Description:
  Sets up the test suites.

Usage:
  setup [options] [--] <project-type>

Arguments:
  project-type                   Select whether you want to setup tests for theme or a plugin. Can be "theme" or "plugin"

Options:
      --wp-version[=WP-VERSION]  Pass the version of the WordPress you want to test on, if you don't pass the version, the latest will be used. [default: "latest"]
      --skip-wp                  If you pass this argument, only the Pest unit test suite will be created.
  -h, --help                     Display help for the given command. When no command is given display help for the list command
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi|--no-ansi           Force (or disable --no-ansi) ANSI output
  -n, --no-interaction           Do not ask any interactive question
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  This command helps you set up WordPress integration and unit test suite.
```

You can set up only unit test suite, but this library is more aimed at integration testing.

## Test example

## Questions

### Why such a high PHP version? What if I need to test my theme/plugin on other PHP versions?

Underlying aim of this package (besides getting WordPress developers more acquainted to testing) is to urge the developers to update their projects, and use more modern PHP features. 
While WordPress supports PHP 5.6, it's no longer even supported with security patches (at the time of writing this PHP 7.3 is in the [EOL phase](https://www.php.net/supported-versions.php)).

The WordPress community needs to move on, and if this package will help somebody to update their servers and PHP version I'll call that a success.

## To Do: 

- add command to initialize test setup - we can provide WP version there, etc.
- add commands (symfony) that will copy over necessary template files and setup everything
- test everything!
- Expand the test suite to include PHP 8.2 as nightly (allowed failure)
