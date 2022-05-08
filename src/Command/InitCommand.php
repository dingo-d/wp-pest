<?php

/**
 * WordPress integration tests with PestPHP.
 *
 * @package MadeByDenis\WpPestIntegrationTestSetup
 * @link    https://github.com/dingo-d/wp-pest-integration-test-setup
 * @license https://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace MadeByDenis\WpPestIntegrationTestSetup\Command;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

/**
 * Init command that will set up the WordPress integration suite
 *
 * @package MadeByDenis\WpPestIntegrationTestSetup
 *
 * @since 1.0.0
 */
class InitCommand extends Command
{
	/**
	 * WordPress version option string
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const WP_VERSION = 'wp-version';

	/**
	 * Project type option string
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const PROJECT_TYPE = 'project-type';

	/**
	 * Plugin slug option string
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const PLUGIN_SLUG = 'plugin-slug';

	/**
	 * WordPress API is odd.
	 *
	 * HTTP URL will serve a single instance of the latest offer, whereas HTTPS will serve multiple.
	 * We only need the one to see what is the latest version of WP.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const WP_API_URL = 'http://api.wordpress.org/core/version-check/1.7/';

	/**
	 * WordPress GitHub tag zip url
	 *
	 * At the end there needs to go the version followed by .zip in order to fetch the contents.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const WP_GH_TAG_URL = 'https://github.com/WordPress/wordpress-develop/archive/refs/tags/';

	/**
	 * WordPress version tags
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const WP_API_TAGS = 'https://api.wordpress.org/core/stable-check/1.0/';

	/**
	 * Root path of the project
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $rootPath;

	/**
	 * Filesystem dependency
	 *
	 * @since 1.0.0
	 *
	 * @var Filesystem
	 */
	private Filesystem $filesystem;

	/**
	 * Command name property
	 *
	 * @since 1.0.0
	 *
	 * @var string Command name.
	 */
	protected static $defaultName = 'setup';


	public function __construct(string $rootPath, Filesystem $filesystem)
	{
		$this->rootPath = $rootPath;
		$this->filesystem = $filesystem;

		parent::__construct();
	}

	/**
	 * Configures the current command
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function configure(): void
	{
		$this
			->setDescription('Sets up the test suites.')
			->setHelp('This command helps you set up WordPress integration and unit test suite.')
			->addArgument(
				self::PROJECT_TYPE,
				InputArgument::REQUIRED,
				'Select whether you want to setup tests for theme or a plugin. Can be "theme" or "plugin"'
			)
			->addOption(
				self::WP_VERSION,
				null,
				InputOption::VALUE_OPTIONAL,
				'Pass the version of the WordPress you want to test on.',
				'latest'
			)
			->addOption(
				self::PLUGIN_SLUG,
				null,
				InputOption::VALUE_OPTIONAL,
				'If you are setting the plugin tests provide the plugin slug.'
			);
	}

	/**
	 * Executes the current command
	 *
	 * @param InputInterface $input Command input values.
	 * @param OutputInterface $output Command output.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);

		$projectType = $input->getArgument(self::PROJECT_TYPE);
		$pluginSlug = $input->getOption(self::PLUGIN_SLUG);

		if (!in_array($projectType, ['theme', 'plugin'], true)) {
			$io->error("The argument must either be 'theme' or 'plugin', $projectType provided.");

			return Command::FAILURE;
		}

		if ($projectType === 'plugin') {
			if (empty($pluginSlug)) {
				$io->error('You need to provide the plugin slug if you want to set up plugin integration test suite.');

				return Command::FAILURE;
			}
		}

		$wpVersion = $input->getOption(self::WP_VERSION);

		$io->info('Attempting to create tests folder');

		$testsDir = $this->rootPath . DIRECTORY_SEPARATOR . 'tests';
		$wpDir = $this->rootPath . DIRECTORY_SEPARATOR . 'wp';

		// Check if folder exists, and create it if it doesn't.
		try {
			if (!$this->filesystem->exists($testsDir)) {
				$this->setUpBasicTestFiles($testsDir, $projectType);

				$io->success('Folder and files created successfully.');

				return Command::SUCCESS;
			} else {
				$io->info('tests/ directory already exits. Moving on.');
			}
		} catch (IOException $exception) {
			$io->error("Error happened when creating files and folders at {$exception->getPath()}. \
			Error message: {$exception->getMessage()}");

			return Command::FAILURE;
		}

		if ($this->filesystem->exists($wpDir)) {
			$io->info('WordPress core and test files already downloaded. No need to run this command again.');

			return Command::FAILURE;
		}

		if ($wpVersion === 'latest') {
			// Find the latest tag and download that one.
			$io->info('Downloading the latest WordPress version');

			try {
				$this->downloadWPCoreAndTests('latest');
			} catch (InvalidArgumentException $e) {
				$io->error($e->getMessage());

				return Command::FAILURE;
			}

			$io->success('WordPress downloaded successfully');
			return Command::SUCCESS;
		}

		$io->info("Downloading WordPress version $wpVersion");
		try {
			$this->downloadWPCoreAndTests($wpVersion);
		} catch (InvalidArgumentException $e) {
			$io->error($e->getMessage());

			return Command::FAILURE;
		}

		$io->success('WordPress downloaded successfully');

		// Copy the DB files in a correct place.


		$io->comment('Make sure you autoload your tests in composer.json, otherwise they probably won\'t work.');
		return Command::SUCCESS;
	}

	/**
	 * Sets up the test files
	 *
	 * This method will:
	 *  - Create a test folder in your project root
	 *  - Copy phpunit.xml.tmpl with the database details
	 *  - Set up Integration/Unit test examples
	 *
	 * @param string $testsPath Root path of the project.
	 * @param string $projectType Type of project to set up. Default is theme.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 * @throws IOException Throws exception in case something fails with fs operations.
	 */
	private function setUpBasicTestFiles(string $testsPath, string $projectType = 'theme'): void
	{
		$ds = DIRECTORY_SEPARATOR;

		$this->filesystem->mkdir($testsPath, 0755);

		// Copy phpunit.xml.tmpl from templates folder.
		$templatesFolder = dirname(__FILE__, 3) . $ds . 'templates';

		$bootstrap = ($projectType === 'theme') ? 'bootstrap-theme.php.tmpl' : 'bootstrap-plugin.php.tmpl';

		$this->filesystem->copy($templatesFolder . $ds . 'phpunit.xml.tmpl', $this->rootPath . $ds . 'phpunit.xml');
		$this->filesystem->copy($templatesFolder . $ds . $bootstrap, $testsPath . $ds . 'bootstrap.php');
		$this->filesystem->copy($templatesFolder . $ds . 'ExampleUnitTest.php.tmpl', $testsPath . $ds . 'Unit' . $ds . 'ExampleTest.php');
		$this->filesystem->copy($templatesFolder . $ds . 'ExampleIntegrationTest.php.tmpl', $testsPath . $ds . 'Integration' . $ds . 'ExampleTest.php');
		$this->filesystem->copy($templatesFolder . $ds . 'Pest.php.tmpl', $testsPath . $ds . 'Pest.php');
	}

	/**
	 * Downloads the WordPress core and test files and copies them to correct folder
	 *
	 * @param string $version Version to download.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 * @throws InvalidArgumentException Throws an exception if the version number is not correct.
	 */
	private function downloadWPCoreAndTests(string $version)
	{
		if (empty($version)) {
			$wpApiInfo = json_decode(file_get_contents(self::WP_API_URL), true);
			$version = $wpApiInfo['offers'][0]['current'];
		}

		if (!$this->isWPVersionValid($version)) {
			throw new InvalidArgumentException('Wrong WordPress version. Make sure the version number is correct.');
		}

		// Download a zip file, unzip it to root/wp folder and delete the .zip file.
		$value = file_get_contents(self::WP_GH_TAG_URL . $version . 'zip');

		$zip = new ZipArchive();

		if ($zip->open(str_replace('//', '/', $value)) === true) {
			$zip->extractTo($this->rootPath . DIRECTORY_SEPARATOR . 'wp');
			$zip->close();
		}
	}

	/**
	 * Checks if the WordPress version is a valid one
	 *
	 * @param string $version Version number.
	 *
	 * @return bool True if the response returns correct value. False otherwise.
	 */
	private function isWPVersionValid(string $version): bool
	{
		$versions = json_decode(file_get_contents(self::WP_API_TAGS), true);

		if (!isset($versions[$version])) {
			return false;
		};

		return true;
	}
}
