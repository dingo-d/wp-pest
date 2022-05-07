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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

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
	 * Skip WordPress installation option string
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const SKIP = 'skip-wp';

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
	 * Command name property
	 *
	 * @since 1.0.0
	 *
	 * @var string Command name.
	 */
	protected static $defaultName = 'setup';

	public function __construct(string $rootPath)
	{
		$this->rootPath = $rootPath;

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
				'Pass the version of the WordPress you want to test on, if you don\'t pass the version, the latest will be used.',
				'latest'
			)
			->addOption(
				self::SKIP,
				null,
				InputOption::VALUE_NONE,
				'If you pass this argument, only the Pest unit test suite will be created.'
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

		$wpVersion = $input->getOption(self::WP_VERSION);
		$skipWPInstall = $input->getOption(self::SKIP);
		$projectType = $input->getArgument(self::PROJECT_TYPE);

		$filesystem = new Filesystem();

		$io->info('Creating tests folder');

		$testsDir = $this->rootPath . DIRECTORY_SEPARATOR . 'tests';

		// Only setup basic test folder, don't download WP.
		if ($skipWPInstall) {
			// Check if folder exists, and create it if it doesn't.
			try {
				if (!$filesystem->exists($testsDir)) {
					$filesystem->mkdir($testsDir, 0755);
					$io->success('Folder created successfully');

					return Command::SUCCESS;
				} else {
					$io->error('tests directory already exits!');

					return Command::FAILURE;
				}
			} catch (IOExceptionInterface $exception) {
				$io->error("Error copying directory at {$exception->getPath()}.");

				return Command::FAILURE;
			}
		}

		if ($wpVersion === 'latest') {
			// Find the latest tag and download that one.
			$io->info('Downloading the latest WordPress version');
			$wpApiInfo = json_decode(file_get_contents(self::WP_API_URL), true);
			$latestVersion = $wpApiInfo['offers'][0]['current'];





			$io->success('WP downloaded successfully');
			return Command::SUCCESS;
		}

		$io->info("Downloading WordPress version $wpVersion");



		$io->comment('Make sure you autoload your tests in composer.json, otherwise they probably won\'t work.');
		return Command::SUCCESS;
	}
}
