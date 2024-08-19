<?php

use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Due to the bug in how Pest handles file loading, in order to successfully run the unit tests
 * we need to use this helper function in all integration tests.
 */
function isUnitTest() {
	return !empty($GLOBALS['argv']) && $GLOBALS['argv'][1] === '--group=unit';
}

uses()->group('integration')->in('Integration');
uses()->group('unit')->in('Unit');

uses(TestCase::class)->in('Unit');
