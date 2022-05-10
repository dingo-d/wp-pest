<?php

/**
 * A test helper functions
 *
 * @package MadeByDenis\WpPestIntegrationTestSetup
 *
 * @since 1.0.0
 */

namespace MadeByDenis\WpPestIntegrationTestSetup\Tests;

use Brain\Monkey\Functions;
use FilesystemIterator;
use MadeByDenis\WpPestIntegrationTestSetup\Command\InitCommand;
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

/**
 * Used for setting up file_get_contents stubs
 *
 * @since 1.0.0
 *
 * @return void
 */
function prepareFileStubs(): void
{
	$ds = DIRECTORY_SEPARATOR;
	$versions = file_get_contents(dirname(__FILE__) . $ds . 'stubs' . $ds . 'stable-check.json');
	$zip = file_get_contents(dirname(__FILE__) . $ds . 'stubs' . $ds . 'wordpress-develop-5.9.3.zip');

	// Mock file get contents. So that we don't really call the API.
	Functions\stubs([
		'file_get_contents' => function (string $filename) use ($versions, $zip) {
			switch (true) {
				case strpos($filename, InitCommand::WP_GH_TAG_URL) !== false:
					return $zip;
				case strpos($filename, InitCommand::WP_API_TAGS) !== false:
					return $versions;
				default:
					return file_get_contents($filename);
			}
		}
	]);
}
