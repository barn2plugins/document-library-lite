<?php

namespace Barn2\Plugin\Document_Library\Admin\Settings_Tab;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Admin\Settings_API_Helper;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;
use Barn2\Plugin\Document_Library\Util\Options;

/**
 * Display Setting Tab
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Display implements Registerable {
	const TAB_ID       = 'display';
	const OPTION_GROUP = 'document_library_pro_display';
	const MENU_SLUG    = 'dlp-settings-display';

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
		$this->id               = 'display';
		$this->title            = __( 'Display', 'document-library-lite' );
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
		// Display section
		Settings_API_Helper::add_settings_section( 'dlp_display', self::MENU_SLUG, '', [ $this, 'display_description' ], $this->get_display_settings() );

		// Table section
		Settings_API_Helper::add_settings_section( 'dlp_table', self::MENU_SLUG, __( 'Table', 'document-library-lite' ), '__return_false', $this->get_table_settings() );

		// Grid section
		Settings_API_Helper::add_settings_section( 'dlp_grid', self::MENU_SLUG, __( 'Grid', 'document-library-lite' ), [ $this, 'display_grid_section' ], $this->get_grid_settings() );

		// Sorting section
		Settings_API_Helper::add_settings_section( 'dlp_sort_by', self::MENU_SLUG, __( 'Sorting', 'document-library-lite' ), '__return_false', $this->get_sort_by_settings() );

		// Download button section
		Settings_API_Helper::add_settings_section( 'dlp_download_button', self::MENU_SLUG, __( 'Download button', 'document-library-lite' ), '__return_false', $this->get_download_button_settings() );

		// Preview button section
		Settings_API_Helper::add_settings_section( 'dlp_preview_button', self::MENU_SLUG, __( 'Preview button', 'document-library-lite' ), '__return_false', $this->get_preview_button_settings() );

		// Multi-downloads section
		Settings_API_Helper::add_settings_section( 'dlp_multi_downloads', self::MENU_SLUG, __( 'Multi-downloads', 'document-library-lite' ), [ $this, 'display_multi_downloads_section' ], [] );

		// Folders section
		Settings_API_Helper::add_settings_section( 'dlp_folders', self::MENU_SLUG, __( 'Folders', 'document-library-lite' ), '__return_false', $this->get_folders_settings() );
	}

	/**
	 * Output the Display description.
	 */
	public function display_description() {
		printf(
			'<p>' .
			esc_html__( 'The following options control the contents and layout of your document lists.', 'document-library-lite' ) .
			'</p>'
		);
	}

	/**
	 * Output Pro-only section description.
	 */
	public function display_pro_only_section() {
		printf(
			'<p><span class="pro-version">%s</span></p>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::barn2_link( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings', __( 'Pro version only', 'document-library-lite' ), true )
		);
	}

	/**
	 * Output the Grid section description.
	 */
	public function display_grid_section() {
		printf(
			'<p>%s</p>',
			esc_html__( 'Display your documents in a responsive grid layout. Set the default layout to Grid to enable it.', 'document-library-lite' )
		);
	}

	/**
	 * Get the Grid settings.
	 *
	 * @return array
	 */
	private function get_grid_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'id'      => Options::SHORTCODE_OPTION_KEY . '[grid_content]',
					'title'   => __( 'Grid content', 'document-library-lite' ),
					'type'    => 'text',
					'desc'    => __( 'Choose which information to display in the grid of documents.', 'document-library-lite' ) . ' ' . Lib_Util::barn2_link( 'kb/document-library-wordpress-documentation/#document-tables', '', true ),
					'default' => 'image,title,content,link',
				],
				[
					'id'                => Options::SHORTCODE_OPTION_KEY . '[grid_columns]',
					'title'             => __( 'Number of columns', 'document-library-lite' ),
					'type'              => 'number',
					'class'             => 'small-text',
					'desc'              => __( 'The number of columns to display in the grid layout.', 'document-library-lite' ),
					'default'           => 4,
					'custom_attributes' => [
						'min' => 1,
						'max' => 6,
					],
				],
			]
		);
	}

	/**
	 * Output the Multi-downloads section description.
	 */
	public function display_multi_downloads_section() {
		printf(
			'<div class="multi-downloads-settings"> <p>' .
			esc_html__( 'Enable the multi-download feature to allow users to select multiple documents and download them together as a single ZIP file.', 'document-library-lite' ) .
			' %s</p>' .
			'<p><span class="pro-version">%s</span></p></div>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::barn2_link( 'kb/document-multiple-download/', __( 'Read more', 'document-library-lite' ), true ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::barn2_link( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings', __( 'Pro version only', 'document-library-lite' ), true )
		);
	}

	/**
	 * Get the Display settings.
	 *
	 * @return array
	 */
	private function get_display_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'id'      => Options::SHORTCODE_OPTION_KEY . '[layout]',
					'title'   => __( 'Default layout', 'document-library-lite' ),
					'type'    => 'radio',
					'options' => [
						'table' => __( 'Table', 'document-library-lite' ),
						'grid'  => __( 'Grid', 'document-library-lite' ),
					],
					'default' => 'table',
				],
			]
		);
	}

	/**
	 * Get the Table settings.
	 *
	 * @return array
	 */
	private function get_table_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'id'      => Options::SHORTCODE_OPTION_KEY . '[columns]',
					'title'   => __( 'Columns', 'document-library-lite' ),
					'type'    => 'text',
					'desc'    => __( 'Enter the fields to include in your document tables.', 'document-library-lite' ) . ' ' . Lib_Util::barn2_link( 'kb/document-library-wordpress-documentation/#document-tables', '', true ),
					'default' => 'id,title,content,image,date,doc_categories,link',
				],
			]
		);
	}

	/**
	 * Get the Sort By settings.
	 *
	 * @return array
	 */
	private function get_sort_by_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'   => __( 'Sort by', 'document-library-lite' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[sort_by]',
					'options' => [
						'title'      => __( 'Title', 'document-library-lite' ),
						'id'         => __( 'ID', 'document-library-lite' ),
						'date'       => __( 'Date published', 'document-library-lite' ),
						'modified'   => __( 'Date modified', 'document-library-lite' ),
						'menu_order' => __( 'Page order (menu order)', 'document-library-lite' ),
						'author'     => __( 'Author', 'document-library-lite' ),
						'rand'       => __( 'Random', 'document-library-lite' ),
					],
					'desc'    => __( 'The initial sort order of the document library.', 'document-library-lite' ) . ' ' . Lib_Util::barn2_link( 'kb/document-library-wordpress-documentation/#general-tab', '', true ),
					'default' => $this->default_settings['sort_by'],
				],
				[
					'title'   => __( 'Sort direction', 'document-library-lite' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[sort_order]',
					'options' => [
						''     => __( 'Automatic', 'document-library-lite' ),
						'asc'  => __( 'Ascending (A to Z, oldest to newest)', 'document-library-lite' ),
						'desc' => __( 'Descending (Z to A, newest to oldest)', 'document-library-lite' ),
					],
					'default' => $this->default_settings['sort_order'],
				],
			]
		);
	}

	/**
	 * Get the Download Button settings.
	 *
	 * @return array
	 */
	private function get_download_button_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'   => __( 'Button behavior', 'document-library-lite' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[link_destination]',
					'desc'    => __( 'What happens when someone clicks on a link to a document.', 'document-library-lite' ),
					'options' => [
						'direct' => __( 'Direct access', 'document-library-lite' ),
						'single' => __( 'Open single document page', 'document-library-lite' ),
					],
					'default' => 'direct',
				],
				[
					'title'   => __( 'Style', 'document-library-lite' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[link_style]',
					'desc'    => __( 'Control the appearance of the link to the document.', 'document-library-lite' ) . ' ' . Lib_Util::barn2_link( 'kb/document-library-settings#download-button', '', true ),
					'options' => [
						'button' => __( 'Button', 'document-library-lite' ),
						'icon'   => __( 'File type button', 'document-library-lite' ),
						'text'   => __( 'Text link', 'document-library-lite' ),
					],
					'default' => $this->default_settings['link_style'],
				],
				[
					'id'      => Options::SHORTCODE_OPTION_KEY . '[link_text]',
					'title'   => __( 'Button/link text', 'document-library-lite' ),
					'type'    => 'text',
					'desc'    => __( 'The text displayed on the button or link.', 'document-library-lite' ),
					'default' => $this->default_settings['link_text'],
					'custom_attributes' => [
						'data-show-if' => 'link_style',
						'data-show-if-value' => 'button,text',
					],
				],
				[
					'title'            => __( 'Icon', 'document-library-lite' ),
					'type'             => 'checkbox',
					'id'               => Options::SHORTCODE_OPTION_KEY . '[link_icon]',
					'label'            => __( 'Display download icon', 'document-library-lite' ),
					'default'          => false,
					'class'            => 'dll-link-icon-option',
					'custom_attributes' => [
						'data-show-if' => 'link_style',
						'data-show-if-value' => 'button,text',
					],
				],
				[
					'title'   => __( 'New tab', 'document-library-lite' ),
					'type'    => 'checkbox',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[link_target]',
					'label'   => __( 'Open download in a new tab', 'document-library-lite' ),
					'default' => false,
				],
			]
		);
	}

	/**
	 * Get the Preview Button settings.
	 *
	 * @return array
	 */
	private function get_preview_button_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'   => __( 'Enable preview?', 'document-library-lite' ),
					'type'    => 'checkbox',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[preview]',
					'label'   => __( 'Show a preview button for supported file types', 'document-library-lite' ),
					'desc'    => __( 'The preview option will appear for supported file types only.', 'document-library-lite' ) . ' ' . Lib_Util::barn2_link( 'kb/document-preview/', '', true ),
					'default' => false,
				],
				[
					'title'   => __( 'Style', 'document-library-lite' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[preview_style]',
					'desc'    => __( 'Control the appearance of the preview option.', 'document-library-lite' ) . ' ' . Lib_Util::barn2_link( 'kb/document-preview/#preview-style', '', true ),
					'options' => [
						'button'           => __( 'Button with text', 'document-library-lite' ),
						'button_icon_text' => __( 'Button with icon and text', 'document-library-lite' ),
						'button_icon'      => __( 'Button with icon', 'document-library-lite' ),
						'icon_only'        => __( 'Icon only', 'document-library-lite' ),
						'link'             => __( 'Text link', 'document-library-lite' ),
					],
					'default' => 'button_with_icon',
				],
				[
					'title'   => __( 'Button/link text', 'document-library-lite' ),
					'type'    => 'text',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[preview_text]',
					'desc'    => __( 'The text displayed on the preview button or link.', 'document-library-lite' ),
					'default' => __( 'Preview', 'document-library-lite' ),
				],
			]
		);
	}

	/**
	 * Get the Folders settings.
	 *
	 * @return array
	 */
	private function get_folders_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'   => __( 'Enable folders', 'document-library-lite' ),
					'type'    => 'checkbox',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[folders]',
					'label'   => __( 'Group the document library into folders, one per category', 'document-library-lite' ),
					'default' => false,
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
