<?php

namespace Barn2\Plugin\Document_Library\Admin;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\Document_Library\Taxonomies;

/**
 * Handles the custom menu
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Menu implements Registerable, Standard_Service {

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
		add_filter( 'parent_file', [ $this, 'keep_menu_open' ] );
		add_filter( 'submenu_file', [ $this, 'highlight_submenus' ] );
	}

	/**
	 * Registers our custom menu and sub menu pages.
	 *
	 * @return void
	 */
	public function add_menu_pages() {
		// Main Menu
		add_menu_page(
			__( 'Documents', 'document-library-lite' ),
			__( 'Documents', 'document-library-lite' ),
			'edit_posts',
			'document_library',
			'',
			'dashicons-media-document',
			26
		);

		// Add New
		add_submenu_page(
			'document_library',
			__( 'Add New', 'document-library-lite' ),
			__( 'Add New', 'document-library-lite' ),
			'edit_posts',
			'/post-new.php?post_type=dlp_document',
			'',
			5
		);

		// Categories
		add_submenu_page(
			'document_library',
			__( 'Categories', 'document-library-lite' ),
			__( 'Categories', 'document-library-lite' ),
			'edit_posts',
			'/edit-tags.php?taxonomy=doc_categories&post_type=dlp_document',
			'',
			6
		);
	}

	/**
	 * Need to make sure the Documents menu stays open when we are on a taxonomy page
	 *
	 * @param   string  $parent_file The filename of the parent menu.
	 * @return  string  $parent_file
	 */
	public function keep_menu_open( $parent_file ) {
		global $pagenow;

		$taxonomy = filter_input( INPUT_GET, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( in_array( $pagenow, [ 'term.php', 'edit-tags.php' ], true ) && $taxonomy === Taxonomies::CATEGORY_SLUG ) {
			$parent_file = 'document_library';
		}

		return $parent_file;
	}

	/**
	 * Highlight submenu pages
	 *
	 * @param   string  $submenu_file The filename of the sub menu.
	 * @return  string  $submenu_file
	 */
	public function highlight_submenus( $submenu_file ) {
		global $pagenow;

		$taxonomy = filter_input( INPUT_GET, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( in_array( $pagenow, [ 'term.php', 'edit-tags.php' ], true ) && $taxonomy === Taxonomies::CATEGORY_SLUG ) {
			$submenu_file = 'edit-tags.php?taxonomy=doc_categories&post_type=dlp_document';
		}

		return $submenu_file;
	}
}
