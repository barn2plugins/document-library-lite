<?php

namespace Barn2\Plugin\Document_Library\Table;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\Document_Library\Simple_Document_Library;
use Barn2\Plugin\Document_Library\Simple_Document_Grid;

/**
 * Handles AJAX requests for loading document library posts.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Ajax_Handler implements Standard_Service {
	const SHORTCODE = 'doc_library';

	public function __construct() {
		add_action( 'wp_ajax_dll_load_posts', [ $this, 'load_posts' ] );
		add_action( 'wp_ajax_nopriv_dll_load_posts', [ $this, 'load_posts' ] );
		add_action( 'wp_ajax_dll_load_grid', [ $this, 'load_grid' ] );
		add_action( 'wp_ajax_nopriv_dll_load_grid', [ $this, 'load_grid' ] );
	}

	public function register() {
	}

	public function load_posts() {
		// Verify nonce first for all requests
		$nonce = '';
		if ( isset( $_POST['_ajax_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		} elseif ( isset( $_POST['ajax_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['ajax_nonce'] ) );
		}

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dll_load_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Security check failed' ], 403 );
		}

		if ( ! isset( $_POST['table_id'] ) ) {
			wp_send_json_error( [ 'message' => 'Table ID is required' ], 400 );
		}

		$table_id = sanitize_key( wp_unslash( $_POST['table_id'] ) );

		// Retrieve the stored configuration using the table ID
		$args = Config_Builder::retrieve( $table_id );

		if ( false === $args ) {
			wp_send_json_error( [ 'message' => 'Invalid or expired table configuration' ], 404 );
		}

		$requested_status = isset( $args['status'] ) ? $args['status'] : 'publish';
		$is_logged_in = is_user_logged_in();

		// Unauthenticated users can ONLY see published content
		if ( ! $is_logged_in ) {
			$args['status'] = 'publish';
		}
		// Authenticated users requesting non-published content must have capability
		elseif ( $requested_status !== 'publish' ) {
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( [ 'message' => 'Insufficient permissions' ], 403 );
			}
		}

		// Allow category filtering via AJAX (this is safe because it's part of the UI)
		// The category input field is visible to users and they can change it
		if ( isset( $_POST['category'] ) && ! empty( $_POST['category'] ) ) {
			$args['doc_category'] = sanitize_text_field( wp_unslash( $_POST['category'] ) );
		}

		$table = new simple_Document_Library( $args, $table_id );
		$response = $table->get_table( 'array' );

		// Return the response as JSON
		wp_send_json( $response );
	}

	/**
	 * Handle AJAX requests for loading a page of grid cards.
	 */
	public function load_grid() {
		$nonce = '';
		if ( isset( $_POST['_ajax_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		} elseif ( isset( $_POST['ajax_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['ajax_nonce'] ) );
		}

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dll_load_grid' ) ) {
			wp_send_json_error( [ 'message' => 'Security check failed' ], 403 );
		}

		if ( ! isset( $_POST['grid_id'] ) ) {
			wp_send_json_error( [ 'message' => 'Grid ID is required' ], 400 );
		}

		$grid_id = sanitize_key( wp_unslash( $_POST['grid_id'] ) );

		// Retrieve the stored configuration using the grid ID.
		$args = Config_Builder::retrieve( $grid_id );

		if ( false === $args ) {
			wp_send_json_error( [ 'message' => 'Invalid or expired grid configuration' ], 404 );
		}

		$requested_status = isset( $args['status'] ) ? $args['status'] : 'publish';

		// Unauthenticated users can ONLY see published content.
		if ( ! is_user_logged_in() ) {
			$args['status'] = 'publish';
		} elseif ( $requested_status !== 'publish' && ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions' ], 403 );
		}

		// Allow category filtering via the visible UI input.
		if ( isset( $_POST['category'] ) && ! empty( $_POST['category'] ) ) {
			$args['doc_category'] = sanitize_text_field( wp_unslash( $_POST['category'] ) );
		}

		// Free-text search.
		if ( isset( $_POST['search_query'] ) ) {
			$args['search_value'] = sanitize_text_field( wp_unslash( $_POST['search_query'] ) );
		}

		// Documents per page.
		if ( isset( $_POST['length'] ) ) {
			$length = (int) $_POST['length'];
			if ( $length > 0 ) {
				$args['requested_length'] = $length;
			}
		}

		$page = isset( $_POST['page_number'] ) ? max( 1, (int) $_POST['page_number'] ) : 1;

		$grid = new Simple_Document_Grid( $args, $grid_id );

		wp_send_json( $grid->get_ajax_parts( $page ) );
	}

}
