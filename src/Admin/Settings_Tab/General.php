<?php

namespace Barn2\Plugin\Document_Library\Admin\Settings_Tab;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Admin\Settings_API_Helper;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;
use	Barn2\Plugin\Document_Library\Util\Options;

/**
 * General Setting Tab
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class General implements Registerable {
	const TAB_ID       = 'general';
	const OPTION_GROUP = 'document_library_pro_general';
	const MENU_SLUG    = 'dlp-settings-general';

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
		$this->id               = 'general';
		$this->title            = __( 'General', 'document-library-lite' );
		$this->default_settings = Options::get_default_settings();
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_filter( 'barn2_plugin_settings_help_links', [ $this, 'change_support_url' ], 10, 2 );
	}

	/**
	 * Register the Settings with WP Settings API.
	 */
	public function register_settings() {
		// General Section
		Settings_API_Helper::add_settings_section( 'dlp_general', self::MENU_SLUG, '', [ $this, 'display_general_description' ], $this->get_general_settings() );

		// Document Data
		Settings_API_Helper::add_settings_section( 'dlp_document_data', self::MENU_SLUG, __( 'Document data', 'document-library-lite' ), [ $this, 'display_document_data_description' ], $this->get_document_data_settings() );

		// Version Control - Pro Only
		Settings_API_Helper::add_settings_section( 'dlp_version_control', self::MENU_SLUG, __( 'Version control', 'document-library-lite' ), [ $this, 'display_version_control_section' ], [] );

		// Frontend Submission - Pro Only
		Settings_API_Helper::add_settings_section( 'dlp_frontend_submission', self::MENU_SLUG, __( 'Front end document submission', 'document-library-lite' ), [ $this, 'display_frontend_submission_section' ], [] );

		// Lead Capture - Pro Only
		Settings_API_Helper::add_settings_section( 'dlp_lead_capture', self::MENU_SLUG, __( 'Lead capture', 'document-library-lite' ), [ $this, 'display_lead_capture_section' ], [] );
	}

	/**
	 * Output the General section description.
	 */
	public function display_general_description() {
		printf(
			'<p>' .
			esc_html__( 'The following options control the Document Library extension.', 'document-library-lite' ) .
			'</p>'
		);
	}

	/**
	 * Output the Document Data description.
	 */
	public function display_document_data_description() {
		printf(
			'<p>' .
			/* translators: %1: knowledge base link start, %2: knowledge base link end */
			esc_html__( 'Use the following options to manage the fields that are used to store information about your documents. You can add additional fields using a custom fields plugin and display them in the table layout. %1$sRead more%2$s.', 'document-library-lite' ) .
			'</p>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::format_link_open( Lib_Util::barn2_url( 'kb/document-library-settings/#document-fields' ), true ),
			'</a>'
		);
	}

	/**
	 * Output Pro-only section description.
	 */
	public function display_pro_only_section() {
		printf(
			'<p><span class="dlw-pro-only">%s</span></p>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::barn2_link( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings', __( 'Pro version only', 'document-library-lite' ), true )
		);
	}

	/**
	 * Output the Version Control section description.
	 */
	public function display_version_control_section() {
		printf(
			'<p>' .
			esc_html__( 'The version control options allow you to decide how to keep track of the uploaded files.', 'document-library-lite' ) .
			' %s</p>' .
			'<p><span class="dlw-pro-only">%s</span></p>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::barn2_link( 'kb/document-version-control', __( 'Read more', 'document-library-lite' ), true ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::barn2_link( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings', __( 'Pro version only', 'document-library-lite' ), true )
		);
	}

	/**
	 * Output the Frontend Submission section description.
	 */
	public function display_frontend_submission_section() {
		printf(
			'<p>' .
			esc_html__( 'Use the [dlp_submission_form] shortcode to allow people to add documents from the front end.', 'document-library-lite' ) .
			' %s</p>' .
			'<p><span class="dlw-pro-only">%s</span></p>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::barn2_link( 'kb/add-import-documents/#upload-documents-from-the-front-end', __( 'Read more', 'document-library-lite' ), true ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::barn2_link( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings', __( 'Pro version only', 'document-library-lite' ), true )
		);
	}

	/**
	 * Output the Lead Capture section description.
	 */
	public function display_lead_capture_section() {
		printf(
			'<p>' .
			esc_html__( 'Require users to enter their email address before they can access document links.', 'document-library-lite' ) .
			' %s</p>' .
			'<p><span class="dlw-pro-only">%s</span></p>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::barn2_link( 'kb/lead-capture/', __( 'Read more', 'document-library-lite' ), true ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::barn2_link( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings', __( 'Pro version only', 'document-library-lite' ), true )
		);
	}

	/**
	 * Get the General settings.
	 *
	 * @return array
	 */
	private function get_general_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'id'       => Options::DOCUMENT_PAGE_OPTION_KEY,
					'title'    => __( 'Document library page', 'document-library-lite' ),
					'type'     => 'select',
					'desc'     => __( 'The page to display your documents.', 'document-library-lite' ),
					'desc_tip' => __( 'You can also use the [doc_library] shortcode to list documents on other pages.', 'document-library-lite' ),
					'options'  => $this->get_pages(),
					'default'  => '',
				],
			]
		);
	}

	/**
	 * Get the Document Data settings.
	 *
	 * @return array
	 */
	private function get_document_data_settings() {
		return [
			[
				'id'       => Options::DOCUMENT_FIELDS_OPTION_KEY,
				'title'    => __( 'Document fields', 'document-library-lite' ),
				'type'     => 'multicheckbox',
				'options'  => [
					'editor'          => __( 'Content', 'document-library-lite' ),
					'excerpt'         => __( 'Excerpt', 'document-library-lite' ),
					'thumbnail'       => __( 'Featured image', 'document-library-lite' ),
					'comments'        => __( 'Comments', 'document-library-lite' ),
					'custom_fields'   => __( 'Custom fields', 'document-library-lite' ),
					'author'          => __( 'Author', 'document-library-lite' ),
				],
				'default'  => [
					'editor'     => '1',
					'excerpt'    => '0',
					'thumbnail'  => '1',
					'comments'   => '0',
				],
				'field_class'       => 'readonly',
				'custom_attributes' => [
					'disabled' => 'disabled'
				]
			],
			[
				'id'       => Options::DOCUMENT_FIELDS_OPTION_KEY . '[document_slug]',
				'title'    => __( 'Document slug', 'document-library-lite' ) .
					sprintf( ' <span class="pro-version">%s</span>', Lib_Util::barn2_link( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings', __( 'Pro version only', 'document-library-lite' ), true ) ),
				'type'     => 'text',
				'desc'     => __( 'Change the permalink for your documents.', 'document-library-lite' ) . ' ' . Lib_Util::barn2_link( 'kb/document-library-settings/#document-slug', '', true ),
				'default'  => 'doc_library',
				'field_class'       => 'readonly',
				'custom_attributes' => [
					'disabled' => 'disabled'
				]
			],
		];
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

	/**
	 * Get a list of WP Pages for the settings select.
	 *
	 * @return array
	 */
	private function get_pages() {
		$pages = get_pages(
			[
				'sort_column'  => 'menu_order',
				'sort_order'   => 'ASC',
				'hierarchical' => 0,
			]
		);

		$options = [];
		foreach ( $pages as $page ) {
			$options[ $page->ID ] = ! empty( $page->post_title ) ? $page->post_title : '#' . $page->ID;
		}

		return $options;
	}

	/**
	 * Change the default support link to the WordPress repository
	 */
	public function change_support_url( $links, $plugin ) {
		if( $plugin->get_id() === $this->plugin->get_id() ) {
			$links[ 'support' ][ 'url' ] = 'https://wordpress.org/support/plugin/document-library-lite/';
		}
		return $links;
	}
}
