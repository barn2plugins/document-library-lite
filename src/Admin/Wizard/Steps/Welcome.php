<?php

namespace Barn2\Plugin\Document_Library\Admin\Wizard\Steps;

use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Steps\Welcome_Free;

/**
 * Welcome Step.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Welcome extends Welcome_Free {

	/**
	 * Constructor.
	 */
	public function init() {
		$this->set_id( 'welcome_free' );
		$this->set_title( 'Welcome to Document Library Lite' );
		$this->set_name( esc_html__( 'Welcome', 'document-library-lite' ) );
		$this->set_tooltip( false );
		$this->set_description( esc_html__( 'Start displaying documents in no time.', 'document-library-lite' ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {
		$fields = [
			'welcome_messages' => [
				'type'  => 'heading',
				'raw'   => true,
				'label' => esc_html__( 'Use this setup wizard to quickly configure the most popular options for your document libraries. You can change these options later on the plugin settings page or by relaunching the setup wizard. You can also override these options in the shortcode for individual libraries.', 'document-library-lite' ),
				'size'  => 'p',
				'style' => [
					'textAlign' => 'center',
					'color'     => '#757575'
				]
			]
		];

		return $fields;
	}
}
