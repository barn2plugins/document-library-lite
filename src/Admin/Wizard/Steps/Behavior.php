<?php

namespace Barn2\Plugin\Document_Library\Admin\Wizard\Steps;

use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Api;
use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Step,
	Barn2\Plugin\Document_Library\Util\Options,
	Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;

/**
 * Behaviour Settings Step.
 *
 * @package   Barn2\document-library-lite
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
		$this->set_name( esc_html__( 'Behavior', 'document-library-lite' ) );
		$this->set_description( esc_html__( 'Finally, choose from a range of options to customize your document libraries.', 'document-library-lite' ) );
		$this->set_title( esc_html__( 'Document library behavior', 'document-library-lite' ) );

		$this->values = Options::get_defaults();
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {
		$fields = [
			'lightbox'      => [
				'title' => __( 'Image lightbox', 'document-library-lite' ),
				'label' => __( 'Display images in a lightbox when opened', 'document-library-lite' ),
				'type'  => 'checkbox',
				'value' => $this->values['lightbox'],
			],
			'rows_per_page' => [
				'label'       => __( 'Documents per page', 'document-library-lite' ),
				'type'        => 'number',
				'description' => __( 'The text displayed on the button or link.', 'document-library-lite' ),
				'value'       => $this->values['rows_per_page'],
			],
			'sort_by'       => [
				'label'       => __( 'Sort by', 'document-library-lite' ),
				'type'        => 'select',
				'description' => __( 'The initial sort order of the document library.', 'document-library-lite' ) . ' ' . Lib_Util::barn2_link( 'kb/document-library-wordpress-documentation/#general-tab', esc_html__( 'Read more', 'document-library-lite' ), true ),
				'options'     => [
					[
						'value' => 'title',
						'label' => __( 'Title', 'document-library-lite' ),
					],
					[
						'value' => 'id',
						'label' => __( 'ID', 'document-library-lite' ),
					],
					[
						'value' => 'date',
						'label' => __( 'Date published', 'document-library-lite' ),
					],
					[
						'value' => 'modified',
						'label' => __( 'Date modified', 'document-library-lite' ),
					],
					[
						'value' => 'menu_order',
						'label' => __( 'Page order (menu order)', 'document-library-lite' ),
					],
					[
						'value' => 'author',
						'label' => __( 'Author', 'document-library-lite' ),
					],
					[
						'value' => 'rand',
						'label' => __( 'Random', 'document-library-lite' ),
					],
				],
				'value'       => $this->values['sort_by'],
			],
			'sort_order'    => [
				'label'   => __( 'Sort direction', 'document-library-lite' ),
				'type'    => 'select',
				'options' => [
					[
						'value' => 'auto',
						'label' => __( 'Automatic', 'document-library-lite' ),
					],
					[
						'value' => 'asc',
						'label' => __( 'Ascending (A to Z, oldest to newest)', 'document-library-lite' ),
					],
					[
						'value' => 'desc',
						'label' => __( 'Descending (Z to A, newest to oldest)', 'document-library-lite' ),
					],
				],
				'value'   => $this->values['sort_order'] === '' ? 'auto' : $this->values['sort_order']
			],
			'filters'       => [
				'label'       => __( 'Search filters', 'document-library-lite' ),
				'type'        => 'select',
				'description' => __( 'Show filters dropdown to allow users to filter by categories, tags, or custom taxonomy.', 'document-library-lite' ),
				'options'     => [
					[
						'value' => 'false',
						'label' => __( 'Disabled', 'document-library-lite' ),
					]
				],
				'value'       => 'false',
				'premium'     => true,
			],
			'lazy_load'     => [
				'title'       => __( 'Lazy load', 'document-library-lite' ),
				'type'        => 'checkbox',
				'label'       => __( 'Load the document table one page at a time', 'document-library-lite' ),
				'description' => __( 'Enable this if you will have lots of documents, otherwise leave it blank.', 'document-library-lite' ),
				'value'       => $this->values['lazy_load'],
			],
		];

		return $fields;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submit( array $values ) {
		$lightbox      = isset( $values['lightbox'] ) && $values['lightbox'] ? true : false;
		$rows_per_page = isset( $values['rows_per_page'] ) && is_numeric( $values['rows_per_page'] ) ? $values['rows_per_page'] : $this->values['rows_per_page'];
		$sort_by       = isset( $values['sort_by'] ) ? $values['sort_by'] : $this->values['sort_by'];
		$sort_order    = isset( $values['sort_order'] ) ? $values['sort_order'] : $this->values['sort_order'];
		$lazy_load     = isset( $values['lazy_load'] ) && $values['lazy_load'] ? true : false;

		if ( $sort_order === 'auto' ) {
			$sort_order = '';
		}

		Options::update_shortcode_option(
			[
				'lightbox'      => $lightbox,
				'rows_per_page' => $rows_per_page,
				'sort_by'       => $sort_by,
				'sort_order'    => $sort_order,
				'lazy_load'		=> $lazy_load
			]
		);

		return Api::send_success_response();
	}

}
