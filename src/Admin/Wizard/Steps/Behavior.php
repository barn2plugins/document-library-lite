<?php

namespace Barn2\Plugin\Document_Library\Admin\Wizard\Steps;

use Barn2\Plugin\Document_Library\Dependencies\Barn2\Setup_Wizard\Step,
	Barn2\Plugin\Document_Library\Util\Options,
	Barn2\DLW_Lib\Util as Lib_Util;

/**
 * Behaviour Settings Step.
 *
 * @package   Barn2/document-library-for-wordpress
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Behavior extends Step {


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
		$this->set_id( 'table' );
		$this->set_name( esc_html__( 'Behavior', 'document-library-for-wordpress' ) );
		$this->set_description( esc_html__( 'Finally, choose from a range of options to customize your document libraries.', 'document-library-for-wordpress' ) );
		$this->set_title( esc_html__( 'Document library behavior', 'document-library-for-wordpress' ) );

		$this->values = Options::get_defaults();
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {
		$fields = [
			'lightbox'      => [
				'title' => __( 'Image lightbox', 'document-library-for-wordpress' ),
				'label' => __( 'Display images in a lightbox when opened', 'document-library-for-wordpress' ),
				'type'  => 'checkbox',
				'value' => false,
			],
			'rows_per_page' => [
				'label'       => __( 'Documents per page', 'document-library-for-wordpress' ),
				'type'        => 'number',
				'description' => __( 'The text displayed on the button or link.', 'document-library-for-wordpress' ),
				'value'       => $this->values['rows_per_page'],
			],
			'sort_by'       => [
				'label'       => __( 'Sort by', 'document-library-for-wordpress' ),
				'type'        => 'select',
				'description' => __( 'The initial sort order of the document library.', 'document-library-for-wordpress' ) . ' ' . Lib_Util::barn2_link( 'kb/document-library-wordpress-documentation/#general-tab', esc_html__( 'Read more', 'document-library-for-wordpress' ), true ),
				'options'     => [
					[
						'key'   => 'title',
						'label' => __( 'Title', 'document-library-for-wordpress' ),
					],
					[
						'key'   => 'id',
						'label' => __( 'ID', 'document-library-for-wordpress' ),
					],
					[
						'key'   => 'date',
						'label' => __( 'Date published', 'document-library-for-wordpress' ),
					],
					[
						'key'   => 'modified',
						'label' => __( 'Date modified', 'document-library-for-wordpress' ),
					],
					[
						'key'   => 'menu_order',
						'label' => __( 'Page order (menu order)', 'document-library-for-wordpress' ),
					],
					[
						'key'   => 'author',
						'label' => __( 'Author', 'document-library-for-wordpress' ),
					],
					[
						'key'   => 'rand',
						'label' => __( 'Random', 'document-library-for-wordpress' ),
					],
				],
				'value'       => $this->values['sort_by'],
			],
			'sort_order'    => [
				'label'   => __( 'Sort direction', 'document-library-for-wordpress' ),
				'type'    => 'select',
				'options' => [
					[
						'key'   => 'auto',
						'label' => __( 'Automatic', 'document-library-for-wordpress' ),
					],
					[
						'key'   => 'asc',
						'label' => __( 'Ascending (A to Z, oldest to newest)', 'document-library-for-wordpress' ),
					],
					[
						'key'   => 'desc',
						'label' => __( 'Descending (Z to A, newest to oldest)', 'document-library-for-wordpress' ),
					],
				],
				'value'   => $this->values['sort_order'] === '' ? 'auto' : $this->values['sort_order']
			],
			'filters'       => [
				'label'       => __( 'Search filters', 'document-library-for-wordpress' ),
				'type'        => 'select',
				'description' => __( 'Show filters dropdown to allow users to filter by categories, tags, or custom taxonomy.', 'document-library-for-wordpress' ),
				'options'     => [
					[
						'key'   => 'false',
						'label' => __( 'Disabled', 'document-library-for-wordpress' ),
					]
				],
				'value'       => 'false',
				'premium'     => true,
			],
			'lazy_load'     => [
				'title'       => __( 'Lazy load', 'document-library-for-wordpress' ),
				'type'        => 'checkbox',
				'label'       => __( 'Load the document table one page at a time', 'document-library-for-wordpress' ),
				'description' => __( 'Enable this if you will have lots of documents, otherwise leave it blank.', 'document-library-for-wordpress' ),
				'value'       => false,
				'premium'     => true,
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

		$lightbox      = isset( $values['lightbox'] ) && $values['lightbox'] ? true : false;
		$rows_per_page = isset( $values['rows_per_page'] ) && is_numeric( $values['rows_per_page'] ) ? $values['rows_per_page'] : $this->values['rows_per_page'];
		$sort_by       = isset( $values['sort_by'] ) ? $values['sort_by'] : $this->values['sort_by'];
		$sort_order    = isset( $values['sort_order'] ) ? $values['sort_order'] : $this->values['sort_order'];

		if ( $sort_order === 'auto' ) {
			$sort_order = '';
		}

		Options::update_shortcode_option(
			[
				'lightbox'      => $lightbox,
				'rows_per_page' => $rows_per_page,
				'sort_by'       => $sort_by,
				'sort_order'    => $sort_order,
			]
		);

		wp_send_json_success();
	}

}
