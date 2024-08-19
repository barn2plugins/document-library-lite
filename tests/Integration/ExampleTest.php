<?php
/**
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\Document_Library\Tests;

use Yoast\WPTestUtils\WPIntegration\TestCase;

// Abort if running unit tests.
if ( isUnitTest() ) {
	return;
}

/*
 * We need to provide the base test class to every integration test.
 * This will enable us to use all the WordPress test goodies, such as
 * factories and proper test cleanup.
 */
uses(TestCase::class);

beforeEach(function () {
	parent::setUp();

	// Set up a REST server instance.
	global $wp_rest_server;

	$this->server = $wp_rest_server = new \WP_REST_Server();
	do_action('rest_api_init', $this->server);
});

afterEach(function () {
	global $wp_rest_server;
	$wp_rest_server = null;

	parent::tearDown();
});

test('Rest API endpoints work', function () {
	$routes = $this->server->get_routes();

	expect($routes)
		->toBeArray()
		->toHaveKey('/wp/v2/posts');
});

test('Creating terms in category works', function() {
	$this::factory()->term->create_many(5, [
		'taxonomy' => 'category',
	]);

	expect(get_terms([
		'taxonomy' => 'category',
		'hide_empty' => false,
	]))->toBeArray()
		->toHaveCount(6); // Uncategorized is a default term in the category taxonomy.
});
