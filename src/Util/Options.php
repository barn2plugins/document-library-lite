<?php
namespace Barn2\Plugin\Document_Library\Util;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;

/**
 * Settings Options Utilities
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class Options {

	const DOCUMENT_FIELDS_OPTION_KEY = 'dlp_document_fields';
	const DOCUMENT_PAGE_OPTION_KEY   = 'dlp_document_page';
	const SHORTCODE_OPTION_KEY       = 'dlp_shortcode_defaults';
	const MISC_OPTION_KEY            = 'dlp_misc_settings';

	const GENERAL_OPTION_GROUP = 'document_library_pro_general';
	const TABLE_OPTION_GROUP   = 'document_library_pro_table';
	const GRID_OPTION_GROUP    = 'document_library_pro_grid';
	const SINGLE_OPTION_GROUP  = 'document_library_pro_single_document';

	/**
	 * The list of readonly settings.
	 *
	 * @var array
	 */
	public static $readonly_settings = [
		'layout',
		'folders',
		'document_link',
		'link_style',
		'link_destination',
		'link_target',
		'links',
		'preview',
		'preview_style',
		'preview_text',
		'image_size',
		'shortcodes',
		'excerpt_length',
		'content_length',
		'lazy_load',
		'post_limit',
		'cache',
		'cache_expiry',
		'filters',
		'page_length',
		'search_box',
		'totals',
		'pagination',
		'paging_type',
		'reset_button',
		'accessing_documents',
		'multi_download_button',
		'multi_download_text',
		'design'
	];

	/**
	 * Retrieve the shortcode option from the DB
	 *
	 * @return array
	 */
	public static function get_shortcode_option() {
		return get_option( self::SHORTCODE_OPTION_KEY, [] );
	}

	/**
	 * Update the shortcode.
	 *
	 * @param array $values
	 * @return bool
	 */
	public static function update_shortcode_option( $values = [] ) {
		if ( ! is_array( $values ) || empty( $values ) ) {
			return false;
		}

		$options = wp_parse_args( get_option( self::SHORTCODE_OPTION_KEY ), self::get_default_settings() );

		$allowed_keys = array_keys( self::get_default_settings() );

		foreach ( $values as $key => $value ) {
			if ( ! in_array( $key, $allowed_keys, true ) ) {
				unset( $values[ $key ] );
			}
		}

		update_option( self::SHORTCODE_OPTION_KEY, array_merge( $options, $values ) );

		return true;
	}

	/**
	 * Retrieves the shortcode options with the core defaults.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return wp_parse_args( self::get_shortcode_option(), self::get_default_settings() );
	}

	/**
	 * Retrieves the default args specific to DLP (as opposed to the PTP defaults)
	 *
	 * @return string[]
	 */
	public static function get_default_settings() {
		$default_settings = [
			'link_text'       => __( 'Download', 'document-library-lite' ),
			'lightbox'        => false,
			'rows_per_page'   => 20,
			'sort_by'         => 'date',
			'sort_order'      => '',
			'columns'         => 'id,title,content,image,date,doc_categories,link',
			'doc_category'    => '',
			'content_length'  => 15,
			'date_format'     => 'Y/m/d',
			'search_on_click' => true,
			'wrap'            => true,
			'content_length'  => 15,
			'scroll_offset'   => 15
		];

		return $default_settings;
	}

	/**
	 * Mark a subsetting as read-only.
	 *
	 * @param array $settings
	 * @return array
	 */
	public static function mark_readonly_settings( $settings ) {
		foreach ( $settings as &$setting ) {
			$subkey = preg_filter( '/^[\w\[\]]+\[(\w+)\]$/', '$1', $setting['id'] );

			if ( $subkey && false !== array_search( $subkey, self::$readonly_settings, true ) ) {
				$setting['field_class']       = isset( $setting['field_class'] ) && strlen( $setting['field_class'] ) > 0 ? $setting['field_class'] . ' readonly' : 'readonly';
				$setting['custom_attributes'] = isset( $setting['custom_attributes'] ) && is_array( $setting['custom_attributes'] ) ? array_merge( $setting['custom_attributes'], [ 'disabled' => 'disabled' ] ) : [ 'disabled' => 'disabled' ];

				$setting['title'] = $setting['title'] .
					sprintf( '<span class="pro-version">%s</span>', Lib_Util::barn2_link( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings', __( 'Pro version only', 'document-library-lite' ), true ) );
			}
		}

		return $settings;
	}

	/**
	 * Handles aliases for shortcode attributes.
	 *
	 * @param array $atts
	 * @return array
	 */
	public static function handle_shortcode_attribute_aliases( $atts ) {
		if ( isset( $atts['content'] ) ) {
			$atts['columns'] = $atts['content'];
			unset( $atts['content'] );
		}

		if ( isset( $atts['docs_per_page'] ) ) {
			$atts['rows_per_page'] = $atts['docs_per_page'];
			unset( $atts['docs_per_page'] );
		}

		return $atts;
	}

	/**
	 * Converts a string list to an array.
	 *
	 * @param mixed $arg
	 * @return array
	 */
	public static function string_list_to_array( $arg ) {
		if ( is_array( $arg ) ) {
			return $arg;
		}
		return array_filter( array_map( 'trim', explode( ',', $arg ) ) );
	}

	/**
	 * Retrieve an option.
	 *
	 * @param string $option
	 * @param mixed $default
	 * @return mixed
	 */
	private static function get_option( $option, $default ) {
		$value = get_option( $option, $default );

		if ( empty( $value ) || ( is_array( $default ) && ! is_array( $value ) ) ) {
			$value = $default;
		}

		if ( is_array( $value ) && is_array( $default ) ) {
			$value = array_merge( $default, $value );
		}

		return $value;
	}

}