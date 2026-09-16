<?php

/**
 * A test helper functions
 *
 * @package MadeByDenis\WpPestIntegrationTestSetup
 *
 * @since 1.0.0
 */

namespace MadeByDenis\WpPestIntegrationTestSetup\Tests;

use FilesystemIterator;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use Mockery\LegacyMockInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Mockery shorthand
 *
 * @param string $class Class name to mock.
 *
 * @since 1.0.0
 *
 * @return MockInterface|LegacyMockInterface
 */
function mock(string $class)
{
	return Mockery::mock($class);
}

/**
 * Used for cleaning out the output directory created after every test
 *
 * @param string $dir Directory to remove.
 *
 * @since 1.0.0
 *
 * @return void
 */
function deleteOutputDir(string $dir = ''): void
{
	if (!$dir) {
		$dir = __DIR__ . DIRECTORY_SEPARATOR . 'output';
	}

	if (!\is_dir($dir)) {
		return;
	}

	$fs = new Filesystem();

	$fs->remove($dir);
}

/**
 * Build a Guzzle client that returns the given queued responses
 *
 * The tags endpoint is requested more than once per command run (once while
 * downloading and once while validating the version), so error-path tests queue
 * the same response twice. `http_errors` is disabled so that non-2xx responses
 * are returned to the caller instead of throwing, exercising the status guard.
 *
 * @param Response ...$responses Responses to return in order.
 *
 * @since 1.8.0
 *
 * @return Client
 */
function clientReturning(Response ...$responses): Client
{
	return new Client([
		'handler' => HandlerStack::create(new MockHandler($responses)),
		'http_errors' => false,
	]);
}
