<?php

namespace MadeByDenis\WpPestIntegrationTestSetup\Tests\Unit\Command;

use Brain\Monkey;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MadeByDenis\WpPestIntegrationTestSetup\Command\InitCommand;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Console\Test\TestCommand;

use function MadeByDenis\WpPestIntegrationTestSetup\Tests\prepareFileStubs;
use function MadeByDenis\WpPestIntegrationTestSetup\Tests\deleteOutputDir;

beforeEach(function () {
	Monkey\setUp();
	$ds = DIRECTORY_SEPARATOR;

	// Create a mock and queue two responses.
	$zipContents = file_get_contents(dirname(__FILE__, 3) . $ds . 'stubs' . $ds . 'hello.zip');

	$mock = new MockHandler([
		new Response(200, [], $zipContents),
	]);

	$handlerStack = HandlerStack::create($mock);
	$client = new Client(['handler' => $handlerStack]);

	$this->outputDir = dirname(__FILE__, 3) . $ds . 'output';
	$this->fileSystem = new Filesystem();

	$this->command = new InitCommand($this->outputDir, $this->fileSystem, $client);
});

afterEach(function () {
	Monkey\tearDown();

	// Clean up the output dir.
	deleteOutputDir();
});

it("checks that the command name is correct", function () {
	expect($this->command::getDefaultName())->toBe('setup');
});

it("checks that the command doesn't have default description", function () {
	expect($this->command::getDefaultDescription())->toBeNull();
});

it("checks that the command throws error when argument isn't specified", function () {
	TestCommand::for($this->command)
		->execute();
})->expectException('RuntimeException');

it("checks that the command throws error when argument isn't correct", function () {
	TestCommand::for($this->command)
		->addArgument('bla')
		->execute()
		->assertStatusCode(1)
		->assertOutputContains("The argument must either be 'theme' or 'plugin', bla provided");
});

it("checks that the command throws error when plugin slug isn't provided for plugin set up", function () {
	TestCommand::for($this->command)
		->addArgument('plugin')
		->execute()
		->assertStatusCode(1)
		->assertOutputContains("You need to provide the plugin slug if you want to set up plugin integration test suite.");
});

it("checks that the command throws error if the wp directory already exists", function () {
	$this->fileSystem->mkdir($this->outputDir . DIRECTORY_SEPARATOR . 'wp');

	TestCommand::for($this->command)
		->addArgument('theme')
		->execute()
		->assertStatusCode(1)
		->assertOutputContains("WordPress core and test files already downloaded. No need to run this command again.");
});

it("checks that the command throws error if the plugin slug isn't valid", function ($slugs) {
	prepareFileStubs();

	TestCommand::for($this->command)
		->addArgument('plugin')
		->addOption('plugin-slug', $slugs)
		->execute()
		->assertStatusCode(1)
		->assertOutputContains("Plugin slug must be written in lowercase, separated by a dash.");
})->with([
	"1Not",
	"noT_allowed",
	"asllaso-asdasdasd_aasdasdasd",
	"1243-234234234",
	"NO-YELLING",
	"asdlkj^asdasd",
	"olko_asdasdad",
	"IKjasopdk-asdasd",
	"./.asd.asd-asd/asd.,123445",
	"this-is-ok.zip",
]);

it("checks that the command works ok if the plugin slug is valid", function ($slugs) {
	prepareFileStubs();

	TestCommand::for($this->command)
		->addArgument('plugin')
		->addOption('plugin-slug', $slugs)
		->execute()
		->assertSuccessful();
})->with([
	'ok-name',
	'ok-even-if-multiple-dashes',
	'thisisok',
]);

it("checks that the command creates folder with correct templates for a plugin", function () {
	prepareFileStubs();

	TestCommand::for($this->command)
		->addArgument('plugin')
		->addOption('plugin-slug', 'fake-plugin')
		->execute()
		->assertSuccessful();

	// Check if the files were created, as intended.
	expect($this->outputDir)->toBeDirectory();

	$testsFolder = $this->outputDir . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR;
	$bootstrapFilePath = $testsFolder . 'bootstrap.php';

	// Check if correct files are copied over.
	expect($this->outputDir . DIRECTORY_SEPARATOR . 'phpunit.xml')->toBeReadableFile();
	expect($testsFolder . 'Pest.php')->toBeReadableFile();
	expect($testsFolder . 'Unit' . DIRECTORY_SEPARATOR . 'ExampleTest.php')->toBeReadableFile();
	expect($testsFolder . 'Integration' . DIRECTORY_SEPARATOR . 'ExampleTest.php')->toBeReadableFile();
	expect($bootstrapFilePath)->toBeReadableFile();

	// Ensure the contents of the bootstrap.php file are correct.
	$bootstrapContents = file_get_contents($bootstrapFilePath);

	expect($bootstrapContents)->toContain("tests_add_filter('muplugins_loaded', '_manually_load_plugin');");
	expect($bootstrapContents)->toContain("require dirname(dirname(__FILE__)) . '/fake-plugin.php';");

	// Check if the mock file was unzipped.
	$wpFolderPath = $this->outputDir . DIRECTORY_SEPARATOR . 'wp' . DIRECTORY_SEPARATOR . 'hello.txt';

	$zipContents = file_get_contents($wpFolderPath);
	expect($zipContents)->toContain('Hi!');
});

it("checks that the command creates folder with correct templates for a theme", function () {
	prepareFileStubs();

	TestCommand::for($this->command)
		->addArgument('theme')
		->execute()
		->assertSuccessful();

	// Check if the files were created, as intended.
	expect($this->outputDir)->toBeDirectory();

	$bootstrapFilePath = $this->outputDir . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'bootstrap.php';

	// Check if correct file is copied over.
	expect($bootstrapFilePath)->toBeReadableFile();

	// Ensure the contents are correct.
	$bootstrapContents = file_get_contents($bootstrapFilePath);

	expect($bootstrapContents)->toContain('\tests_add_filter(\'muplugins_loaded\', \'_register_theme\');');
});

it("checks that attempting to download wrong WordPress version will throw an exception", function ($versions) {
	prepareFileStubs();

	TestCommand::for($this->command)
		->addArgument('theme')
		->addOption('--wp-version', $versions)
		->execute()
		->assertStatusCode(1)
		->assertOutputContains('Wrong WordPress version. Make sure the version number is correct.');
})->with([
	'5.9.15',
	'4.2.',
	'latestt',
	'sdlfkj97 0236 ./',
]);

it("checks that attempting to download WordPress version will work", function ($versions) {
	prepareFileStubs();

	TestCommand::for($this->command)
		->addArgument('theme')
		->addOption('--wp-version', $versions)
		->execute()
		->assertSuccessful();
})->with([
	null,
	'',
	'5.4'
]);

it('checks that the database dropin is copied over correctly', function () {
	$ds = DIRECTORY_SEPARATOR;
	prepareFileStubs();

	TestCommand::for($this->command)
		->addArgument('theme')
		->execute()
		->assertSuccessful();

	// Check if the files were created, as intended.
	expect($this->outputDir . $ds . 'wp')->toBeDirectory();
	expect($this->outputDir . $ds . 'wp' . $ds . 'src' . $ds . 'wp-content' . $ds . 'db.php')->toBeReadableFile();
});
