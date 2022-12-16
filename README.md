# WordPress integration tests with PestPHP

A package that will add WordPress integration and unit test suites using [Pest PHP](https://pestphp.com/) testing framework.

## Why bother?

When somebody mentions automated testing, WordPress doesn't really come to mind, right? Hopefully, this package will help break the stigma of testing in WordPress.

This package will enable you to get up and running in no time with easy and readable testing setup using Pest PHP framework.

## Requirements

1. PHP > 7.4
2. Composer

This package will only work with Composer, I don't plan on supporting alternative ways of installations.

## Setup

In your project run:

```bash
composer require dingo-d/wp-pest-integration-test-setup --dev
```

After that you can run the following command:

```bash
vendor/bin/wp-pest setup theme
```

This will set up the `tests` folder, download the latest version of [WordPress develop](https://github.com/WordPress/wordpress-develop/) repo and place it in `wp` folder. It will also set up your integration and unit test suites with an example that you can run in your theme.

There are other options you can choose from by typing

```bash
vendor/bin/wp-pest setup --help
```

```bash
Description:
  Sets up the test suites.

Usage:
  setup [options] [--] <project-type>

Arguments:
  project-type                     Select whether you want to setup tests for theme or a plugin. Can be "theme" or "plugin"

Options:
      --wp-version[=WP-VERSION]    Pass the version of the WordPress you want to test on. [default: "latest"]
      --plugin-slug[=PLUGIN-SLUG]  If you are setting the plugin tests provide the plugin slug.
      --skip-delete                If you are running the setup tests in a CI pipeline, provide this option to skip the deletion step.
  -h, --help                       Display help for the given command. When no command is given display help for the list command
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi|--no-ansi             Force (or disable --no-ansi) ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  This command helps you set up WordPress integration and unit test suites.
```

## Under the hood

For an in-depth reasoning and explanation of how this package came to be, you can read [this article](https://madebydenis.com/wordpress-integration-tests-with-pest-php/).

Basically what's "under the hood" is downloaded [wordpress-develop](https://github.com/WordPress/wordpress-develop) repository to your project, added an in memory DB (sql lite from [aaemnnosttv/wp-sqlite-db](https://github.com/aaemnnosttv/wp-sqlite-db)), and a base test class from [Yoast/wp-test-utils](https://github.com/Yoast/wp-test-utils). All that combined allows you to run integration tests in WordPress with Pest PHP without any additional setup.

## Test example

The command will set up two examples - one for unit test, one for integration test.

Running:

```bash
vendor/bin/pest --group=unit
```

will run unit test:

```bash
   PASS  Tests\Unit\ExampleTest
  ✓ example

  Tests:  1 passed
  Time:   0.02s
```

and running:

```bash
vendor/bin/pest --group=integration
```

will run integration tests:

```bash
Installing...
Running as single site... To run multisite, use -c tests/phpunit/multisite.xml
Not running ajax tests. To execute these, use --group ajax.
Not running ms-files tests. To execute these, use --group ms-files.
Not running external-http tests. To execute these, use --group external-http.

   PASS  Tests\Integration\ExampleTest
  ✓ Rest API endpoints work
  ✓ Creating terms in category works

  Tests:  2 passed
  Time:   0.14s
```

The test suites are grouped together, and it's necessary to pass the `--group=integration` option if you want to run integration tests, because that way the bootstrap knows to load integration test specific configuration when running tests.

## Running the package in CI pipelines

If you want to run the package as a part of your continuous integration (CI) pipeline, be sure to provide the `--skip-delete` parameter when running the `wp-pest setup` command. This will skip the deletion of the `wp-content` folder (which is not important at all, especially in CI environments), and won't block the setup script.

## Questions

### Why such a high PHP version? What if I need to test my theme/plugin on other PHP versions?

Underlying aim of this package (besides getting WordPress developers more acquainted to testing) is to urge the developers to update their projects, and use more modern PHP features. 
While WordPress supports PHP 5.6, it's no longer even supported with security patches (at the time of writing this PHP 7.4 is in the [EOL phase](https://www.php.net/supported-versions.php)).

The WordPress community needs to move on, and if this package will help somebody to update their servers and PHP version I'll call that a success.

### The script is stuck on Download WordPress part, what do I do?

It's not stuck! 😂 

You're probably running this in WSL, right? For some reason, download on WSL terminal _can_ be slow.  
This is a [known issue](https://github.com/microsoft/WSL/issues/4901).

The solution is probably to disable some network adapters, as [described here](https://github.com/microsoft/WSL/issues/4901#issuecomment-1192517363) (you can also read a [tl;dr version](https://github.com/microsoft/WSL/issues/4901#issuecomment-1203857953) 😅).

### It's not working on Windows

I haven't tested it yet on native Windows installation. This is on my to do list, but not high on the priority list.

### Something is not working

Please do [open an issue](/issues) for that.

## Updates

### 1.6.0 version

I've decided to change the name to a more catchy `wp-pest`. To be honest, not sure why I haven't done this before.
The functionality stays the same.

If you've just downloaded and set up the testing from scratch on version 1.6.0, then you're all set, happy testing!  
If not, you should probably update your `phpunit.xml` file to include

```xml
<env name="WP_TESTS_DIR" value="wp/tests/phpunit"/>
```

in the `<php>` part of the configuration.

Also, update your `bootstrap.php` file according to the templates in the package. Namely you should remove the line at the end

```php
require_once dirname(__FILE__, 2) . '/wp/tests/phpunit/includes/bootstrap.php';
```

with 

```php
require_once dirname(__DIR__) . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

WPIntegration\bootstrap_it();
```

Make sure you import the namespace for the `bootstrap_it()` function at the top of the file

```php
use Yoast\WPTestUtils\WPIntegration;
```

Last, but really important, remove the `Integration` in the `Pest.php` file

```php
uses(TestCase::class)->in('Unit', 'Integration');
```

And add

```php
use Yoast\WPTestUtils\WPIntegration\TestCase;

uses(TestCase::class);
```

At the top of every integration test you have. This will ensure a correct base test class is used for integration tests.
