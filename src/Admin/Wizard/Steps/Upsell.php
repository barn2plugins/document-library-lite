<?php

namespace Barn2\Plugin\Document_Library\Admin\Wizard\Steps;

use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Steps\Cross_Selling;
use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Util;

/**
 * Upsell Step.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Upsell extends Cross_Selling {

	// URL of the api from where upsells are pulled from.
	const REST_URL = 'https://barn2.com/wp-json/upsell/v1/get/';

	/**
	 * Constructor.
	 */
	public function init() {
		$this->set_id( 'more' );
		$this->set_name( esc_html__( 'More', 'document-library-lite' ) );
		$this->set_description(
			sprintf(
				// translators: %1$s: URL to All Access Pass page %2$s: URL to the KB about the upgrading process
				__( 'Enhance your website with these fantastic plugins from Barn2, or get them all by upgrading to an <a href="%1$s" target="_blank">All Access Pass<a/>! <a href="%2$s" target="_blank">(learn how here)</a>', 'document-library-lite' ),
				Util::generate_utm_url( 'https://barn2.com/wordpress-plugins/bundles/', 'dlw' ),
				Util::generate_utm_url( 'https://barn2.com/kb/how-to-upgrade-license/', 'dlw' )
			)
		);
		$this->set_title( esc_html__( 'Extra features', 'document-library-lite' ) );
	}

}
