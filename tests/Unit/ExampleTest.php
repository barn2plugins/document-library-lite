<?php
/**
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 * @link https://github.com/pestphp/pest/blob/master/stubs/init/ExampleTest.php
 */

namespace Barn2\Plugin\Document_Library\Tests;

use WP_Mock;

beforeEach(
	function () {
		WP_Mock::bootstrap();
	}
);

test('example', function () {
    WP_Mock::userFunction(
		'is_single',
		[
			'return_in_order' => [ true, false ],
		]
	);

    expect( is_single() )->toBeTrue();
    expect( is_single() )->toBeFalse();
    expect( is_single() )->toBeFalse();
});
