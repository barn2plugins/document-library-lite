<?php

namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;

/**
 * Add a notice to request a review.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Review_Notice implements Registerable, Standard_Service {

	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		if ( defined( 'DISABLE_NAG_NOTICES' ) ) {
			return;
		}

		add_action( 'transition_post_status', [ $this, 'check_document_published_count' ], 10, 3 );
		add_action( 'admin_notices', [ $this, 'maybe_add_notice' ] );
		add_action( 'wp_ajax_dlw_dismiss_notice', [ $this, 'maybe_dismiss_notice' ] );
	}

	/**
	 * Count the number of published documents.
	 *
	 * @param string $new
	 * @param string $old
	 * @param WP_Post $post
	 */
	public function check_document_published_count( $new, $old, $post ) {
		if ( get_option( 'dlw_review_notice_triggered' ) ) {
			return;
		}

		$document_count = (int) get_option( 'dlw_document_count', 0 );

		if ( ( $new === 'publish' ) && ( $old !== 'publish' ) && ( $post->post_type === Post_Type::POST_TYPE_SLUG ) ) {
			$document_count++;
		}

		if ( $document_count === 5 ) {
			update_option( 'dlw_review_notice_triggered', true, false );
			update_option( 'dlw_review_notice_user', $post->post_author );
			delete_option( 'dlw_document_count' );
		} else {
			update_option( 'dlw_document_count', $document_count, false );
		}
	}

	/**
	 * Handle the dismiss AJAX action.
	 */
	public function maybe_dismiss_notice() {
		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! $action || 'dlw_dismiss_notice' !== $action ) {
			return;
		}

		check_ajax_referer( 'dlw_dismiss_review_notice', 'nonce', true );

		update_option( 'dlw_review_notice_dismissed', true, false );
	}

	/**
	 * Maybe add the notice.
	 */
	public function maybe_add_notice() {
		global $pagenow, $current_screen;

		if ( ! in_array( $pagenow, [ 'index.php', 'edit.php', 'edit-tags.php', 'plugins.php', 'admin.php' ], true ) ) {
			return;
		}

		if ( $pagenow === 'admin.php' && $current_screen->parent_base !== 'document_library' ) {
			return;
		}

		if ( in_array( $pagenow, [ 'edit.php', 'edit-tags.php' ], true ) && $current_screen->post_type !== Post_Type::POST_TYPE_SLUG ) {
			return;
		}

		if ( ! get_option( 'dlw_review_notice_triggered' ) || get_option( 'dlw_review_notice_dismissed' ) ) {
			return;
		}

		$user_id = (int) get_option( 'dlw_review_notice_user', 0 );

		if ( get_current_user_id() !== $user_id ) {
			return;
		}

		$this->print_script();
		$this->print_style();

		?>
		<div id="dlw-review-notice" class="notice">
			<div class="dlw-review-notice-left">
				<h1><?php esc_html_e( 'Are you enjoying Document Library Lite?', 'document-library-lite' ); ?></h1>
				<p><?php esc_html_e( 'Congratulations, you\'ve just published your 5th document! If you have time, it would be great if you could leave us a review and let us know what you think of the plugin.', 'document-library-lite' ); ?></p>

				<div class="dlw-review-notice-actions">
					<a href="https://wordpress.org/support/plugin/document-library-lite/reviews/#new-post" target="_blank" class="dlw-add-review button button-primary"><?php esc_html_e( 'Add Review', 'document-library-lite' ); ?></a>
					<a class="dlw-dismiss-notice"><?php esc_html_e( 'Dismiss', 'document-library-lite' ); ?></a>
				</div>

				<span class="dlw-review-notice-meta"><?php esc_html_e( 'Barn2', 'document-library-lite' ); ?></span>
			</div>

			<div class="dlw-review-notice-right">
				<?php printf( '<img src=%s" />', esc_url( $this->plugin->get_dir_url() . '/assets/images/review.png' ) ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Print the script for dismissing the notice.
	 */
	private function print_script() {

		// Create a nonce.
		$nonce = wp_create_nonce( 'dlw_dismiss_review_notice' );
		?>
		<script>
		window.addEventListener( 'load', function() {
			var dismissBtn = document.querySelector( '.dlw-dismiss-notice' );

			// Add an event listener to the dismiss button.
			dismissBtn.addEventListener( 'click', function( event ) {
				var httpRequest = new XMLHttpRequest(),
					postData    = '';

				// Build the data to send in our request.
				// Data has to be formatted as a string here.
				postData += 'id=dlw-review-notice';
				postData += '&action=dlw_dismiss_notice';
				postData += '&nonce=<?php echo esc_html( $nonce ); ?>';

				httpRequest.open( 'POST', '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>' );
				httpRequest.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' )
				httpRequest.send( postData );

				// handle the notice fadeout
				var reviewNotice = document.getElementById( 'dlw-review-notice' );
				var fadeEffect = setInterval(function () {
					if ( ! reviewNotice.style.opacity ) {
						reviewNotice.style.opacity = 1;
					}

					if ( reviewNotice.style.opacity > 0 ) {
						reviewNotice.style.opacity -= 0.1;
					} else {
						clearInterval( fadeEffect );
						reviewNotice.style.display = 'none';
					}
				}, 20 );
			});
		});
		</script>
		<?php
	}

	/**
	 * Print the styles.
	 */
	private function print_style() {

		?>
		<style>
			#dlw-review-notice {
				display: flex;
				justify-content: space-between;
				max-width: 1200px;
				margin: 10px 0 15px;
				padding: 5px 30px 10px;
				background: #ffffff;
				border: 1px solid #c3c4c7;
				border-left-width: 4px;
				border-left-color: #000;
				box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
			}
			.dlw-review-notice-left {
				position: relative;
			}
			.dlw-review-notice-right {
				display: flex;
				justify-content: center;
				align-items: center;
			}
			.dlw-review-notice-right img {
				width: 154px;
				padding-left: 110px;
			}
			@media screen and (max-width: 992px) {
				.dlw-review-notice-right img {
					padding-left: 60px;
				}
			}
			#dlw-review-notice h1 {
				margin-bottom: 0;
				font-size: 21px;
				font-weight: 400;
				line-height: 1.2;
			}
			.dlw-review-notice-actions {
				display: flex;
				align-items: center;
			}
			.dlw-add-review {
				padding: 0 20px !important;
			}
			.dlw-dismiss-notice {
				display: flex;
				margin-left: 11px;
				font-size: 14px;
				line-height: 20px;
				cursor: pointer;
			}
			.dlw-dismiss-notice::before {
				content: "\f335";
				font: normal 20px/20px dashicons;
			}
			.dlw-review-notice-meta {
				display: block;
				padding-top: 10px;
				color: #83868b;
			}
		</style>
		<?php
	}
}
