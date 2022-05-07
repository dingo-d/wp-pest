<?php

namespace MadeByDenis\WpPestIntegrationTestSetup\Tests\Unit\Command;

use Brain\Monkey;
use Brain\Monkey\Functions;
use MadeByDenis\WpPestIntegrationTestSetup\Command\InitCommand;
use Zenstruck\Console\Test\TestCommand;

beforeEach(function () {
	Monkey\setUp();
	$this->command = new InitCommand('');
});

afterEach(function () {
	Monkey\tearDown();
});

it('checks that the command name is correct', function () {
	expect($this->command::getDefaultName())->toBe('setup');
});

it('checks that the command doesn\'t have default description', function () {
	expect($this->command::getDefaultDescription())->toBeNull();
});

it('checks that the command runs correctly when --skip-wp option is added', function () {
	Functions\when('mkdir')->justReturn(true); // We don't actually want to create tests folder.

	TestCommand::for($this->command)
		->addOption('--skip-wp')
		->execute()
		->assertSuccessful()
		->assertOutputContains('<success>Folder created successfully</success>');
});
