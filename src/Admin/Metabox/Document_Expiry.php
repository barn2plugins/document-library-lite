<?php

namespace Barn2\Plugin\Document_Library\Admin\Metabox;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Conditional;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Util;
use Barn2\Plugin\Document_Library\Post_Type;

/**
 * Document Expiry - Edit Document Metabox
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0z
 */
class Document_Expiry implements Registerable, Standard_Service, Conditional {
	const ID = 'document_expiry';

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
		add_action( 'current_screen', [ $this, 'add_metabox' ] );
	}

	/**
	 * Add metabox either on the misc options section of the classic editor or as a normal metabox in the block editor.
	 */
	public function add_metabox( $current_screen ) {

		if ( Post_Type::POST_TYPE_SLUG === $current_screen->post_type ) {
			if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
				add_action( 'add_meta_boxes', [ $this, 'register_metabox' ] );
			} else {
				add_action( 'post_submitbox_misc_actions', [ $this, 'render' ] );
			}
		}
	}

	/**
	 * Register the metabox.
	 */
	public function register_metabox() {
		add_meta_box(
			self::ID,
			__( 'Document expiry', 'document-library-lite' ),
			[ $this, 'render' ],
			'dlp_document',
			'side',
		);
	}

	/**
	 * Render the metabox.
	 *
	 * @param WP_Post $post
	 */
	public function render( $post ) {
		// check if dlp document
		if ( Post_Type::POST_TYPE_SLUG !== $post->post_type ) {
			return;
		}

		?>
		<div class="misc-pub-section curtime misc-pub-expiry document-expiry-container">
			<div id="document-expiry">
				<span id="expiry-status">
					<span class="dashicons dashicons-clock"></span>
					<?php esc_html_e( 'Document expiry', 'document-library-lite' ); ?>
					<span class="dlw-help-tip" popovertarget="dlw-document-expiry-popover" tabindex="0"></span>
				</span>
			</div>
		</div>

		<div id="dlw-document-expiry-popover" popover role="tooltip">
			<div class="popover-inner">
				<img src="<?php echo esc_url( plugins_url( 'assets/images/expiry-promo.svg', dirname( __DIR__, 3 ) . '/document-library-lite.php' ) ); ?>" />
				<div class="popover-content">
					<h3><?php esc_html_e( 'Unlock Advanced Features', 'document-library-lite' ); ?></h3>
					<p><?php esc_html_e( 'Upgrade to the Document Library Pro to set expiry dates, in which documents will automatically be removed from the library on the date you choose.', 'document-library-lite' ); ?></p>
					<div>
						<a href="https://barn2.com/wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings" class="button button-primary"><?php esc_html_e( 'Upgrade Now', 'document-library-lite' ); ?></a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
