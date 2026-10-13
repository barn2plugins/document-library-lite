<?php

namespace Barn2\Plugin\Document_Library\Dependencies\Lib\Admin;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;

/**
 * A dismissible, date-limited sale banner shown only on a plugin's own admin screens.
 *
 * Each campaign has a UTC start and end, a discount code and its own wording. The banner shows the
 * campaign that is running now, so a sale that changes code part way through (e.g. Black Friday
 * moving to a "last chance" code) is just two campaigns back to back. Outside every window nothing
 * is output. The notice needs no remote requests: the dates, codes and wording ship with the plugin,
 * as WordPress.org guideline 7 requires for free plugins.
 *
 * Dismissal is stored per user and per campaign, and is shared by every Barn2 plugin: a user who
 * dismisses the banner in one plugin does not see that campaign again in another.
 *
 * Preview: a user with the capability can add ?barn2_sale_preview=<campaign id> to a plugin screen,
 * or a site can define BARN2_SALE_NOTICE_PREVIEW as a campaign id. Preview ignores the dates and
 * the dismissed state.
 *
 * Usage:
 *
 *     $this->add_service( 'sale_notice', new Sale_Notice( [
 *         'plugin_slug' => 'document-library-lite',
 *         'plugin_file' => $plugin->get_file(),
 *         'product_url' => 'https://barn2.com/wordpress-plugins/document-library-pro/',
 *         'pro_name'    => 'Document Library Pro',
 *         'screens'     => [ 'toplevel_page_document_library' ],
 *     ] ) );
 *
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 * @version   1.0
 */
class Sale_Notice implements Registerable, Standard_Service {

	const AJAX_ACTION   = 'barn2_dismiss_sale_notice';
	const NONCE_ACTION  = 'barn2_dismiss_sale_notice';
	const META_PREFIX   = 'barn2_sale_notice_dismissed_';
	const PREVIEW_PARAM = 'barn2_sale_preview';

	/**
	 * @var array
	 */
	private $args;

	/**
	 * @var array|null The campaign to show on this request, resolved lazily.
	 */
	private $campaign = false;

	/**
	 * Constructor.
	 *
	 * @param array $args {
	 *     @type string   $plugin_slug The plugin slug. Used for utm_content and the element ID.
	 *     @type string   $plugin_file The plugin's main file (not used for assets yet; reserved for the final artwork).
	 *     @type string   $product_url The barn2.com page to link to. The discount code and utm tags are added to it.
	 *     @type string   $pro_name    Optional. The paid plugin to name in the message. Empty for a generic "every plugin" message (paid plugins).
	 *     @type string[] $screens     Screen IDs where the banner may show.
	 *     @type callable $condition   Optional. Extra check, run on a matching screen, e.g. to test a settings tab.
	 *     @type string   $capability  Capability needed to see the banner. Default 'manage_options'.
	 *     @type string   $utm_source  Default 'plugin'.
	 * }
	 */
	public function __construct( array $args ) {
		$this->args = wp_parse_args(
			$args,
			[
				'plugin_slug' => '',
				'plugin_file' => '',
				'product_url' => 'https://barn2.com/wordpress-plugins/',
				'pro_name'    => '',
				'screens'     => [],
				'condition'   => null,
				'capability'  => 'manage_options',
				'utm_source'  => 'plugin',
			]
		);
	}

	/**
	 * The sale campaigns. Times are UTC.
	 *
	 * The banner starts the day after the sale opens and switches to the last chance code when the
	 * main code expires, so it never advertises a code that has stopped working.
	 *
	 * @return array
	 */
	public static function get_campaigns() {
		$campaigns = [
			'bf2026'    => [
				'start'    => '2026-11-20 00:00:00',
				'end'      => '2026-12-01 08:00:00',
				'code'     => 'BF2026',
				'discount' => 30,
				'campaign' => 'bf2026',
				'label'    => __( 'Black Friday', 'barn2-lib' ),
				/* translators: 1: paid plugin name, 2: discount percentage */
				'title'    => __( 'Upgrade to %1$s for %2$d%% off', 'barn2-lib' ),
				'text'     => __( 'Unlock all premium features. Your discount is applied automatically at checkout. Offer ends December 1.', 'barn2-lib' ),
				/* translators: %d: discount percentage */
				'title_all' => __( '%d%% off all our plugins', 'barn2-lib' ),
				'text_all'  => __( 'Add another plugin to your site for less. Your discount is applied automatically at checkout. Offer ends December 1.', 'barn2-lib' ),
			],
			'extradays' => [
				'start'    => '2026-12-01 08:00:00',
				'end'      => '2026-12-05 00:00:00',
				'code'     => 'EXTRADAYS',
				'discount' => 25,
				'campaign' => 'bf2026',
				'label'    => __( 'Last chance', 'barn2-lib' ),
				/* translators: 1: paid plugin name, 2: discount percentage */
				'title'    => __( 'Missed Black Friday? Upgrade to %1$s for %2$d%% off', 'barn2-lib' ),
				'text'     => __( 'We\'ve added a few extra days. Your discount is applied automatically at checkout. Offer ends December 4.', 'barn2-lib' ),
				/* translators: %d: discount percentage */
				'title_all' => __( 'Missed Black Friday? %d%% off all our plugins', 'barn2-lib' ),
				'text_all'  => __( 'We\'ve added a few extra days. Your discount is applied automatically at checkout. Offer ends December 4.', 'barn2-lib' ),
			],
		];

		/**
		 * Filters the sale campaigns shown by the Barn2 sale notice.
		 *
		 * @param array $campaigns Campaigns keyed by ID.
		 */
		return apply_filters( 'barn2_sale_notice_campaigns', $campaigns );
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		if ( ! is_admin() ) {
			return;
		}

		// The dates are checked when the notice renders, not here, so nothing is translated or computed this early.
		add_action( 'admin_notices', [ $this, 'render' ] );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ __CLASS__, 'ajax_dismiss' ] );
	}

	/**
	 * Gets the campaign to show on this request: a valid preview, else the one running now.
	 *
	 * @return array|null
	 */
	private function get_campaign() {
		if ( $this->campaign !== false ) {
			return $this->campaign;
		}

		$campaigns      = self::get_campaigns();
		$this->campaign = null;
		$preview        = self::get_preview_id();

		if ( $preview && isset( $campaigns[ $preview ] ) ) {
			$this->campaign = array_merge( $campaigns[ $preview ], [ 'id' => $preview, 'preview' => true ] );
			return $this->campaign;
		}

		$now = time();

		foreach ( $campaigns as $id => $campaign ) {
			$start = strtotime( $campaign['start'] . ' UTC' );
			$end   = strtotime( $campaign['end'] . ' UTC' );

			if ( $start && $end && $now >= $start && $now < $end ) {
				$this->campaign = array_merge( $campaign, [ 'id' => $id, 'preview' => false ] );
				break;
			}
		}

		return $this->campaign;
	}

	/**
	 * Gets the campaign ID requested for preview, if any.
	 *
	 * The query parameter is only read here; the capability is checked at render time, when the
	 * current user is known.
	 *
	 * @return string
	 */
	private static function get_preview_id() {
		if ( defined( 'BARN2_SALE_NOTICE_PREVIEW' ) && BARN2_SALE_NOTICE_PREVIEW ) {
			return sanitize_key( BARN2_SALE_NOTICE_PREVIEW );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ self::PREVIEW_PARAM ] ) ? sanitize_key( wp_unslash( $_GET[ self::PREVIEW_PARAM ] ) ) : '';
	}

	/**
	 * Whether the banner should show on the current screen for the current user.
	 *
	 * @return bool
	 */
	private function should_show() {
		$campaign = $this->get_campaign();

		if ( ! $campaign || ! current_user_can( $this->args['capability'] ) ) {
			return false;
		}

		// Only one sale banner per page, even if several Barn2 plugins share a screen.
		if ( ! empty( $GLOBALS['barn2_sale_notice_rendered'] ) ) {
			return false;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->id, (array) $this->args['screens'], true ) ) {
			return false;
		}

		if ( is_callable( $this->args['condition'] ) && ! call_user_func( $this->args['condition'], $screen ) ) {
			return false;
		}

		if ( ! $campaign['preview'] && get_user_meta( get_current_user_id(), self::META_PREFIX . $campaign['id'], true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets the link to barn2.com with the code applied and utm tags for measuring purchases.
	 *
	 * barn2.com applies ?discount=CODE to the cart on any page.
	 *
	 * @param array $campaign
	 * @return string
	 */
	private function get_link( array $campaign ) {
		return add_query_arg(
			[
				'discount'     => rawurlencode( $campaign['code'] ),
				'utm_source'   => rawurlencode( $this->args['utm_source'] ),
				'utm_medium'   => 'banner',
				'utm_campaign' => rawurlencode( $campaign['campaign'] ),
				'utm_content'  => rawurlencode( $this->args['plugin_slug'] ),
			],
			$this->args['product_url']
		);
	}

	/**
	 * Outputs the banner.
	 */
	public function render() {
		if ( ! $this->should_show() ) {
			return;
		}

		$GLOBALS['barn2_sale_notice_rendered'] = true;

		$campaign = $this->get_campaign();
		if ( $this->args['pro_name'] ) {
			$title   = sprintf( $campaign['title'], $this->args['pro_name'], $campaign['discount'] );
			$message = $campaign['text'];
		} else {
			$title   = sprintf( $campaign['title_all'], $campaign['discount'] );
			$message = $campaign['text_all'];
		}

		/* translators: %d: discount percentage */
		$button = sprintf( __( 'Upgrade for %d%% off', 'barn2-lib' ), $campaign['discount'] );

		$id = 'barn2-sale-notice-' . sanitize_html_class( $this->args['plugin_slug'] );
		?>
		<div id="<?php echo esc_attr( $id ); ?>" class="notice barn2-sale-notice is-dismissible" data-campaign="<?php echo esc_attr( $campaign['id'] ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( self::NONCE_ACTION ) ); ?>">
			<div class="barn2-sale-notice__badge"><?php echo esc_html( $campaign['discount'] ); ?>%<span><?php esc_html_e( 'off', 'barn2-lib' ); ?></span></div>
			<div class="barn2-sale-notice__body">
				<p class="barn2-sale-notice__label"><?php echo esc_html( $campaign['label'] ); ?></p>
				<p class="barn2-sale-notice__title"><?php echo esc_html( $title ); ?></p>
				<p class="barn2-sale-notice__text"><?php echo esc_html( $message ); ?></p>
			</div>
			<a class="barn2-sale-notice__button" href="<?php echo esc_url( $this->get_link( $campaign ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $button ); ?></a>
		</div>
		<?php
		$this->print_assets( $id );
	}

	/**
	 * Prints the banner's styles and dismiss script inline, so it needs no extra files.
	 *
	 * @param string $id The banner element ID.
	 */
	private function print_assets( $id ) {
		?>
		<style>
			.barn2-sale-notice{display:flex;align-items:center;gap:20px;padding:16px 44px 16px 16px;margin:16px 0;background:#f6fbfd;border:1px solid #cde9f2;border-left:4px solid #03a0c7;box-shadow:none}
			.barn2-sale-notice p{margin:0;padding:0}
			.barn2-sale-notice__badge{flex:0 0 auto;width:72px;height:72px;border-radius:50%;background:#ffb608;color:#1f2834;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:24px;font-weight:700;line-height:1}
			.barn2-sale-notice__badge span{font-size:12px;font-weight:600;text-transform:uppercase;margin-top:2px}
			.barn2-sale-notice__body{flex:1 1 auto;min-width:0}
			.barn2-sale-notice__label{color:#027895;font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase}
			.barn2-sale-notice .barn2-sale-notice__title{color:#1f2834;font-size:18px;font-weight:700;margin:2px 0 4px}
			.barn2-sale-notice__text{color:#3c434a;font-size:14px}
			.barn2-sale-notice a.barn2-sale-notice__button{flex:0 0 auto;background:#03a0c7;color:#fff;border-radius:4px;padding:10px 18px;font-size:14px;font-weight:600;text-decoration:none;white-space:nowrap}
			.barn2-sale-notice a.barn2-sale-notice__button:hover,.barn2-sale-notice a.barn2-sale-notice__button:focus{background:#027895;color:#fff}
			@media (max-width:782px){.barn2-sale-notice{flex-wrap:wrap}.barn2-sale-notice__badge{width:56px;height:56px;font-size:19px}}
		</style>
		<script>
			( function () {
				var el = document.getElementById( <?php echo wp_json_encode( $id ); ?> );
				if ( ! el ) {
					return;
				}
				el.addEventListener( 'click', function ( e ) {
					if ( ! e.target.classList.contains( 'notice-dismiss' ) ) {
						return;
					}
					var data = new FormData();
					data.append( 'action', <?php echo wp_json_encode( self::AJAX_ACTION ); ?> );
					data.append( 'campaign', el.getAttribute( 'data-campaign' ) );
					data.append( 'nonce', el.getAttribute( 'data-nonce' ) );
					fetch( <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, { method: 'POST', credentials: 'same-origin', body: data } );
				} );
			} )();
		</script>
		<?php
	}

	/**
	 * Stores the dismissal for the current user. Shared by every Barn2 plugin.
	 */
	public static function ajax_dismiss() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$campaign = isset( $_POST['campaign'] ) ? sanitize_key( wp_unslash( $_POST['campaign'] ) ) : '';

		if ( ! $campaign || ! array_key_exists( $campaign, self::get_campaigns() ) ) {
			wp_send_json_error( null, 400 );
		}

		update_user_meta( get_current_user_id(), self::META_PREFIX . $campaign, time() );
		wp_send_json_success();
	}
}
