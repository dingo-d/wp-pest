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
use Mockery;
use Mockery\MockInterface;
use Mockery\LegacyMockInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
		$dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'output';
	}

	if (!\is_dir($dir)) {
		return;
	}

	$iterator = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
	$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

	foreach ($files as $file) {
		if ($file->isDir()) {
			rmdir($file->getRealPath());
		} else {
			unlink($file->getRealPath());
		}
	}

	rmdir($dir);
}
