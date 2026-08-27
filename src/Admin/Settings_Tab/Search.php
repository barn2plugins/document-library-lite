<?php

namespace Barn2\Plugin\Document_Library\Admin\Settings_Tab;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Admin\Settings_API_Helper;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;
use Barn2\Plugin\Document_Library\Util\Options;

/**
 * Search Setting Tab
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Search implements Registerable {
	const TAB_ID       = 'search';
	const OPTION_GROUP = 'document_library_pro_search';
	const MENU_SLUG    = 'dlp-settings-search';

	private $plugin;
	private $id;
	private $title;
	private $default_settings;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin           = $plugin;
		$this->id               = 'search';
		$this->title            = __( 'Search', 'document-library-lite' );
		$this->default_settings = Options::get_default_settings();
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register the Settings with WP Settings API.
	 */
	public function register_settings() {
		// Search section
		Settings_API_Helper::add_settings_section( 'dlp_search', self::MENU_SLUG, '', [ $this, 'display_search_description' ], $this->get_search_settings() );
	}

	/**
	 * Output the Search description.
	 */
	public function display_search_description() {
		printf(
			'<p>' .
			esc_html__( 'Use the following options to control the search box that appears above your document lists. You can also use the Document Library: Search Box widget to add a search anywhere on your site.', 'document-library-lite' ) .
			'</p>'
		);
	}

	/**
	 * Get the Search settings.
	 *
	 * @return array
	 */
	private function get_search_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'             => __( 'Search filters', 'document-library-lite' ),
					'type'              => 'select',
					'id'                => Options::SHORTCODE_OPTION_KEY . '[filters]',
					'options'           => [
						'false'  => __( 'Disabled', 'document-library-lite' ),
						'true'   => __( 'Show based on data in library', 'document-library-lite' ),
						'custom' => __( 'Custom', 'document-library-lite' ) .
							sprintf( ' <span class="pro-version">%s</span>', Lib_Util::barn2_link( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings', __( 'Pro version only', 'document-library-lite' ), true ) ),
					],
					'desc'              => __( 'Show dropdown menus to allow users to filter the documents.', 'document-library-lite' ) . ' ' . Lib_Util::barn2_link( 'kb/document-library-filters/', '', true ),
					'default'           => 'false',
					'class'             => 'toggle-parent',
					'custom_attributes' => [
						'data-child-class' => 'custom-search-filter',
						'data-toggle-val'  => 'custom',
					],
				],
				[
					'title'   => __( 'Search box', 'document-library-lite' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[search_box]',
					'desc'    => __( 'The position of the search box above the list of documents. You can also add a search box using a shortcode or widget.', 'document-library-lite' ) . ' ' . Lib_Util::barn2_link( 'kb/document-library-search/#standalone-search-box', '', true ),
					'options' => [
						'top'    => __( 'Above library', 'document-library-lite' ),
						'bottom' => __( 'Below library', 'document-library-lite' ),
						'both'   => __( 'Above and below library', 'document-library-lite' ),
						'false'  => __( 'Hidden', 'document-library-lite' ),
					],
					'default' => 'top',
				],
				[
					'title' => __( 'Custom filters', 'document-library-lite' ),
					'type'  => 'text',
					'id'    => Options::SHORTCODE_OPTION_KEY . '[custom_filters]',
					'desc'  => __( 'Enter the filters as a comma-separated list.', 'document-library-lite' ) . ' ' . Lib_Util::barn2_link( 'kb/document-library-filters/', '', true ),
				],
				[
					'title'   => __( 'Search results', 'document-library-lite' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[search_results_page]',
					'options' => [
						'document_search' => __( 'Document search', 'document-library-lite' ),
					],
					'desc'    => sprintf(
						/* translators: %s: global search link. */
						__( 'When using the %s, this page will display your search results.', 'document-library-lite' ),
						Lib_Util::barn2_link( 'kb/document-library-search/#standalone-search-box', __( 'global search', 'document-library-lite' ), true )
					),
					'default' => 'document_search',
				],
			]
		);
	}

	/**
	 * Get the tab title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Get the tab ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}
}
