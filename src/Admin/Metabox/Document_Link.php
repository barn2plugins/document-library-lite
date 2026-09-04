<?php

namespace Barn2\Plugin\Document_Library\Admin\Metabox;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Conditional;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Util;
use Barn2\Plugin\Document_Library\Post_Type;
use Barn2\Plugin\Document_Library\Document;

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
		add_action( 'edit_form_after_title', [ $this, 'reposition_upload_metabox' ] );
		add_action( 'edit_form_after_title', [ $this, 'render_upload_metabox_description' ] );
		add_filter( 'get_user_metadata', [ $this, 'override_metabox_order' ], 10, 4 );
	}

	/**
	 * Register the metabox
	 */
	public function register_metabox() {
		add_meta_box(
			self::ID,
			__( 'File', 'document-library-lite' ),
			[ $this, 'render' ],
			Post_Type::POST_TYPE_SLUG,
			'side',
			'core'
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
		$url_details_class   = $document->get_link_type() === 'url' ? 'active' : '';

		wp_nonce_field( 'dll_save_document_link', 'dll_document_link_nonce' );
		?>

		<label for="<?php esc_attr( self::ID ); ?>" class="howto"><?php esc_html_e( 'Upload a file or add a URL where the document is located:', 'document-library-lite' ); ?></label>

		<!-- option selector -->
		<select name="_dlp_document_link_type" id="dlw_document_link_type" class="postbox">
			<option value="none" <?php selected( $document->get_link_type(), 'none' ); ?>><?php esc_html_e( 'None', 'document-library-lite' ); ?></option>
			<option value="file" <?php selected( $document->get_link_type(), 'file' ); ?>><?php esc_html_e( 'File Upload', 'document-library-lite' ); ?></option>
			<option value="url" <?php selected( $document->get_link_type(), 'url' ); ?>><?php esc_html_e( 'File URL', 'document-library-lite' ); ?></option>
		</select>

		<!-- file upload -->
		<div id="dlw_file_attachment_details" class="<?php echo esc_attr( $file_details_class ); ?>">
			<button id="dlw_add_file_button" class="button button-large"><?php echo esc_html( $button_text ); ?></button>
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

			<input id="dlw_file_id" type="hidden" name="_dlp_attached_file_id" value="<?php echo esc_attr( $document->get_file_id() ); ?>" />

		</div>
		<div id="dlw_link_url_details" class="<?php echo esc_attr( $url_details_class ); ?>">
			<a class="dlw-pro-only" href="https://barn2.com/wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Pro version only', 'document-library-lite' ); ?>
			</a>
		</div>

		<div class="document-library-pro-advanced-promo">
			<?php $this->render_version_history( 'after-html' ); ?>
		</div>
		<?php
	}

	/**
	 * Save the metabox values
	 *
	 * @param mixed $post_id
	 */
	public function save( $post_id ) {
		// Verify nonce
		if ( ! isset( $_POST['dll_document_link_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['dll_document_link_nonce'] ), 'dll_save_document_link' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

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

	/**
	 * Reposition the upload metabox after the title
	 */
	public function reposition_upload_metabox() {
		global $post, $wp_meta_boxes;

		// Only run on dlp_document post type edit page
		if ( ! $post || $post->post_type !== Post_Type::POST_TYPE_SLUG ) {
			return;
		}

		do_meta_boxes( get_current_screen(), 'dlw_below_title', $post );

		unset( $wp_meta_boxes['post']['dlw_below_title'] );
	}

	/**
	 * Prints out the header and the description for the upload metabox after the title and before the box
	 *
	 * @param WP_Post $post
	 */
	public function render_upload_metabox_description( $post ) {
		if ( $post->post_type !== Post_Type::POST_TYPE_SLUG ) {
			return;
		}
		?>
		<div id="dlw_upload_metabox_description">
			<h3><?php esc_html_e( 'Document Content', 'document-library-lite' ); ?></h3>
			<p><?php esc_html_e( 'The document content appears on the single document page and in the "Content" field of the document library.', 'document-library-lite' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Override user meta for metabox order to force our metabox position
	 *
	 * @param mixed  $value     The value get_metadata() should return - a single metadata value, or an array of values.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Whether to return only the first value of the specified $meta_key.
	 * @return mixed
	 */
	public function override_metabox_order( $value, $object_id, $meta_key, $single ) {
		if ( ! is_admin() ||
			( 'meta-box-order_' . Post_Type::POST_TYPE_SLUG !== $meta_key &&
			'metaboxhidden_' . Post_Type::POST_TYPE_SLUG !== $meta_key ) ) {
			return $value;
		}

		// Block editor does not interfere with metabox order.
		global $current_screen;
		if ( $current_screen && $current_screen->base === 'post' && $current_screen->id === Post_Type::POST_TYPE_SLUG && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
			return $value;
		}

		// Override metabox order - always force our position.
		if ( 'meta-box-order_' . Post_Type::POST_TYPE_SLUG === $meta_key ) {
			$meta_value = [ 'dlw_below_title' => self::ID ];
			return [ $meta_value ];
		}

		// Override metabox hidden status - always ensure visible.
		if ( 'metaboxhidden_' . Post_Type::POST_TYPE_SLUG === $meta_key ) {
			return [ [] ];
		}

		return $value;
	}

	/**
	 * Render the version history.
	 *
	 * @param string $context Context identifier to make IDs unique.
	 */
	private function render_version_history( $context = '' ) {
		$id_suffix = $context ? sprintf( '-%s', $context ) : '';
		?>
		<div id="version-history<?php echo esc_attr( $id_suffix ); ?>" class="document-library-pro-advanced-promo-version-history">
			<span class="version-history-status">
				<?php echo '<u>' . esc_html__( 'Version history', 'document-library-lite' ) . '</u>'; ?>
			</span>
			<span class="dlw-help-tip" popovertarget="dlw-version-history-popover<?php echo esc_attr( $id_suffix ); ?>" tabindex="0"></span>
		</div>

		<div id="dlw-version-history-popover<?php echo esc_attr( $id_suffix ); ?>" popover role="tooltip">
			<div class="popover-inner">
				<img src="<?php echo esc_url( plugins_url( 'assets/images/version-history-promo.svg', dirname( __DIR__, 3 ) . '/document-library-lite.php' ) ); ?>" alt="" />
				<div class="popover-content">
					<h3>Unlock Advanced Features</h3>
					<p>Upgrade to the Document Library Pro to manage and store multiple versions of your downloadable documents.</p>
					<div>
						<a href="https://barn2.com/wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings" class="button button-primary"><?php esc_html_e( 'Upgrade Now', 'document-library-lite' ); ?></a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
