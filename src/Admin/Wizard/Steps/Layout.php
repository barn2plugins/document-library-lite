<?php

namespace Barn2\Plugin\Document_Library\Admin\Wizard\Steps;

use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Api;
use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Step,
	Barn2\Plugin\Document_Library\Util\Options,
	Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;

/**
 * Layout Settings Step.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Layout extends Step {

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
		$this->set_id( 'layout' );
		$this->set_name( esc_html__( 'Layout and Content', 'document-library-lite' ) );
		$this->set_description( esc_html__( 'First, choose the layout for your document libraries.', 'document-library-lite' ) );
		$this->set_title( esc_html__( 'Layout and content', 'document-library-lite' ) );

		$this->values = Options::get_defaults();
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {

		$fields = [
			'columns' => [
				'label'       => __( 'Columns', 'document-library-lite' ),
				'description' => __( 'Enter the fields to include in your document tables.', 'document-library-lite' ) . ' ' . Lib_Util::barn2_link( 'kb/document-library-wordpress-documentation/#document-tables-tab', esc_html__( 'Read more', 'document-library-lite' ), true ),
				'type'        => 'text',
				'value'       => $this->values['columns'],
			],
			'layout'  => [
				'label'   => __( 'Default layout', 'document-library-lite' ),
				'type'    => 'radio',
				'options' => [
					[
						'value' => 'table',
						'label' => __( 'Table', 'document-library-lite' ),
					],
					[
						'value' => 'grid',
						'label' => __( 'Grid', 'document-library-lite' ),
					]
				],
				'value'   => 'table',
				'premium' => true,
			],
			'folders' => [
				'title'   => __( 'Folders', 'document-library-lite' ),
				'label'   => __( 'Display the document library in folders', 'document-library-lite' ),
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

		$columns = isset( $values['columns'] ) && ! empty( trim( $values['columns'] ) ) ? $values['columns'] : $this->values['columns'];

		Options::update_shortcode_option(
			[
				'columns' => $columns,
			]
		);

		return Api::send_success_response();

	}

}
