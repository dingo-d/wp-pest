<?php

namespace MadeByDenis\WpPestIntegrationTestSetup\Tests\Unit\Command;

use Brain\Monkey;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use MadeByDenis\WpPestIntegrationTestSetup\Command\InitCommand;
use MadeByDenis\WpPestIntegrationTestSetup\Tests\Mocks\CustomMockHandler;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Console\Test\TestCommand;

use function MadeByDenis\WpPestIntegrationTestSetup\Tests\deleteOutputDir;

beforeEach(function () {
	Monkey\setUp();
	$ds = DIRECTORY_SEPARATOR;

	$mock = new CustomMockHandler();

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
		->assertStatusCode(1);
});

it("checks that the command throws error if the wp directory already exists", function () {
	$this->fileSystem->mkdir($this->outputDir . DIRECTORY_SEPARATOR . 'wp');

	TestCommand::for($this->command)
		->addArgument('theme')
		->execute()
		->assertStatusCode(1);
});

it("checks that the command throws error if the plugin slug isn't a string", function ($slugs) {
	TestCommand::for($this->command)
		->addArgument('plugin')
		->addOption('plugin-slug', $slugs)
		->execute()
		->assertStatusCode(1)
		->assertOutputContains("Plugin slug must be a string.");
})->with([
	false, // null.
	true, // "1".
	new \stdClass(), // "1".
	[], // null.
	[1, 2, 3, 'dog'], // "1".
	null, // "1".
	// fn($a) => 3 + $a, // null. Errors out on the test. Cannot pass this as an argument on the CLI anyhow (I think).
])->skip(
	true,
	'getOption will cast these, and some will pass the test, some not. In any case most of these cannot be passed as options on the CLI'
);

it("checks that the command throws error if the plugin slug isn't valid", function ($slugs) {
	TestCommand::for($this->command)
		->addArgument('plugin')
		->addOption('plugin-slug', $slugs)
		->execute()
		->assertStatusCode(1)
		->assertOutputContains("Plugin slug must be written in lowercase, separated by a dash.");
})->with([
	"1Notvalid", // Capital letters not allowed, numbers are ok.
	"noT_allowed",
	"NO-YELLING",
	"asdlkj^asdasd", // Character not allowed.
	"IKjasopdk-asdasd",
	"./.asd.asd-asd/asd.,123445",
	"this-is-ok.zip",
	"🤞🏼123-tes", // Emoji.
	"👍🏼"
]);


it("checks that the command throws error if the plugin slug is too short", function ($slugs) {
	TestCommand::for($this->command)
		->addArgument('plugin')
		->addOption('plugin-slug', $slugs)
		->execute()
		->assertStatusCode(1)
		->assertOutputContains("Plugin slug must be at least 5 characters long.");
})->with([
	"1",
	"ab",
	"👍",
	"te-s",
	"1e_s",
]);

it("checks that the command works ok if the plugin slug is valid", function ($slugs) {
	TestCommand::for($this->command)
		->addArgument('plugin')
		->addOption('plugin-slug', $slugs)
		->execute()
		->assertSuccessful();
})->with([
	"ok-name",
	"ok-even-if-multiple-dashes",
	"thisisok",
	"qps-s3",
	"12-best-cats-plugin",
	"plugin-90-with-number-in-slug", // Below this are legit plugin slugs found in the wp.org repo.
	"я-делюсь",
	"لينوكس-ويكى",
	"search-excel-csv",
	"cdn-manager",
	"jmbtrn",
	"★-wpsymbols-★",
	"分享图片到新浪微博",
	"印象码",
	"友言",
	"唐诗宋词chinese-poem",
	"图片签名插件",
	"多说社会化评论框",
	"开心网开放平台插件",
	"微集分插件",
	"新浪微博",
	"无觅相关文章插件",
	"日志保护",
	"海阔淘宝相关宝贝插件",
	"社交登录",
	"腾讯微博一键登录",
	"豆瓣秀-for-wordpress",
	"0-delay-late-caching-for-feeds",
	"0-errors",
	"001-prime-strategy-translate-accelerator",
	"002-ps-custom-post-type",
	"011-ps-custom-taxonomy",
	"012-ps-multi-languages",
	"dump_env",
	"dump_queries",
	"dunamys-ribbon",
	"dunstan-error-page",
	"duo-fqa",
]);

it("checks that the command creates folder with correct templates for a plugin", function () {
	$ds = DIRECTORY_SEPARATOR;

	TestCommand::for($this->command)
		->addArgument('plugin')
		->addOption('plugin-slug', 'fake-plugin')
		->execute()
		->assertSuccessful();

	// Check if the files were created, as intended.
	expect($this->outputDir)->toBeDirectory();

	$testsFolder = $this->outputDir . $ds . 'tests' . $ds;
	$bootstrapFilePath = $testsFolder . 'bootstrap.php';

	// Check if correct files are copied over.
	expect($this->outputDir . $ds . 'phpunit.xml')->toBeReadableFile();
	expect($testsFolder . 'Pest.php')->toBeReadableFile();
	expect($testsFolder . 'Unit' . $ds . 'ExampleTest.php')->toBeReadableFile();
	expect($testsFolder . 'Integration' . $ds . 'ExampleTest.php')->toBeReadableFile();
	expect($bootstrapFilePath)->toBeReadableFile();

	// Ensure the contents of the bootstrap.php file are correct.
	$bootstrapContents = file_get_contents($bootstrapFilePath);

	expect($bootstrapContents)->toContain("tests_add_filter('muplugins_loaded', '_manually_load_plugin');");
	expect($bootstrapContents)->toContain("require dirname(dirname(__FILE__)) . '/fake-plugin.php';");

	// Check if the mock file was unzipped.
	$wpFolderPath = $this->outputDir . $ds . 'wp' . $ds . 'src' . $ds . 'hello.txt';

	$zipContents = file_get_contents($wpFolderPath);
	expect($zipContents)->toContain('Hi!');
});

it("checks that the command creates folder with correct templates for a theme", function () {

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

	TestCommand::for($this->command)
		->addArgument('theme')
		->addOption('--wp-version', $versions)
		->execute()
		->assertStatusCode(1)
		->assertOutputContains('Wrong WordPress version. Make sure the version number is correct.');
})->with([
	'5.9.15',
	'5.9',
	'4.2.',
	'latestt',
	'sdlfkj97 0236 ./',
]);

it("checks that attempting to download WordPress version will work", function ($versions) {

	TestCommand::for($this->command)
		->addArgument('theme')
		->addOption('--wp-version', $versions)
		->execute()
		->assertSuccessful();
})->with([
	null,
	'',
	'4.7.3',
	'6.0.0'
]);

it('checks that the database dropin is copied over correctly', function () {
	$ds = DIRECTORY_SEPARATOR;

	TestCommand::for($this->command)
		->addArgument('theme')
		->execute()
		->assertSuccessful();

	// Check if the files were created, as intended.
	expect($this->outputDir . $ds . 'wp')->toBeDirectory();
	expect($this->outputDir . $ds . 'wp' . $ds . 'src')->toBeDirectory();
	expect($this->outputDir . $ds . 'wp' . $ds . 'src' . $ds . 'hello.txt')->toBeReadableFile();

	$helloContents = file_get_contents($this->outputDir . $ds . 'wp' . $ds . 'src' . $ds . 'hello.txt');
	expect($helloContents)->toContain('Hi!');

	expect($this->outputDir . $ds . 'wp' . $ds . 'tests')->toBeDirectory();
	expect($this->outputDir . $ds . 'wp' . $ds . 'tests' . $ds . 'phpunit')->toBeDirectory();
	expect($this->outputDir . $ds . 'wp' . $ds . 'tests' . $ds . 'phpunit' . $ds . 'test.txt')->toBeReadableFile();

	$testContents = file_get_contents($this->outputDir . $ds . 'wp' . $ds . 'tests' . $ds . 'phpunit' . $ds . 'test.txt');
	expect($testContents)->toContain('This is a test!');

	expect($this->outputDir . $ds . 'wp' . $ds . 'src' . $ds . 'wp-content' . $ds . 'db.php')->toBeReadableFile();
});

it('checks that skipping delete will work', function () {
	TestCommand::for($this->command)
		->addArgument('theme')
		->addOption('skip-delete')
		->execute()
		->assertSuccessful();
});
