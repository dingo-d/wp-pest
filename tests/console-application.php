<?php

use Symfony\Component\Console\Application;

// Used when running the wp-pest command from composer.
$vendorPath = dirname(__DIR__, 4) . '/vendor/autoload.php';

// Used for local development.
$localPath = dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists($vendorPath)) {
	require_once $vendorPath;
	$autoloadPath = $vendorPath;
} else {
	require_once $localPath;
	$autoloadPath = $localPath;
}

$rootPath = dirname($autoloadPath, 2);

return new Application();
