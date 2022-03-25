<?php

namespace Barn2\Plugin\Document_Library\Admin;

use Barn2\DLW_Lib\Registerable,
	Barn2\DLW_Lib\Service;
use Barn2\Plugin\Document_Library\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the custom menu
 *
 * @package   Barn2/document-library-for-wordpress
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Menu implements Registerable, Service {

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
			__( 'Documents', 'document-library-for-wordpress' ),
			__( 'Documents', 'document-library-for-wordpress' ),
			'edit_posts',
			'document_library',
			'',
			'dashicons-media-document',
			26
		);

		// Add New
		add_submenu_page(
			'document_library',
			__( 'Add New', 'document-library-for-wordpress' ),
			__( 'Add New', 'document-library-for-wordpress' ),
			'edit_posts',
			'/post-new.php?post_type=dlp_document',
			'',
			5
		);

		// Categories
		add_submenu_page(
			'document_library',
			__( 'Categories', 'document-library-for-wordpress' ),
			__( 'Categories', 'document-library-for-wordpress' ),
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

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( $pagenow === 'edit-tags.php' && $_GET['taxonomy'] === Taxonomies::CATEGORY_SLUG ) {
			$parent_file = 'document_library';
		}
		// phpcs:enable

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

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( $pagenow === 'edit-tags.php' && $_GET['taxonomy'] === Taxonomies::CATEGORY_SLUG ) {
			$submenu_file = 'edit-tags.php?taxonomy=doc_categories&post_type=dlp_document';
		}
		// phpcs:enable

		return $submenu_file;
	}
}
