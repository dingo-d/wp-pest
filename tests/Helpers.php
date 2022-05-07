<?php

/**
 * A test helper functions
 *
 * @package MadeByDenis\WpPestIntegrationTestSetup
 *
 * @since 1.0.0
 */

namespace MadeByDenis\WpPestIntegrationTestSetup\Tests;

use Mockery;
use Mockery\MockInterface;

/**
 * Mockery shorthand
 *
 * @param string $class Class name to mock.
 *
 * @return \Mockery\MockInterface
 */
function mock(string $class): MockInterface
{
	return Mockery::mock($class);
}
