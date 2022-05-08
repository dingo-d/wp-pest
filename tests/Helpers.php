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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Mockery shorthand
 *
 * @param string $class Class name to mock.
 *
 * @return \Mockery\MockInterface
 * @since 1.0.0
 *
 */
function mock(string $class): MockInterface
{
	return Mockery::mock($class);
}

/**
 * Used for cleaning out the output directory created after every test
 *
 * @param string $dir Directory to remove.
 *
 * @return void
 * @since 1.0.0
 *
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
 * @return void
 * @since 1.0.0
 *
 */
function prepareFileStubs(): void
{
	$ds = DIRECTORY_SEPARATOR;
	$versions = file_get_contents(dirname(__FILE__) . $ds . 'stubs' . $ds . 'stable-check.json');
	$zipPath = dirname(__FILE__) . $ds . 'stubs' . $ds . 'hello.zip';
	$zip = file_get_contents($zipPath);

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
		},
		'fopen' => function ($url, $mode) use ($zipPath) {
			switch (true) {
				case strpos($url, InitCommand::WP_GH_TAG_URL) !== false:
					return fopen($zipPath, $mode);
				default:
					return fopen($url, $mode);
			}
		},
	]);
}
