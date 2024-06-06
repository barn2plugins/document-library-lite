<?php

namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;

/**
 * Register the Document Library associated taxonomies
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Taxonomies implements Registerable, Standard_Service {
	const CATEGORY_SLUG          = 'doc_categories';
	const DOCUMENT_DOWNLOAD_SLUG = 'document_download';

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_action( 'init', [ $this, 'register_document_category' ], 11 );
		add_action( 'init', [ $this, 'register_document_download_taxonomy' ], 11 );
	}

	/**
	 * Register the category taxonomy.
	 */
	public function register_document_category() {
		$labels = [
			'name'                       => _x( 'Document Categories', 'Taxonomy General Name', 'document-library-lite' ),
			'singular_name'              => _x( 'Document Category', 'Taxonomy Singular Name', 'document-library-lite' ),
			'menu_name'                  => __( 'Categories', 'document-library-lite' ),
			'all_items'                  => __( 'All Categories', 'document-library-lite' ),
			'parent_item'                => __( 'Parent Category', 'document-library-lite' ),
			'parent_item_colon'          => __( 'Parent Category:', 'document-library-lite' ),
			'new_item_name'              => __( 'New Category Name', 'document-library-lite' ),
			'add_new_item'               => __( 'Add New Category', 'document-library-lite' ),
			'edit_item'                  => __( 'Edit Category', 'document-library-lite' ),
			'update_item'                => __( 'Update Category', 'document-library-lite' ),
			'view_item'                  => __( 'View Category', 'document-library-lite' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'document-library-lite' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'document-library-lite' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'document-library-lite' ),
			'popular_items'              => __( 'Popular Categories', 'document-library-lite' ),
			'search_items'               => __( 'Search Categories', 'document-library-lite' ),
			'not_found'                  => __( 'Not Found', 'document-library-lite' ),
			'no_terms'                   => __( 'No categories', 'document-library-lite' ),
			'items_list'                 => __( 'Categories list', 'document-library-lite' ),
			'items_list_navigation'      => __( 'Categories list navigation', 'document-library-lite' ),
		];

		$args = [
			'labels'            => $labels,
			'public'            => true,
			'hierarchical'      => true,
			'rewrite'           => [ 'slug' => 'document-category' ],
			'capabilities'      => [ Post_Type::POST_TYPE_SLUG ],
			'show_admin_column' => true,
		];

		register_taxonomy( self::CATEGORY_SLUG, Post_Type::POST_TYPE_SLUG, $args );
	}

	/**
	 * Register the document download taxonomy.
	 */
	public function register_document_download_taxonomy() {
		$labels = [
			'name'                       => _x( 'Document Download', 'Taxonomy General Name', 'document-library-lite' ),
			'singular_name'              => _x( 'Document Download', 'Taxonomy Singular Name', 'document-library-lite' ),
			'menu_name'                  => __( 'Document Downloads', 'document-library-lite' ),
			'all_items'                  => __( 'All Items', 'document-library-lite' ),
			'parent_item'                => __( 'Parent Item', 'document-library-lite' ),
			'parent_item_colon'          => __( 'Parent Item:', 'document-library-lite' ),
			'new_item_name'              => __( 'New Item Name', 'document-library-lite' ),
			'add_new_item'               => __( 'Add New Item', 'document-library-lite' ),
			'edit_item'                  => __( 'Edit Item', 'document-library-lite' ),
			'update_item'                => __( 'Update Item', 'document-library-lite' ),
			'view_item'                  => __( 'View Item', 'document-library-lite' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'document-library-lite' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'document-library-lite' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'document-library-lite' ),
			'popular_items'              => __( 'Popular Items', 'document-library-lite' ),
			'search_items'               => __( 'Search Items', 'document-library-lite' ),
			'not_found'                  => __( 'Not Found', 'document-library-lite' ),
			'no_terms'                   => __( 'No items', 'document-library-lite' ),
			'items_list'                 => __( 'Items list', 'document-library-lite' ),
			'items_list_navigation'      => __( 'Items list navigation', 'document-library-lite' ),
		];

		$args = [
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => false,
			'publicly_querable' => true,
			'rewrite'           => [ 'slug' => 'document-download' ],
		];

		register_taxonomy( self::DOCUMENT_DOWNLOAD_SLUG, 'attachment', $args );
	}
}
