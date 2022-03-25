<?php

namespace Barn2\Plugin\Document_Library\Admin\Wizard\Steps;

use Barn2\Plugin\Document_Library\Dependencies\Barn2\Setup_Wizard\Step,
	Barn2\Plugin\Document_Library\Util\Options,
	Barn2\DLW_Lib\Util as Lib_Util;

/**
 * Document Links Settings Step.
 *
 * @package   Barn2/document-library-for-wordpress
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Links extends Step {

	/**
	 * The default or user setting
	 *
	 * @var array
	 */
	private $values;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->set_id( 'links' );
		$this->set_name( esc_html__( 'Links', 'document-library-for-wordpress' ) );
		$this->set_description( esc_html__( 'Next, decide how the links to download a document will look and behave.', 'document-library-for-wordpress' ) );
		$this->set_title( esc_html__( 'Document links', 'document-library-for-wordpress' ) );

		$this->values = Options::get_defaults();
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {

		$fields = [
			'link_text'  => [
				'label'       => __( 'Link text', 'document-library-for-wordpress' ),
				'type'        => 'text',
				'description' => __( 'The text displayed on the button or link.', 'document-library-for-wordpress' ),
				'value'       => $this->values['link_text'],
			],
			'link_style' => [
				'label'   => __( 'Link style', 'document-library-for-wordpress' ),
				'type'    => 'select',
				'options' => [
					[
						'key'   => 'button',
						'label' => __( 'Button with text', 'document-library-for-wordpress' ),
					]
				],
				'value'   => 'button',
				'premium' => true,
			],
			'preview'    => [
				'title'   => __( 'Document preview', 'document-library-for-wordpress' ),
				'label'   => __( 'Allow users to preview documents in a lightbox', 'document-library-for-wordpress' ),
				'type'    => 'checkbox',
				'value'   => false,
				'premium' => true,
			],
		];

		return $fields;

	}

	/**
	 * {@inheritdoc}
	 */
	public function submit() {

		check_ajax_referer( 'barn2_setup_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->send_error( esc_html__( 'You are not authorized.', 'document-library-for-wordpress' ) );
		}

		$values = $this->get_submitted_values();

		$link_text = isset( $values['link_text'] ) ? $values['link_text'] : $this->values['link_text'];

		Options::update_shortcode_option(
			[
				'link_text' => $link_text,
			]
		);

		wp_send_json_success();

	}

}
