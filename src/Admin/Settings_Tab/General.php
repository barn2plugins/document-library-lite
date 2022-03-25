<?php

namespace Barn2\Plugin\Document_Library\Admin\Settings_Tab;

use Barn2\DLW_Lib\Registerable,
	Barn2\DLW_Lib\Admin\Settings_API_Helper,
	Barn2\DLW_Lib\Util as Lib_Util,
	Barn2\Plugin\Document_Library\Util\Options;

defined( 'ABSPATH' ) || exit;

/**
 * General Setting Tab
 *
 * @package   Barn2/document-library-for-wordpress
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class General implements Registerable {
	const TAB_ID       = 'general';
	const OPTION_GROUP = 'document_library_pro_general';
	const MENU_SLUG    = 'dlp-settings-general';

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin           = $plugin;
		$this->id               = 'general';
		$this->title            = __( 'General', 'document-library-for-wordpress' );
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

		// Support Links
		Settings_API_Helper::add_settings_section( 'dlp_support_links', self::MENU_SLUG, '', [ $this, 'support_links' ], [] );

		// Document Data
		Settings_API_Helper::add_settings_section( 'dlp_general_fields', self::MENU_SLUG, __( 'Document data', 'document-library-for-wordpress' ), [ $this, 'display_document_data_description' ], $this->get_document_data_settings() );

		// Document Lists
		Settings_API_Helper::add_settings_section( 'dlp_shared_fields', self::MENU_SLUG, __( 'Document lists', 'document-library-for-wordpress' ), [ $this, 'display_document_lists_description' ], $this->get_document_lists_settings() );

		// Document Links
		Settings_API_Helper::add_settings_section( 'dlp_links', self::MENU_SLUG, __( 'Document links', 'document-library-for-wordpress' ), '__return_false', $this->get_document_links_settings() );

		// Document Preview
		Settings_API_Helper::add_settings_section( 'dlp_preview', self::MENU_SLUG, __( 'Document preview', 'document-library-for-wordpress' ), '__return_false', $this->get_document_preview_settings() );

		// Library Content
		Settings_API_Helper::add_settings_section( 'dlp_library_content', self::MENU_SLUG, __( 'Library content', 'document-library-for-wordpress' ), '__return_false', $this->get_library_content_settings() );

		// Library Controls
		Settings_API_Helper::add_settings_section( 'dlp_table_controls', self::MENU_SLUG, __( 'Document library controls', 'document-library-for-wordpress' ), '__return_false', $this->get_library_controls_settings() );

		// Document Limits
		Settings_API_Helper::add_settings_section( 'dlp_document_limits', self::MENU_SLUG, __( 'Number of documents', 'document-library-for-wordpress' ), '__return_false', $this->get_document_limit_settings() );

		// Document Sorting
		Settings_API_Helper::add_settings_section( 'dlp_document_sorting', self::MENU_SLUG, __( 'Sorting', 'document-library-for-wordpress' ), '__return_false', $this->get_document_sorting_settings() );
	}

	/**
	 * Output the Document Data description.
	 */
	public function display_document_data_description() {
		printf(
			'<p>' .
			esc_html__( 'Use the following options to manage the fields that are used to store information about your documents.', 'document-library-for-wordpress' ) .
			'</p>'
		);
	}

	/**
	 * Output the Document Lists description.
	 */
	public function display_document_lists_description() {
		printf(
			'<p>' .
			/* translators: %1: knowledge base link start, %2: knowledge base link end */
			esc_html__( 'These options set defaults for all your document libraries and are used for the table layout. You can override them in the shortcode for individual libraries. %1$sRead more%2$s.', 'document-library-for-wordpress' ) .
			'</p>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::format_link_open( Lib_Util::barn2_url( 'kb/document-library-wordpress-documentation/#general-tab' ), true ),
			'</a>'
		);
	}

	/**
	 * Output the Barn2 Support Links.
	 */
	public function support_links() {
		printf(
			'<p>%s | %s | %s</p>',
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::format_link( $this->plugin->get_documentation_url(), __( 'Documentation', 'document-library-for-wordpress' ), true ),
			Lib_Util::format_link( 'https://barn2.com/wordpress-plugins/document-library-free-support-request/', __( 'Support', 'document-library-for-wordpress' ), true ),
			sprintf(
				'<a class="barn2-wiz-restart-btn" href="%s">%s</a>',
				add_query_arg( [ 'page' => $this->plugin->get_slug() . '-setup-wizard' ], admin_url( 'admin.php' ) ),
				__( 'Setup wizard', 'document-library-for-wordpress' )
			)
			// phpcs:enable
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
				'id'                => Options::DOCUMENT_FIELDS_OPTION_KEY,
				'title'             => __( 'Document fields', 'document-library-for-wordpress' ) .
				sprintf( '<span class="pro-version">%s</span>', Lib_Util::barn2_link( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings', __( 'Pro version only', 'document-library-for-wordpress' ), true ) ),
				'type'              => 'multicheckbox',
				'options'           => [
					'editor'    => __( 'Content', 'document-library-for-wordpress' ),
					'excerpt'   => __( 'Excerpt', 'document-library-for-wordpress' ),
					'thumbnail' => __( 'Featured Image', 'document-library-for-wordpress' ),
					'comments'  => __( 'Comments', 'document-library-for-wordpress' ),
				],
				'default'           => [
					'editor'    => '1',
					'excerpt'   => '0',
					'thumbnail' => '1',
					'comments'  => '0',
				],
				'field_class'       => 'readonly',
				'custom_attributes' => [
					'disabled' => 'disabled'
				]
			],
		];
	}

	/**
	 * Get the Document Lists settings.
	 *
	 * @return array
	 */
	private function get_document_lists_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'id'       => Options::DOCUMENT_PAGE_OPTION_KEY,
					'title'    => __( 'Document library page', 'document-library-for-wordpress' ),
					'type'     => 'select',
					'desc'     => __( 'The page to display your documents.', 'document-library-for-wordpress' ),
					'desc_tip' => __( 'You can also use the [doc_library] shortcode to list documents on other pages.', 'document-library-for-wordpress' ),
					'options'  => $this->get_pages(),
					'default'  => '',
				],
				[
					'id'      => Options::SHORTCODE_OPTION_KEY . '[layout]',
					'title'   => __( 'Default layout', 'document-library-for-wordpress' ),
					'type'    => 'radio',
					'options' => [
						'table' => __( 'Table', 'document-library-for-wordpress' ),
						'grid'  => __( 'Grid', 'document-library-for-wordpress' ),
					],
					'default' => 'table',
				],
				[
					'title'   => __( 'Folders', 'document-library-for-wordpress' ),
					'type'    => 'checkbox',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[folders]',
					'label'   => __( 'Display the document library in folders', 'document-library-for-wordpress' ),
					'default' => false
				],
			]
		);
	}

	/**
	 * Get the Document Links settings.
	 *
	 * @return array
	 */
	private function get_document_links_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'   => __( 'Link to document', 'document-library-for-wordpress' ),
					'type'    => 'checkbox',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[document_link]',
					'label'   => __( 'Include a link to the document.', 'document-library-for-wordpress' ),
					'desc'    => __( 'Use the \'Link destination\' option below to control the link behavior.', 'document-library-for-wordpress' ) . $this->read_more( 'kb/document-library-settings/#link-to-document' ),
					'default' => true,
				],
				[
					'title'   => __( 'Link style', 'document-library-for-wordpress' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[link_style]',
					'desc'    => __( 'Control the appearance of the link to the document.', 'document-library-for-wordpress' ) . $this->read_more( 'kb/document-library-settings#link-style' ),
					'options' => [
						'button'           => __( 'Button with text', 'document-library-for-wordpress' ),
						'button_icon_text' => __( 'Button with icon and text', 'document-library-for-wordpress' ),
						'button_icon'      => __( 'Button with icon', 'document-library-for-wordpress' ),
						'icon_only'        => __( 'Download icon only', 'document-library-for-wordpress' ),
						'icon'             => __( 'File type icon', 'document-library-for-wordpress' ),
						'text'             => __( 'Text link', 'document-library-for-wordpress' ),
					],
					'default' => 'button_with_text'
				],
				[
					'id'      => Options::SHORTCODE_OPTION_KEY . '[link_text]',
					'title'   => __( 'Link text', 'document-library-for-wordpress' ),
					'type'    => 'text',
					'desc'    => __( 'The text displayed on the button or link.', 'document-library-for-wordpress' ),
					'default' => $this->default_settings['link_text'],
				],
				[
					'title'   => __( 'Link destination', 'document-library-for-wordpress' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[link_destination]',
					'desc'    => __( 'What happens when someone clicks on a link to a document.', 'document-library-for-wordpress' ) . $this->read_more( 'kb/document-library-settings#link-destination' ),
					'options' => [
						'direct' => __( 'Direct access', 'document-library-for-wordpress' ),
						'single' => __( 'Open single document page', 'document-library-for-wordpress' ),
					],
					'default' => 'direct_access'
				],
				[
					'title'   => __( 'Link target', 'document-library-for-wordpress' ),
					'type'    => 'checkbox',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[link_target]',
					'label'   => __( 'Open links in a new tab', 'document-library-for-wordpress' ),
					'default' => false
				],
				[
					'id'      => Options::SHORTCODE_OPTION_KEY . '[links]',
					'title'   => __( 'Clickable fields', 'document-library-for-wordpress' ), // note this in 'links' in PTP
					'type'    => 'text',
					'desc'    => __( 'Control which fields are clickable, in addition to the \'link\' field.', 'document-library-for-wordpress' ) . $this->read_more( 'kb/document-library-settings#clickable-fields' ),
					'default' => 'title,doc_categories,author',
				],
			]
		);
	}

	/**
	 * Get the Document Preview settings.
	 *
	 * @return array
	 */
	public function get_document_preview_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'   => __( 'Document preview', 'document-library-for-wordpress' ),
					'type'    => 'checkbox',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[preview]',
					'label'   => __( 'Allow users to preview documents in a lightbox', 'document-library-for-wordpress' ),
					'desc'    => __( 'The preview option will appear for supported file types only.', 'document-library-for-wordpress' ) . $this->read_more( 'kb/document-preview/' ),
					'default' => false
				],
				[
					'title'   => __( 'Preview style', 'document-library-for-wordpress' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[preview_style]',
					'desc'    => __( 'Control the appearance of the preview option.', 'document-library-for-wordpress' ) . $this->read_more( 'kb/document-preview/#preview-style' ),
					'options' => [
						'button'           => __( 'Button with text', 'document-library-for-wordpress' ),
						'button_icon_text' => __( 'Button with icon and text', 'document-library-for-wordpress' ),
						'button_icon'      => __( 'Button with icon', 'document-library-for-wordpress' ),
						'icon_only'        => __( 'Icon only', 'document-library-for-wordpress' ),
						'link'             => __( 'Text link', 'document-library-for-wordpress' ),
					],
					'default' => 'button_with_icon'
				],
				[
					'title'   => __( 'Preview text', 'document-library-for-wordpress' ),
					'type'    => 'text',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[preview_text]',
					'desc'    => __( 'The text displayed on the preview button or link.', 'document-library-for-wordpress' ),
					'default' => __( 'Preview', 'document-library-for-wordpress' )
				],
			]
		);
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
					'id'      => Options::SHORTCODE_OPTION_KEY . '[lightbox]',
					'title'   => __( 'Image lightbox', 'document-library-for-wordpress' ),
					'type'    => 'checkbox',
					'label'   => __( 'Display images in a lightbox when opened', 'document-library-for-wordpress' ),
					'default' => $this->default_settings['lightbox'],
				],
				[
					'title'   => __( 'Shortcodes', 'document-library-for-wordpress' ),
					'type'    => 'checkbox',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[shortcodes]',
					'label'   => __( 'Display shortcodes, HTML and other formatting in the document content, excerpt and custom field columns', 'document-library-for-wordpress' ),
					'default' => false
				],
				[
					'id'                => Options::SHORTCODE_OPTION_KEY . '[excerpt_length]',
					'title'             => __( 'Excerpt length', 'document-library-for-wordpress' ),
					'type'              => 'number',
					'class'             => 'small-text',
					'suffix'            => __( 'words', 'document-library-for-wordpress' ),
					'desc'              => __( 'Enter -1 to show the full excerpt.', 'document-library-for-wordpress' ),
					'default'           => -1,
					'custom_attributes' => [
						'min' => -1
					]
				],
				[
					'id'                => Options::SHORTCODE_OPTION_KEY . '[content_length]',
					'title'             => __( 'Content length', 'document-library-for-wordpress' ),
					'type'              => 'number',
					'class'             => 'small-text',
					'suffix'            => __( 'words', 'document-library-for-wordpress' ),
					'desc'              => __( 'Enter -1 to show the full content.', 'document-library-for-wordpress' ),
					'default'           => 15,
					'custom_attributes' => [
						'min' => -1
					]
				],
			]
		);
	}

	/**
	 * Get the Library Control settings.
	 *
	 * @return array
	 */
	private function get_library_controls_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'   => __( 'Search box', 'document-library-for-wordpress' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[search_box]',
					'desc'    => __( 'The position of the search box above the list of documents. You can also add a search box using a shortcode or widget.', 'document-library-for-wordpress' ) . $this->read_more( 'kb/document-library-search/#standalone-search-box' ),
					'options' => [
						'top'    => __( 'Above library', 'document-library-for-wordpress' ),
						'bottom' => __( 'Below library', 'document-library-for-wordpress' ),
						'both'   => __( 'Above and below library', 'document-library-for-wordpress' ),
						'false'  => __( 'Hidden', 'document-library-for-wordpress' )
					],
					'default' => 'top'
				],
				[
					'title'   => __( 'Reset button', 'document-library-for-wordpress' ),
					'type'    => 'checkbox',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[reset_button]',
					'label'   => __( 'Show the reset button above the library', 'document-library-for-wordpress' ),
					'default' => false
				],
			]
		);
	}

	/**
	 * Get the Document Limit settings.
	 *
	 * @return array
	 */
	private function get_document_limit_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'             => __( 'Documents per page', 'document-library-for-wordpress' ),
					'type'              => 'number',
					'id'                => Options::SHORTCODE_OPTION_KEY . '[rows_per_page]',
					'desc'              => __( 'The number of documents per page of the document library. Enter -1 to display all documents on one page.', 'document-library-for-wordpress' ),
					'default'           => $this->default_settings['rows_per_page'],
					'custom_attributes' => [
						'min' => -1
					]
				],
				[
					'title'   => __( 'Pagination type', 'document-library-for-wordpress' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[paging_type]',
					'options' => [
						'numbers'        => __( 'Numbers only', 'document-library-for-wordpress' ),
						'simple'         => __( 'Prev|Next', 'document-library-for-wordpress' ),
						'simple_numbers' => __( 'Prev|Next + Numbers', 'document-library-for-wordpress' ),
						'full'           => __( 'Prev|Next|First|Last', 'document-library-for-wordpress' ),
						'full_numbers'   => __( 'Prev|Next|First|Last + Numbers', 'document-library-for-wordpress' )
					],
					'default' => 'simple_numbers'
				],
				[
					'title'   => __( 'Pagination position', 'document-library-for-wordpress' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[pagination]',
					'options' => [
						'top'    => __( 'Above library', 'document-library-for-wordpress' ),
						'bottom' => __( 'Below library', 'document-library-for-wordpress' ),
						'both'   => __( 'Above and below library', 'document-library-for-wordpress' ),
						'false'  => __( 'Hidden', 'document-library-for-wordpress' )
					],
					'desc'    => __( 'The position of the paging buttons which scroll between results.', 'document-library-for-wordpress' ),
					'default' => 'bottom'
				],
				[
					'title'   => __( 'Totals', 'document-library-for-wordpress' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[totals]',
					'options' => [
						'top'    => __( 'Above library', 'document-library-for-wordpress' ),
						'bottom' => __( 'Below library', 'document-library-for-wordpress' ),
						'both'   => __( 'Above and below library', 'document-library-for-wordpress' ),
						'false'  => __( 'Hidden', 'document-library-for-wordpress' )
					],
					'desc'    => __( "The position of the document total, e.g. '25 documents'.", 'document-library-for-wordpress' ),
					'default' => 'bottom'
				],
			]
		);
	}

	/**
	 * Get the Document Sorting settings.
	 *
	 * @return array
	 */
	private function get_document_sorting_settings() {
		return Options::mark_readonly_settings(
			[
				[
					'title'   => __( 'Sort by', 'document-library-for-wordpress' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[sort_by]',
					'options' => [
						'title'      => __( 'Title', 'document-library-for-wordpress' ),
						'id'         => __( 'ID', 'document-library-for-wordpress' ),
						'date'       => __( 'Date published', 'document-library-for-wordpress' ),
						'modified'   => __( 'Date modified', 'document-library-for-wordpress' ),
						'menu_order' => __( 'Page order (menu order)', 'document-library-for-wordpress' ),
						'author'     => __( 'Author', 'document-library-for-wordpress' ),
						'rand'       => __( 'Random', 'document-library-for-wordpress' ),
					],
					'desc'    => __( 'The initial sort order of the document library.', 'document-library-for-wordpress' ) . $this->read_more( 'kb/document-library-wordpress-documentation/#general-tab' ),
					'default' => $this->default_settings['sort_by'],
				],
				[
					'title'   => __( 'Sort direction', 'document-library-for-wordpress' ),
					'type'    => 'select',
					'id'      => Options::SHORTCODE_OPTION_KEY . '[sort_order]',
					'options' => [
						''     => __( 'Automatic', 'document-library-for-wordpress' ),
						'asc'  => __( 'Ascending (A to Z, oldest to newest)', 'document-library-for-wordpress' ),
						'desc' => __( 'Descending (Z to A, newest to oldest)', 'document-library-for-wordpress' )
					],
					'default' => $this->default_settings['sort_order']
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

	/**
	 * Get a Read more KB link.
	 *
	 * @param string $path
	 * @return string
	 */
	private function read_more( $path ) {
		return ' ' . Lib_Util::barn2_link( $path );
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
}
