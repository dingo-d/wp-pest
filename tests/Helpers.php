<?php

/**
 * A test helper functions
 *
 * @package MadeByDenis\WpPestIntegrationTestSetup
 *
 * @since 1.0.0
 */

namespace MadeByDenis\WpPestIntegrationTestSetup\Tests;

use Mockery;
use Mockery\MockInterface;
use Mockery\LegacyMockInterface;
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
function mock(string $class): MockInterface | LegacyMockInterface
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
