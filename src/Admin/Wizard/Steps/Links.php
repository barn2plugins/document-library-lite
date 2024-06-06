<?php

namespace Barn2\Plugin\Document_Library\Admin\Wizard\Steps;

use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Api;
use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Step,
	Barn2\Plugin\Document_Library\Util\Options,
	Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;

/**
 * Document Links Settings Step.
 *
 * @package   Barn2\document-library-lite
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
		$this->set_name( esc_html__( 'Links', 'document-library-lite' ) );
		$this->set_description( esc_html__( 'Next, decide how the links to download a document will look and behave.', 'document-library-lite' ) );
		$this->set_title( esc_html__( 'Document links', 'document-library-lite' ) );

		$this->values = Options::get_defaults();
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {

		$fields = [
			'link_text'  => [
				'label'       => __( 'Link text', 'document-library-lite' ),
				'type'        => 'text',
				'description' => __( 'The text displayed on the button or link.', 'document-library-lite' ),
				'value'       => $this->values['link_text'],
			],
			'link_style' => [
				'label'   => __( 'Link style', 'document-library-lite' ),
				'type'    => 'select',
				'options' => [
					[
						'key'   => 'button',
						'label' => __( 'Button with text', 'document-library-lite' ),
					]
				],
				'value'   => 'button',
				'premium' => true,
			],
			'preview'    => [
				'title'   => __( 'Document preview', 'document-library-lite' ),
				'label'   => __( 'Allow users to preview documents in a lightbox', 'document-library-lite' ),
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
	public function submit( array $values ) {

		$link_text = isset( $values['link_text'] ) ? $values['link_text'] : $this->values['link_text'];

		Options::update_shortcode_option(
			[
				'link_text' => $link_text,
			]
		);

		return Api::send_success_response();

	}

}
