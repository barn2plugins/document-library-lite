<?php

namespace Barn2\Plugin\Document_Library\Admin\Metabox;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Conditional;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Util;
use	Barn2\Plugin\Document_Library\Post_Type;
use	Barn2\Plugin\Document_Library\Document;

/**
 * Document Link - Edit Document Metabox
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0z
 */
class Document_Link implements Registerable, Standard_Service, Conditional {
	const ID = 'document_link';

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
		add_action( 'add_meta_boxes', [ $this, 'register_metabox' ], 1 );
		add_action( 'save_post_' . Post_Type::POST_TYPE_SLUG, [ $this, 'save' ] );
	}

	/**
	 * Register the metabox
	 */
	public function register_metabox() {
		add_meta_box(
			self::ID,
			__( 'Document Link', 'document-library-lite' ),
			[ $this, 'render' ],
			'dlp_document',
			'side',
			'high'
		);
	}

	/**
	 * Render the metabox.
	 *
	 * @param WP_Post $post
	 */
	public function render( $post ) {
		$document = new Document( $post->ID );

		$button_text         = $document->get_file_id() ? __( 'Replace File', 'document-library-lite' ) : __( 'Add File', 'document-library-lite' );
		$file_attached_class = $document->get_file_id() ? ' active' : '';
		$file_details_class  = $document->get_link_type() === 'file' ? 'active' : '';
		?>

		<label for="<?php esc_attr( self::ID ); ?>" class="howto"><?php esc_html_e( 'Upload a file or select one from the media library:', 'document-library-lite' ); ?></label>

		<!-- option selector -->
		<select name="_dlp_document_link_type" id="dlw_document_link_type" class="postbox">
			<option value="none" <?php selected( $document->get_link_type(), 'none' ); ?>><?php esc_html_e( 'None', 'document-library-lite' ); ?></option>
			<option value="file" <?php selected( $document->get_link_type(), 'file' ); ?>><?php esc_html_e( 'File Upload', 'document-library-lite' ); ?></option>
		</select>

		<!-- file upload -->
		<div id="dlw_file_attachment_details" class="<?php echo esc_attr( $file_details_class ); ?>">
				<div class="dlw_file_attached <?php echo esc_attr( $file_attached_class ); ?>">
					<button type="button" id="dlw_remove_file_button">
						<span class="remove-file-icon" aria-hidden="true"></span>

						<span class="screen-reader-text">
						<?php
						/* translators: %s: File name */
						echo esc_html( sprintf( __( 'Remove file: %s', 'document-library-lite' ), $document->get_file_name() ) );
						?>
						</span>
					</button>

					<span class="dlw_file_name_text"><?php echo esc_html( $document->get_file_name() ); ?></span>
					<input id="dlw_file_name_input" type="hidden" name="_dlp_attached_file_name" value="<?php echo esc_attr( $document->get_file_name() ); ?>" />
				</div>


			<button id="dlw_add_file_button" class="button button-large"><?php echo esc_html( $button_text ); ?></button>
			<input id="dlw_file_id" type="hidden" name="_dlp_attached_file_id" value="<?php echo esc_attr( $document->get_file_id() ); ?>" />

		</div>
		<?php
	}

	/**
	 * Save the metabox values
	 *
	 * @param mixed $post_id
	 */
	public function save( $post_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['_dlp_document_link_type'] ) ) {
			return;
		}

		$type = filter_input( INPUT_POST, '_dlp_document_link_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$data = [];

		switch ( $type ) {
			case 'file':
				$data['file_id']   = filter_input( INPUT_POST, '_dlp_attached_file_id', FILTER_SANITIZE_NUMBER_INT );
				$data['file_name'] = filter_input( INPUT_POST, '_dlp_attached_file_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
				break;
		}

		try {
			$document = new Document( $post_id );
			$document->set_document_link( $type, $data );
			$document->set_file_type( $post_id );
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( \Exception $exception ) {
			// silent
		}
	}
}
