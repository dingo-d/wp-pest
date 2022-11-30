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

use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use RuntimeException;
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
	 * Skip wp-content folder delete option string
	 *
	 * @since 1.4.0
	 *
	 * @var string
	 */
	private const SKIP_DELETE = 'skip-delete';

	/**
	 * Force reinstall of the WordPress directory
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	private const FORCE = 'force';

	/**
	 * WordPress GitHub tag zip url
	 *
	 * At the end there needs to go the version followed by .zip in order to fetch the contents.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public const WP_GH_TAG_URL = 'https://github.com/WordPress/wordpress-develop/archive/refs/tags/';

	/**
	 * WordPress release zip url
	 *
	 * At the end there needs to go the version followed by .zip in order to fetch the contents.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public const WP_GH_RELEASE_TAG_URL = 'https://github.com/WordPress/WordPress/archive/refs/tags/';

	/**
	 * WordPress version tags
	 *
	 * @since 1.3.0 Change the URL of the API tags to GH one.
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public const WP_API_TAGS = 'https://api.github.com/repos/WordPress/wordpress-develop/git/matching-refs/tags';

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
	 * Client instance property
	 *
	 * @since 1.0.0
	 *
	 * @var ClientInterface
	 */
	private ClientInterface $client;

	/**
	 * Command name property
	 *
	 * @since 1.0.0
	 *
	 * @var string Command name.
	 */
	protected static $defaultName = 'setup';

	/**
	 * Command class constructor
	 *
	 * @since 1.0.0
	 *
	 * @param string $rootPath Root path of the project.
	 * @param Filesystem $filesystem Symfony filesystem dependency.
	 */
	public function __construct(string $rootPath, Filesystem $filesystem, ClientInterface $client)
	{
		$this->rootPath = $rootPath;
		$this->filesystem = $filesystem;
		$this->client = $client;

		parent::__construct();
	}

	/**
	 * Configures the current command
	 *
	 * @since 1.4.0 Add option to skip deletion of the wp-content folder.
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function configure(): void
	{
		$this
			->setDescription('Sets up the test suites.')
			->setHelp('This command helps you set up WordPress integration and unit test suites.')
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
			)
			->addOption(
				self::SKIP_DELETE,
				null,
				InputOption::VALUE_NONE,
				'If you are running the setup tests in a CI pipeline, provide this option to skip the deletion step.'
			)
			->addOption(
				self::FORCE,
				null,
				InputOption::VALUE_NONE,
				'Force download of WordPress core files. This will overwrite everything in the wp folder.'
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
		$ds = DIRECTORY_SEPARATOR;

		$projectType = $input->getArgument(self::PROJECT_TYPE);
		$pluginSlug = $input->getOption(self::PLUGIN_SLUG);

		if (!in_array($projectType, ['theme', 'plugin'], true)) {
			// @phpstan-ignore-next-line
			$io->error("The argument must either be 'theme' or 'plugin', $projectType provided.");

			return Command::FAILURE;
		}

		if ($projectType === 'plugin') {
			if (!is_string($pluginSlug)) {
				$io->error('Plugin slug must be a string.');

				return Command::FAILURE;
			}

			/**
			 * Check if the plugin slug is less than 5 characters long.
			 *
			 * @link https://meta.svn.wordpress.org/sites/trunk/wordpress.org/public_html/wp-content/plugins/plugin-directory/shortcodes/class-upload-handler.php#L173
			 */
			if (strlen($pluginSlug) < 5) {
				$io->error('Plugin slug must be at least 5 characters long.');

				return Command::FAILURE;
			}

			if (!$this->checkIfPluginSlugIsValid($pluginSlug)) {
				$io->error('Plugin slug must be written in lowercase, separated by a dash.');

				return Command::FAILURE;
			}
		}

		$wpVersion = $input->getOption(self::WP_VERSION);
		$forceReinstall = $input->getOption(self::FORCE);

		$io->text('Attempting to create tests folder');

		$testsDir = $this->rootPath . $ds . 'tests';
		$wpDir = $this->rootPath . $ds . 'wp';

		// Check if folder exists, and create it if it doesn't.
		try {
			if (!$this->filesystem->exists($testsDir)) {
				$pluginSlug = $projectType === 'plugin' ? $pluginSlug : '';

				// @phpstan-ignore-next-line
				$this->setUpBasicTestFiles($testsDir, $projectType, $pluginSlug);

				$io->success('Folder and files created successfully.');
			} else {
				$io->info('tests/ directory already exits. Moving on.');
			}
		} catch (IOException $exception) {
			$io->error("Error happened when creating files and folders at {$exception->getPath()}. " .
				"Error message: {$exception->getMessage()}");

			return Command::FAILURE;
		}

		if ($this->filesystem->exists($wpDir) && !$forceReinstall) {
			$io->info('WordPress core and test files already downloaded. No need to run this command again.');

			return Command::FAILURE;
		}

		// Guard against empty string or nulls
		$wpVersion = !empty($wpVersion) ? $wpVersion : 'latest';

		if ($wpVersion === 'latest') {
			// Find the latest tag and download that one.
			$io->text('Downloading the latest WordPress version. This may take a while, grab a coffee or tea 🍵...');

			try {
				$this->downloadWPCoreAndTests('latest', $wpDir);
			} catch (Exception | GuzzleException $e) {
				$io->error($e->getMessage());

				return Command::FAILURE;
			}
		} else {
			// @phpstan-ignore-next-line
			$io->text("Downloading WordPress version $wpVersion. This may take a while, grab a coffee or tea 🍵...");

			try {
				// @phpstan-ignore-next-line
				$this->downloadWPCoreAndTests($wpVersion, $wpDir);
			} catch (Exception | GuzzleException $e) {
				$io->error($e->getMessage());

				return Command::FAILURE;
			}
		}

		$io->success('WordPress downloaded successfully.');

		// Extract will extract the file to a folder like wp/wordpress-develop-X.Y.Z
		// we need to move all files up one level.

		/**
		 * Copy the DB files in a correct place.
		 *
		 * Because the DB package is a WP drop-in, that means that the folder `wp-content/wp-sqlite-db`
		 * will be copied in the project root (kinda annoying). So we need to manually clean that folder later.
		 */
		$packageDropIn = $this->rootPath . $ds . 'wp-content' . $ds . 'wp-sqlite-db' . $ds . 'src' . $ds . 'db.php';
		$coreDropInPath = $wpDir . $ds . 'src' . $ds . 'wp-content';
		$coreDropIn = $coreDropInPath . $ds . 'db.php';

		// This is a dirty hack so that the test pass.
		if (isset($_ENV['WP_PEST_TESTING']) && $_ENV['WP_PEST_TESTING']) {
			$packageDropIn = dirname($this->rootPath, 2) . $ds . 'wp-content' . $ds . 'wp-sqlite-db' . $ds . 'src' . $ds . 'db.php';
		}

		if (!$this->filesystem->exists($coreDropInPath)) {
			$this->filesystem->mkdir($coreDropInPath);
		}

		$this->filesystem->copy($packageDropIn, $coreDropIn);

		$io->success('Database drop-in copied successfully.');

		$skipDelete = $input->getOption(self::SKIP_DELETE);

		if ($skipDelete) {
			$io->success("All done! Go and write tests 😄");

			return Command::SUCCESS;
		}

		$cleanDbPackage = $io->confirm('Do you want to clean the DB package folder?', false);

		if ($cleanDbPackage) {
			$this->filesystem->remove($this->rootPath . $ds . 'wp-content');
			$io->success('Database drop-in folder deleted successfully.');
		}

		$io->success("All done! Go and write tests 😄");

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
	 * @param string $pluginSlug Plugin slug.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 * @throws IOException Throws exception in case something fails with fs operations.
	 */
	private function setUpBasicTestFiles(string $testsPath, string $projectType = 'theme', string $pluginSlug = ''): void
	{
		$ds = DIRECTORY_SEPARATOR;

		$this->filesystem->mkdir($testsPath, 0755);

		// Copy phpunit.xml.tmpl from templates folder.
		$templatesFolder = dirname(__FILE__, 3) . $ds . 'templates';

		$bootstrap = ($projectType === 'theme') ? 'bootstrap-theme.php.tmpl' : 'bootstrap-plugin.php.tmpl';
		$bootstrapOutputPath = $testsPath . $ds . 'bootstrap.php';

		$this->filesystem->copy($templatesFolder . $ds . 'phpunit.xml.tmpl', $this->rootPath . $ds . 'phpunit.xml');
		$this->filesystem->copy($templatesFolder . $ds . $bootstrap, $bootstrapOutputPath);
		$this->filesystem->copy($templatesFolder . $ds . 'ExampleUnitTest.php.tmpl', $testsPath . $ds . 'Unit' . $ds . 'ExampleTest.php');
		$this->filesystem->copy($templatesFolder . $ds . 'ExampleIntegrationTest.php.tmpl', $testsPath . $ds . 'Integration' . $ds . 'ExampleTest.php'); // phpcs:ignore Generic.Files.LineLength.TooLong
		$this->filesystem->copy($templatesFolder . $ds . 'Pest.php.tmpl', $testsPath . $ds . 'Pest.php');

		if ($projectType == 'plugin') {
			$bootstrapContents = (string) file_get_contents($bootstrapOutputPath);
			$bootstrapContents = str_replace('%%%PLUGIN-SLUG%%%', $pluginSlug, $bootstrapContents);
			file_put_contents($bootstrapOutputPath, $bootstrapContents);
		}
	}

	/**
	 * Downloads the WordPress core and core test files and copies them to correct folder
	 *
	 * @param string $version Version to download.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException Throws an exception if the version number is not correct.
	 * @throws RuntimeException Throws an exception if the file download fails.
	 * @throws GuzzleException Throws an exception if the file download fails.
	 *
	 * @since 1.5.0 Add wp directory path parameter and delete the directory.
	 * @since 1.3.0 Change the way GH tags are fetched.
	 * @since 1.0.0
	 *
	 */
	private function downloadWPCoreAndTests(string $version, string $wpdir): void
	{
		if ($this->filesystem->exists($wpdir)) {
			$this->filesystem->remove($wpdir);
		}

		$versions = $this->getGitHubTags();

		if ($version === 'latest') {
			$version = end($versions);
		} else {
			/**
			 * Only validate if the parameter was passed.
			 *
			 * If the latest tag is used, the API will already be called, and we know that they
			 * have the correct versions (because it's the same API used to check the validity of versions).
			 */
			if (!$this->isWPVersionValid($version)) {
				throw new InvalidArgumentException('Wrong WordPress version. Make sure the version number is correct.');
			}
		}

		if (empty($version)) {
			throw new InvalidArgumentException('WordPress version is empty.');
		}

		// Download a zip file, unzip it to root/wp folder and delete the .zip file.
		$zipName = $this->rootPath . DIRECTORY_SEPARATOR . "wordpress-develop-$version.zip";

		try {
			ini_set('memory_limit', '1536M'); // Safeguard.
			$this->client->request('GET', self::WP_GH_TAG_URL . $version . '.zip', ['sink' => $zipName]);
		} catch (GuzzleException $e) {
			throw new RuntimeException('Failed opening remote file');
		}

		$zip = new ZipArchive();

		if ($zip->open($zipName)) {
			$extractSuccessful = $zip->extractTo($wpdir);

			if (!$extractSuccessful) {
				throw new RuntimeException('Failed extracting zip file');
			}

			$zip->close();
		}

		$this->filesystem->remove($zipName);

		/*
		 * Because WordPress is being WordPress, the GitHub repo of the wordpress-develop
		 * branch doesn't contain all the core files you'd get when you download WordPress,
		 * so we need to download the release version as well, and rewrite the `wp/src` core files.
		 *
		 * Also, the major versions are tagged differently, so 1.5.0 on develop is 1.5 on core.
		 */
		$coreVersion = preg_replace('/^(\d\.\d)\.0/i', '$1', $version);

		// Download a zip file, unzip it to root/wp/src folder and delete the .zip file.
		$zipName = $this->rootPath . DIRECTORY_SEPARATOR . "WordPress-$coreVersion.zip";

		try {
			$this->client->request('GET', self::WP_GH_RELEASE_TAG_URL . $coreVersion . '.zip', ['sink' => $zipName]);
		} catch (GuzzleException $e) {
			throw new RuntimeException('Failed opening remote file');
		}

		if ($zip->open($zipName)) {
			$extractSuccessful = $zip->extractTo($wpdir);

			if (!$extractSuccessful) {
				throw new RuntimeException('Failed extracting zip file');
			}

			$zip->close();
		}

		$this->filesystem->remove($zipName);

		// Loop through all the folder contents, and copy them to the wp/ folder.
		$destinationFolder = $wpdir;
		$folderToCopy = $wpdir . DIRECTORY_SEPARATOR . "wordpress-develop-$version";

		$this->filesystem->mirror($folderToCopy, $wpdir, null, ['override' => true]);
		$this->filesystem->remove($folderToCopy);

		// Loop through all the folder contents, and copy them to the wp/src folder.
		$wpCoreFolderToCopy = $destinationFolder . DIRECTORY_SEPARATOR . "WordPress-$coreVersion";
		$coderDestinationFolder = $wpdir . DIRECTORY_SEPARATOR . 'src';

		$this->filesystem->mirror($wpCoreFolderToCopy, $coderDestinationFolder, null, ['override' => true]);
		$this->filesystem->remove($wpCoreFolderToCopy);
	}

	/**
	 * Checks if the WordPress version is a valid one
	 *
	 * @param string $version Version number.
	 *
	 * @since 1.3.0 Changed the logic of checking the existing tags.
	 * @since 1.0.0
	 *
	 * @return bool True if the response returns correct value. False otherwise.
	 */
	private function isWPVersionValid(string $version): bool
	{
		try {
			return in_array($version, $this->getGitHubTags());
		} catch (GuzzleException $e) {
			return false;
		}
	}

	/**
	 * Check for the validity of the plugin slug
	 *
	 * @link https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/#what-will-my-plugin-permalink-slug-be
	 *
	 * @param string $pluginSlug Plugin slug option passed to the command.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the plugin slug is valid, false if not.
	 */
	private function checkIfPluginSlugIsValid(string $pluginSlug): bool
	{
		preg_match_all('/^[a-z\-\d_\p{Cyrillic}\p{Arabic}★\p{Han}]+$/mu', $pluginSlug, $matches, PREG_SET_ORDER);

		return !empty($matches);
	}

	/**
	 * Get all the tags from the GitHub
	 *
	 * @return string[] List of all the available GitHub tagged versions.
	 *
	 * @since 1.3.0
	 *
	 * @throws GuzzleException Exception in case of Guzzle error.
	 */
	private function getGitHubTags(): array
	{
		static $versions;

		if (empty($versions)) {
			$response = $this->client->request(
				'GET',
				self::WP_API_TAGS,
				[
					'Accept' => 'application/vnd.github.v3+json'
				]
			);
			if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
				return [];
			}

			$contents = $response->getBody()->getContents();

			$refArray = (array) json_decode($contents, true);

			$versions = array_map(static function ($refInfo) {
				if (!is_array($refInfo)) {
					return '';
				}

				$reference = $refInfo['ref'] ?? '';
				preg_match_all('/[\d.]*$/', $reference, $matchNumber, PREG_SET_ORDER);
				return $matchNumber[0][0] ?? '';
			}, $refArray);
		}

		return $versions;
	}
}
