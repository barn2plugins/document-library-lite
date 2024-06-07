<?php

namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable,
	Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;

/**
 * Register the Document Library post type
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Post_Type implements Registerable, Standard_Service {
	const POST_TYPE_SLUG = 'dlp_document';

	/**
	 * @var array $default_fields
	 */
	protected $default_fields;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->default_fields = [ 'author', 'title', 'editor', 'thumbnail' ];
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_action( 'init', [ $this, 'register_post_type' ], 15 );
		add_action( 'init', [ $this, 'flush_rewrite_rules' ], 16 );
	}

	/**
	 * Register the Document post type.
	 */
	public function register_post_type() {
		$labels = [
			'name'                  => _x( 'Documents', 'Post Type General Name', 'document-library-lite' ),
			'singular_name'         => _x( 'Document', 'Post Type Singular Name', 'document-library-lite' ),
			'menu_name'             => _x( 'Documents', 'Admin Menu text', 'document-library-lite' ),
			'name_admin_bar'        => _x( 'Documents', 'Add New on Toolbar', 'document-library-lite' ),
			'archives'              => __( 'Documents Archives', 'document-library-lite' ),
			'attributes'            => __( 'Documents Attributes', 'document-library-lite' ),
			'parent_item_colon'     => __( 'Parent Documents:', 'document-library-lite' ),
			'all_items'             => __( 'All Documents', 'document-library-lite' ),
			'add_new_item'          => __( 'Add New Document', 'document-library-lite' ),
			'add_new'               => __( 'Add New', 'document-library-lite' ),
			'new_item'              => __( 'New Document', 'document-library-lite' ),
			'edit_item'             => __( 'Edit Document', 'document-library-lite' ),
			'update_item'           => __( 'Update Document', 'document-library-lite' ),
			'view_item'             => __( 'View Document', 'document-library-lite' ),
			'view_items'            => __( 'View Documents', 'document-library-lite' ),
			'search_items'          => __( 'Search Documents', 'document-library-lite' ),
			'not_found'             => __( 'Not found', 'document-library-lite' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'document-library-lite' ),
			'featured_image'        => __( 'Featured Image', 'document-library-lite' ),
			'set_featured_image'    => __( 'Set featured image', 'document-library-lite' ),
			'remove_featured_image' => __( 'Remove featured image', 'document-library-lite' ),
			'use_featured_image'    => __( 'Use as featured image', 'document-library-lite' ),
			'insert_into_item'      => __( 'Insert into Document', 'document-library-lite' ),
			'uploaded_to_this_item' => __( 'Uploaded to this document', 'document-library-lite' ),
			'items_list'            => __( 'Document list', 'document-library-lite' ),
			'items_list_navigation' => __( 'Documents list navigation', 'document-library-lite' ),
			'filter_items_list'     => __( 'Filter Documents list', 'document-library-lite' ),
		];

		$args = [
			'label'               => __( 'Documents', 'document-library-lite' ),
			'description'         => __( 'Document Library documents.', 'document-library-lite' ),
			'labels'              => $labels,
			'menu_icon'           => 'dashicons-media-document',
			'supports'            => $this->default_fields,
			'taxonomies'          => [],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'document_library',
			'menu_position'       => 26,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'exclude_from_search' => false,
			'show_in_rest'        => false,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
			'rewrite'             => false,
		];

		register_post_type( self::POST_TYPE_SLUG, $args );
	}

	/**
	 * Flushes rewrite rules once after activation and CPT registration.
	 */
	public function flush_rewrite_rules() {
		if ( get_option( 'dlw_should_flush_rewrite_rules' ) ) {
			flush_rewrite_rules();
			update_option( 'dlw_should_flush_rewrite_rules', false );
		}
	}
}
