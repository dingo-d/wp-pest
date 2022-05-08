<?php

namespace MadeByDenis\WpPestIntegrationTestSetup\Tests\Unit\Command;

use Brain\Monkey;
use Brain\Monkey\Functions;
use MadeByDenis\WpPestIntegrationTestSetup\Command\InitCommand;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Console\Test\TestCommand;

use function MadeByDenis\WpPestIntegrationTestSetup\Tests\mock;
use function MadeByDenis\WpPestIntegrationTestSetup\Tests\deleteOutputDir;

beforeEach(function () {
	Monkey\setUp();

	$this->outputDir = dirname(__FILE__, 3) . DIRECTORY_SEPARATOR . 'output';

	$this->command = new InitCommand($this->outputDir, new Filesystem());
});

afterEach(function () {
	Monkey\tearDown();

	// Clean up the output dir.
	deleteOutputDir();
});

it('checks that the command name is correct', function () {
	expect($this->command::getDefaultName())->toBe('setup');
});

it('checks that the command doesn\'t have default description', function () {
	expect($this->command::getDefaultDescription())->toBeNull();
});

it('checks that the command throws error when argument isn\'t specified', function () {
	TestCommand::for($this->command)
		->expectException('RuntimeException')
		->execute();
});

it('checks that the command throws error when argument isn\'t correct', function () {

	TestCommand::for($this->command)
		->addArgument('bla')
		->execute()
		->assertStatusCode(1)
		->assertOutputContains("The argument must either be 'theme' or 'plugin', bla provided");
});

it('checks that the command throws error when plugin slug isn\'t provided for plugin set up', function () {

	TestCommand::for($this->command)
		->addArgument('plugin')
		->execute()
		->assertStatusCode(1)
		->assertOutputContains("You need to provide the plugin slug if you want to set up plugin integration test suite.");
});

it('checks that the command creates folder with correct templates for a plugin', function () {

	TestCommand::for($this->command)
		->addArgument('plugin')
		->addOption('plugin-slug', 'fake-plugin')
		->execute()
		->assertSuccessful();

	// Check if the files were created, as intended.
	expect($this->outputDir)->toBeDirectory();

	$bootstrapFilePath = $this->outputDir . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'bootstrap.php';

	expect($this->outputDir . DIRECTORY_SEPARATOR . 'phpunit.xml')->toBeReadableFile();
	expect($bootstrapFilePath)->toBeReadableFile();

	// Ensure the contents are correct.
	$bootstrapContents = file_get_contents($bootstrapFilePath);

	expect($bootstrapContents)->toContain('%%%PLUGIN-SLUG%%%.php');
});


it('checks that the command creates folder with correct templates for a theme', function () {

	TestCommand::for($this->command)
		->addArgument('theme')
		->execute()
		->assertSuccessful();

	// Check if the files were created, as intended.
	expect($this->outputDir)->toBeDirectory();

	$bootstrapFilePath = $this->outputDir . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'bootstrap.php';

	expect($this->outputDir . DIRECTORY_SEPARATOR . 'phpunit.xml')->toBeReadableFile();
	expect($bootstrapFilePath)->toBeReadableFile();

	// Ensure the contents are correct.
	$bootstrapContents = file_get_contents($bootstrapFilePath);

	expect($bootstrapContents)->toContain('\tests_add_filter(\'muplugins_loaded\', \'_register_theme\');');
});
