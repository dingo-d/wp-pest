<?php

namespace MadeByDenis\WpPestIntegrationTestSetup\Tests\Mocks;

use MadeByDenis\WpPestIntegrationTestSetup\Command\InitCommand;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Custom Guzzle mock handler
 *
 * Used for handling requests for GH tags and GH zip file in tests.
 *
 * @since 1.3.0
 */
final class CustomMockHandler
{
	/**
	 * @param RequestInterface $request Incoming request.
	 * @param array<string, mixed> $options Options mock.
	 *
	 * @return PromiseInterface
	 */
	public function __invoke(RequestInterface $request, array $options): PromiseInterface
	{
		if (isset($options['delay']) && \is_numeric($options['delay'])) {
			\usleep((int)$options['delay'] * 1000);
		}

		$ds = DIRECTORY_SEPARATOR;
		$path = $request->getUri()->getPath();

		if (strpos($path, 'wordpress-develop/archive') !== false) {
			$zipContents = (string)file_get_contents(
				dirname(__DIR__) . $ds . 'stubs' . $ds . 'wordpress-develop-6.1.1.zip'
			);
		} else {
			$zipContents = (string)file_get_contents(
				dirname(__DIR__) . $ds . 'stubs' . $ds . 'WordPress-6.1.1.zip'
			);
		}

		$versions = (string)file_get_contents(dirname(__DIR__) . $ds . 'stubs' . $ds . 'git-refs.json');

		// For the path that contains the .zip with version number we need to strip that one out.
		if (preg_match('/\/[\d.]+\.zip/', $path) === 1) {
			$sink = $options['sink'];

			if (\is_resource($sink)) {
				\fwrite($sink, $zipContents);
			} elseif (\is_string($sink)) {
				\file_put_contents($sink, $zipContents);
			} elseif ($sink instanceof StreamInterface) {
				$sink->write($zipContents);
			}

			$body = 'ZIP file';
		} elseif (strpos(InitCommand::WP_API_TAGS, $path) !== false) {
			$body = $versions;
		} else {
			$body = 'Nothing matched';
		}

		return new FulfilledPromise(
			new Response(200, ['X-Header' => 'test'], $body)
		);
	}
}
