<?php

namespace Barn2\Plugin\Document_Library\Admin;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Conditional;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Util;

/**
 * Handles functionality on the Pages list table screen
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Page_List implements Registerable, Standard_Service, Conditional {

	/**
	 * {@inheritdoc}
	 */
	public function is_required() {
		return Util::is_admin();
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_filter( 'display_post_states', [ $this, 'display_post_states' ], 10, 2 );
	}

	/**
	 * Add a post display state for the document library page
	 *
	 * @param array     $post_states An array of post display states.
	 * @param \WP_Post  $post        The current post object.
	 */
	public function display_post_states( $post_states, $post ) {
		if ( $this->get_page_id( 'document_page' ) === $post->ID ) {
			$post_states['dlp_document_library_page'] = __( 'Document Library Page', 'document-library-lite' );
		}

		return $post_states;
	}

	/**
	 * Returns the document page ID
	 *
	 * @param string $page_key
	 * @return int $page_id
	 */
	private function get_page_id( $page_key ) {
		$page_id = get_option( "dlp_$page_key" );

		return $page_id ? absint( $page_id ) : -1;
	}

}
