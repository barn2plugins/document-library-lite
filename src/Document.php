<?php

namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Util\SVG_Icon;

/**
 * Document Controller
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Document {
	/**
	 * ID
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Post object
	 *
	 * @var \WP_Post
	 */
	protected $post_object;

	/**
	 * Constructor
	 *
	 * @param integer   $id
	 */
	public function __construct( $id = 0 ) {
		$this->fetch_document( $id );
	}

	/**
	 * Fetch and setup existing document
	 *
	 * @param int $id
	 */
	protected function fetch_document( $id ) {
		$this->post_object = get_post( $id, 'object' );

		if ( is_null( $this->post_object ) ) {
			throw new \Exception( __( 'Document does not exist', 'document-library-lite' ) );
		}

		$this->id = $this->post_object->ID;
	}

	/**
	 * Retrieves meta data from the post
	 *
	 * @param   string $key
	 * @return  string
	 */
	public function get_meta_data( $key ) {
		return get_post_meta( $this->id, $key, true );
	}

	/**
	 * Sets the document link data
	 *
	 * @param   string  $type 'file' | 'none
	 * @param   array   $data Should contain 'file_id' for 'file'
	 */
	public function set_document_link( $type, $data = [] ) {
		update_post_meta( $this->id, '_dlp_document_link_type', $type );

		switch ( $type ) {
			case 'none':
				if ( $this->get_file_id() && is_numeric( $this->get_file_id() ) ) {
					wp_set_object_terms( $this->get_file_id(), null, Taxonomies::DOCUMENT_DOWNLOAD_SLUG );
					delete_post_meta( $this->id, '_dlp_attached_file_id' );
				}
				break;

			case 'file':
				if ( $this->get_file_id() && is_numeric( $this->get_file_id() ) ) {
					wp_set_object_terms( $this->get_file_id(), null, Taxonomies::DOCUMENT_DOWNLOAD_SLUG );
				}

				if ( filter_var( $data['file_id'], FILTER_VALIDATE_INT ) ) {
					$this->set_file_id( $data['file_id'] );
					wp_set_object_terms( $data['file_id'], 'document-download', Taxonomies::DOCUMENT_DOWNLOAD_SLUG );
				}
				break;
		}
	}

	/**
	 * Set the file id meta
	 *
	 * @param string $file_id
	 */
	public function set_file_id( $file_id ) {
		$this->set_meta_data( '_dlp_attached_file_id', $file_id );
	}

	/**
	 * Sets meta data
	 *
	 * @param string $key
	 * @param string $value
	 */
	protected function set_meta_data( $key, $value ) {
		update_post_meta( $this->id, $key, $value );
	}

	/**
	 * Returns the document ID
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Retrieves the attached file id
	 *
	 * @return string
	 */
	public function get_file_id() {
		return $this->get_meta_data( '_dlp_attached_file_id' );
	}

	/**
	 * Retrieves the attached file name
	 *
	 * @return string
	 */
	public function get_file_name() {
		$file = false;

		if ( $this->get_file_id() ) {
			$file = get_attached_file( $this->get_file_id() );
		}

		if ( ! $file ) {
			return false;
		}

		return wp_basename( $file );
	}

	/**
	 * Retrieves the associated file type icon
	 *
	 * @return string $file_icon
	 */
	public function get_file_icon() {
		$file_type = $this->get_file_type();

		if ( $file_type ) {
			$file_icon = SVG_Icon::get( SVG_Icon::get_file_extension_icon( $file_type ), [ 'dll-file-icon' ] );
		} else {
			$file_icon = SVG_Icon::get( 'default', [ 'dll-file-icon' ] );
		}

		return $file_icon;
	}

	/**
	 * Gets the associated file type
	 *
	 * @return string
	 */
	public function get_file_type() {
		$file_types = wp_get_object_terms( $this->id, 'file_type' );

		if ( ! is_wp_error( $file_types ) && is_array( $file_types ) && isset( $file_types[0] ) ) {
			return strtolower( $file_types[0]->name );
		}

		return false;
	}

	/**
	 * Gets the link type
	 *
	 * @return string
	 */
	public function get_link_type() {
		$saved_meta = $this->get_meta_data( '_dlp_document_link_type' );

		return $saved_meta ? $saved_meta : 'none';
	}

	/**
	 * Gets the download URL
	 *
	 * @return string
	 */
	public function get_download_url() {
		switch ( $this->get_link_type() ) {
			case 'none':
				$url = false;
				break;

			case 'file':
				$url = wp_get_attachment_url( $this->get_file_id() );
				break;

			default:
				$url = false;
				break;
		}

		return $url;
	}

	/**
	 * Generate the Download button HTML markup
	 *
	 * @param string $link_text
	 * @param string $link_style
	 * @return string
	 */
	public function get_download_button( $link_text, $link_style = 'button' ) {
		if ( ! $this->get_download_url() ) {
			return '';
		}

		$link_text          = $this->ensure_download_button_link_text( $link_text );
		$button_class       = in_array( $link_style, [ 'icon_only', 'icon', 'text' ], true ) ? '' : apply_filters( 'document_library_button_column_button_class', 'dll-download-button document-library-button button btn' );
		$download_attribute = $this->get_download_button_attributes();

		$anchor_open = sprintf(
			'<a href="%1$s" class="%2$s" %3$s>',
			esc_url( $this->get_download_url() ),
			esc_attr( $button_class ),
			$download_attribute
		);

		$anchor_text = [
			'button'           => $link_text,
			'button_icon_text' => SVG_Icon::get( 'download', [ 'dll-button-icon', 'dll-button-icon-text' ] ) . $link_text,
			'button_icon'      => SVG_Icon::get( 'download', [ 'dll-button-icon' ] ),
			'icon_only'        => SVG_Icon::get( 'download', [ 'dll-button-icon' ] ),
			'icon'             => $this->get_file_icon(), // file type icon
			'text'             => $link_text,
		];

		$anchor_close = '</a>';

		return $anchor_open . $anchor_text[ $link_style ] . $anchor_close;
	}

	/**
	 * Retrieves the 'download' attribute
	 *
	 * @return string
	 */
	private function get_download_button_attributes() {

		if ( $this->get_link_type() !== 'file' ) {
			return '';
		}

		$mime_type = get_post_mime_type( $this->get_file_id() );

		return sprintf( ' download="%1$s" type="%2$s"', basename( get_attached_file( $this->get_file_id() ) ), $mime_type );
	}

	/**
	 * Retrieves the download button text
	 *
	 * @param string $link_text
	 * @return string
	 */
	private function ensure_download_button_link_text( $link_text ) {
		$link_text = $link_text ? $link_text : get_the_title( $this->get_id() );

		return apply_filters( 'document_library_button_column_button_text', $link_text );
	}

	/**
	 * @param int $post_id
	 * Sets the file type taxonomy if there is associated file
	 */
	public function set_file_type( $post_id ) {
		$file_name = $this->get_file_name();

		if ( $file_name ) {
			$file_type = wp_check_filetype( $file_name );

			if ( isset( $file_type['ext'] ) ) {
				wp_set_object_terms( $post_id, $file_type['ext'], Taxonomies::FILE_TYPE_SLUG );
			}
		}
	}
}
