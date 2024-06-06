<?php

namespace Barn2\Plugin\Document_Library\Admin;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use	Barn2\Plugin\Document_Library\Taxonomies;

/**
 * Handles the Media Library features
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Media_Library implements Registerable, Standard_Service {

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		// List View - Document Download Filter
		add_action( 'restrict_manage_posts', [ $this, 'add_list_view_document_download_dropdown' ], 10, 1 );
	}

	/**
	 * Adds the Document Download filter dropdown to the Media Library list view.
	 *
	 * @param string $post_type
	 */
	public function add_list_view_document_download_dropdown( $post_type ) {
		if ( $post_type !== 'attachment' ) {
			return;
		}

		$document_download = filter_input( INPUT_GET, Taxonomies::DOCUMENT_DOWNLOAD_SLUG, FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		?>
		<select name="<?php echo esc_attr( Taxonomies::DOCUMENT_DOWNLOAD_SLUG ); ?>" id="filter-by-dlp">
			<option<?php selected( $document_download, '' ); ?> value=""><?php esc_html_e( 'All types', 'document-library-lite' ); ?></option>
			<option<?php selected( $document_download, 'document-download' ); ?> value="document-download"><?php esc_html_e( 'Documents', 'document-library-lite' ); ?></option>
		</select>
		<?php
	}

}
