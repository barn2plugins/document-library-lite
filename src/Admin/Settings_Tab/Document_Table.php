<?php

namespace Barn2\Plugin\Document_Library\Admin\Settings_Tab;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Admin\Settings_API_Helper;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;
use	Barn2\Plugin\Document_Library\Util\Options;

/**
 * Document Table Setting Tab
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Document_Table implements Registerable {
	const TAB_ID       = 'document_libraries';
	const OPTION_GROUP = 'document_library_pro_table';
	const MENU_SLUG    = 'dlp-settings-libraries';

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
		$this->id               = 'document_libraries';
		$this->title            = __( 'Document Tables', 'document-library-lite' );
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

		Settings_API_Helper::add_settings_section( 'dlp_grid_title', self::MENU_SLUG, '', [ $this, 'table_content_description' ], [] );

		// Table Content section.
		Settings_API_Helper::add_settings_section( 'dlp_shortcode_defaults', self::MENU_SLUG, __( 'Library content', 'document-library-lite' ), '__return_false', $this->get_library_content_settings() );

		// Document links
		Settings_API_Helper::add_settings_section( 'dlp_links', self::MENU_SLUG, __( 'Document links', 'document-library-lite' ), '__return_false', $this->get_document_link_settings() );

		// Loading Posts section.
		Settings_API_Helper::add_settings_section( 'dlp_post_loading', self::MENU_SLUG, __( 'Loading & performance', 'document-library-lite' ), '__return_false', $this->get_performance_settings() );

		// Table Controls section.
		Settings_API_Helper::add_settings_section( 'dlp_table_controls', self::MENU_SLUG, __( 'Document library controls', 'document-library-lite' ), '__return_false', $this->get_table_controls_settings() );

		// Table design.
		Settings_API_Helper::add_settings_section( 'dlp_design', self::MENU_SLUG, __( 'Design', 'document-library-lite' ), [ $this, 'display_table_design_description' ], $this->get_design_settings() );
	}

	/**
	 * Get the Library Content settings.
	 *
	 * @return array
	 */
	private function get_library_content_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'id'      => Options::SHORTCODE_OPTION_KEY . '[columns]',
					'title'   => __( 'Columns', 'document-library-lite' ),
					'type'    => 'text',
					'desc'    => __( 'Enter the fields to include in your document tables.', 'document-library-lite' ) . $this->read_more( 'kb/document-library-wordpress-documentation/#document-tables' ),
					'default' => 'id,title,content,image,date,doc_categories,link'
				],
				[
					'id'      => Options::SHORTCODE_OPTION_KEY . '[image_size]',
					'title'   => __( 'Image size', 'document-library-lite' ),
					'type'    => 'text',
					'desc'    => __( 'Enter WxH in pixels (e.g. 80x80).', 'document-library-lite' ) . $this->read_more( 'kb/document-library-image-options/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings#image-size' ),
					'default' => '70x70',
				],
			]
		);
	}

	/**
	 * Get the Doocument Link settings.
	 *
	 * @return array
	 */
	private function get_document_link_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'   => __( 'Accessing documents', 'document-library-lite' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[accessing_documents]',
					'desc'    => __( 'How a user accesses documents from the ‘link’ column.', 'document-library-lite' ) . $this->read_more( 'kb/document-library-settings/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings#accessing-documents' ),
					'options' => [
						'link'     => __( 'Link to document', 'document-library-lite' ),
						'checkbox' => __( 'Multi-select checkboxes', 'document-library-lite' ),
						'both'     => __( 'Both', 'document-library-lite' ),
					],
					'default' => 'link'
				],
				[
					'title'   => __( 'Multi-download button', 'document-library-lite' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[multi_download_button]',
					'desc'    => __( 'The position of the button to download all selected documents.', 'document-library-lite' ),
					'options' => [
						'below' => __( 'Below document library', 'document-library-lite' ),
						'above' => __( 'Above document library', 'document-library-lite' ),
						'both'  => __( 'Both', 'document-library-lite' ),
					],
					'default' => 'above'
				],
				[
					'title'   => __( 'Multi-download button text', 'document-library-lite' ),
					'type'    => 'text',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[multi_download_text]',
					'desc'    => __( 'The text for the button to download all selected documents.', 'document-library-lite' ),
					'default' => __( 'Download Selected Documents', 'document-library-lite' ),
				],
			]
		);
	}

	/**
	 * Get the Performance settings.
	 *
	 * @return array
	 */
	private function get_performance_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'             => __( 'Lazy load', 'document-library-lite' ),
					'type'              => 'checkbox',
					'id'                => Options::SHORTCODE_OPTION_KEY . '[lazy_load]',
					'label'             => __( 'Load the document table one page at a time', 'document-library-lite' ),
					'desc'              => __( 'Enable this if you have many documents or experience slow page load times.', 'document-library-lite' ) . '<br/>' .
					__( 'Warning: Lazy load limits the searching and sorting features in the document library. Only use it if you definitely need it.', 'document-library-lite' ) .
					$this->read_more( 'kb/document-library-lazy-load/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings' ),
					'default'           => $this->default_settings['lazy_load'],
					'class'             => 'dlp-toggle-parent',
					'custom_attributes' => [
						'data-child-class' => 'post-limit',
						'data-toggle-val'  => 0
					]
				],
				[
					'title'             => __( 'Document limit', 'document-library-pro' ),
					'type'              => 'number',
					'id'                => Options::SHORTCODE_OPTION_KEY . '[post_limit]',
					'desc'              => __( 'The maximum number of documents to display in each table. Enter -1 to show all documents.', 'document-library-pro' ),
					'default'           => $this->default_settings['post_limit'],
					'class'             => 'small-text post-limit',
					'custom_attributes' => [
						'min' => -1
					]
				],
				[
					'title'             => __( 'Caching', 'document-library-lite' ),
					'type'              => 'checkbox',
					'id'                => Options::SHORTCODE_OPTION_KEY . '[cache]',
					'label'             => __( 'Cache document libraries to improve load time', 'document-library-lite' ),
					'default'           => false,
					'class'             => 'toggle-parent',
					'custom_attributes' => [
						'data-child-class' => 'expires-after'
					]
				],
				[
					'title'             => __( 'Cache expires after', 'document-library-lite' ),
					'type'              => 'number',
					'id'                => Options::MISC_OPTION_KEY . '[cache_expiry]',
					'suffix'            => __( 'hours', 'document-library-lite' ),
					'desc'              => __( 'Your table data will be refreshed after this length of time.', 'document-library-lite' ),
					'default'           => 6,
					'class'             => 'expires-after',
					'custom_attributes' => [
						'min' => 1,
						'max' => 9999
					]
				],
			]
		);
	}

	/**
	 * Get the Table Controls settings.
	 *
	 * @return array
	 */
	private function get_table_controls_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'             => __( 'Search filters', 'document-library-lite' ),
					'type'              => 'select',
					'id'                => Options::SHORTCODE_OPTION_KEY . '[filters]',
					'options'           => [
						'false'  => __( 'Disabled', 'document-library-lite' ),
						'true'   => __( 'Show based on columns in table', 'document-library-lite' ),
						'custom' => __( 'Custom', 'document-library-lite' )
					],
					'desc'              => __( 'Show dropdown menus to filter by doc_categories, doc_tags, or custom taxonomy.', 'document-library-lite' ) . $this->read_more( 'kb/document-library-filters/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings' ),
					'default'           => 'false',
					'class'             => 'toggle-parent',
					'custom_attributes' => [
						'data-child-class' => 'custom-search-filter',
						'data-toggle-val'  => 'custom'
					]
				],
				[
					'title'   => __( 'Page length', 'document-library-lite' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[page_length]',
					'options' => [
						'top'    => __( 'Above library', 'document-library-lite' ),
						'bottom' => __( 'Below library', 'document-library-lite' ),
						'both'   => __( 'Above and below library', 'document-library-lite' ),
						'false'  => __( 'Hidden', 'document-library-lite' )
					],
					'desc'    => __( "The position of the 'Show [x] entries' dropdown list.", 'document-library-lite' ),
					'default' => 'above'
				],
			]
		);
	}

	/**
	 * Get the Table Design settings.
	 *
	 * @return string
	 */
	private function get_design_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'id'                => Options::MISC_OPTION_KEY . '[design]',
					'title'             => __( 'Design', 'document-library-lite' ),
					'type'              => 'radio',
					'options'           => [
						'default' => __( 'Default', 'document-library-lite' ),
						'custom'  => __( 'Custom', 'document-library-lite' ),
					],
					'default'           => 'default',
					'class'             => 'toggle-parent',
					'custom_attributes' => [
						'data-child-class' => 'custom-design',
						'data-toggle-val'  => 'custom'
					]
				]
			]
		);
	}

	/**
	 * Output the Table Design description.
	 */
	public function display_table_design_description() {
		?>
		<p><?php esc_html_e( 'Customize the design of the document tables.', 'document-library-lite' ); ?></p>
		<?php
	}

	/**
	 * Output the Table Content description.
	 */
	public function table_content_description() {
		printf(
			'<p>' .
			/* translators: %1: knowledge base link start, %2: knowledge base link end */
			esc_html__( 'The following options are used when documents are listed in a table layout. You can override them in the [doc_library] shortcode. See the %1$sknowledge base%2$s for details of how to configure your document tables even further.', 'document-library-lite' ) .
			'</p>',
             // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::format_link_open( Lib_Util::barn2_url( 'kb/document-library-wordpress-documentation/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings' ), true ),
			'</a>'
		);
	}

	/**
	 * Get a Read more KB link.
	 *
	 * @param string $path
	 * @return string
	 */
	private function read_more( $path ) {
		return ' ' . Lib_Util::barn2_link( $path, '', true );
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
