<?php

namespace Barn2\Plugin\Document_Library\Admin;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use	Barn2\Plugin\Document_Library\Util\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Settings Registry
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Settings implements Registerable, Standard_Service {

	private $plugin;
	
	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'filter_allowed_options' ] );
	}

	/**
	 * Register our settings parent options with Settings API.
	 */
	public function register_settings() {

		register_setting(
			Options::GENERAL_OPTION_GROUP,
			Options::DOCUMENT_FIELDS_OPTION_KEY,
			[
				'type'              => 'string', // array type not supported, so just use string
				'description'       => 'Document Fields',
				'sanitize_callback' => [ $this, 'sanitize_document_fields' ]
			]
		);

		register_setting(
			Options::GENERAL_OPTION_GROUP,
			Options::DOCUMENT_PAGE_OPTION_KEY,
			[
				'type'              => 'string', // array type not supported, so just use string
				'description'       => 'Document Library Pro default page',
				'sanitize_callback' => [ $this, 'sanitize_document_page' ]
			]
		);

		register_setting(
			Options::TABLE_OPTION_GROUP,
			Options::SHORTCODE_OPTION_KEY,
			[
				'type'              => 'string', // array type not supported, so just use string
				'description'       => 'Document Library Pro shortcode defaults',
				'sanitize_callback' => [ $this, 'sanitize_shortcode_settings' ]
			]
		);
	}

	/**
	 * Hook into the allowed_options filter.
	 * Back compatibility ( < 5.5 ) included with 'whitelist_options'.
	 */
	public function filter_allowed_options() {
		if ( function_exists( 'add_allowed_options' ) ) {
			add_filter( 'allowed_options', [ $this, 'allowed_options' ] );
		} else {
			add_filter( 'whitelist_options', [ $this, 'allowed_options' ] );
		}
	}

	/**
	 * Adjust the allowed_options so that single settings keys can be shared across tabs.
	 *
	 * @param array $options
	 * @return array
	 */
	public function allowed_options( $options ) {
		$new_options[ Options::GENERAL_OPTION_GROUP ] = [ Options::SHORTCODE_OPTION_KEY ];

		if ( function_exists( 'add_allowed_options' ) ) {
			$options = add_allowed_options( $new_options, $options );
		} else {
			$options = add_option_whitelist( $new_options, $options );
		}

		return $options;
	}

	/**
	 * Sanitize the document post type fields.
	 *
	 * @param mixed $args
	 * @return string[]
	 */
	public function sanitize_document_fields( $args ) {
		if ( is_null( $args ) ) {
			$args = [];
		}

		$document_fields_structure = [
			'editor'    => '1',
			'excerpt'   => '0',
			'thumbnail' => '1',
			'comments'  => '0',
		];

		return array_merge( $document_fields_structure, $args );
	}

	/**
	 * Sanitize the Document Page setting.
	 *
	 * @param string $page_setting
	 * @return string
	 */
	public function sanitize_document_page( $page_setting ) {
		if ( ! is_numeric( $page_setting ) ) {
			return;
		}

		$page = get_post( absint( $page_setting ) );

		$update_page = [ 'ID' => $page->ID ];

		// Add the doc library shortcode if we don't have it
		if ( $page && 'publish' === $page->post_status && ! stripos( $page->post_content, '[doc_library' ) ) {
			$update_page['post_content'] = $page->post_content . '<!-- wp:shortcode -->[doc_library]<!-- /wp:shortcode -->';
		}

		// We always update post when changing pages to clear any cache
		wp_update_post( $update_page );

		return $page_setting;
	}

	/**
	 * Sanitize the shortcode setting depending on the setting tab.
	 *
	 * @param mixed $args
	 * @return array
	 */
	public function sanitize_shortcode_settings( $args ) {
		$existing_options = $this->get_existing_shortcode_options();
		$option_page      = filter_input( INPUT_GET, 'option_page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! $option_page ) {
			return array_merge( $existing_options, $args );
		}

		if ( is_null( $args ) ) {
			$args = [];
		}

		if ( $option_page === Options::GENERAL_OPTION_GROUP ) {
			// Content length
			if ( isset( $args['content_length'] ) ) {
				$args['content_length'] = filter_var(
					$args['content_length'],
					FILTER_VALIDATE_INT,
					[
						'options' => [
							'min' => -1
						]
					]
				);
			}

			// Rows Per Page
			if ( isset( $args['rows_per_page'] ) ) {
				$args['rows_per_page'] = filter_var(
					$args['rows_per_page'],
					FILTER_VALIDATE_INT,
					[
						'options' => [
							'min' => -1
						]
					]
				);
			}

			// Sort By
			if ( isset( $args['sort_by'] ) ) {
				$args['sort_by'] = sanitize_key( $args['sort_by'] );
			}

			// Sort Order
			if ( isset( $args['sort_order'] ) && ! in_array( $args['sort_order'], [ 'asc', 'desc', '' ], true ) ) {
				$args['sort_order'] = '';
			}

			// Lightbox
			if ( ! isset( $args['lightbox'] ) ) {
				$args['lightbox'] = false;
			}
			$args['lightbox'] = filter_var( $args['lightbox'], FILTER_VALIDATE_BOOLEAN );

		} elseif ( $option_page === Options::TABLE_OPTION_GROUP ) {
			if ( isset( $args['columns'] ) ) {
				$args['columns'] = sanitize_text_field( $args['columns'] );
			}
		}

		$merge_settings = array_merge( $existing_options, $args );

		return $merge_settings;
	}

	/**
	 * Retrieve the existing shortcode options.
	 *
	 * This is used so we can merge the shared settings from other tabs before
	 * WordPress saves the setting.
	 *
	 * @return array
	 */
	private function get_existing_shortcode_options() {
		$current_options = get_option( Options::SHORTCODE_OPTION_KEY );
		$default_args    = Options::get_default_settings();

		$option_keys = [
			// general
			'link_text',
			'lightbox',
			'content_length',
			'rows_per_page',
			'sort_by',
			'sort_order',
			// document tables
			'columns',
		];

		$existing_options = [];

		foreach ( $option_keys as $option ) {
			$existing_options[ $option ] = isset( $current_options[ $option ] ) ? $current_options[ $option ] : $default_args[ $option ];
		}

		return $existing_options;
	}

}
