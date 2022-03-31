<?php
namespace Barn2\DLW_Lib\Admin;

use Barn2\DLW_Lib\Registerable,
	Barn2\DLW_Lib\Plugin\Plugin,
	Barn2\DLW_Lib\Util;

/**
 * Provides functions to add the plugin promo to the plugin settings page in the WordPress admin.
 *
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 * @version   1.1
 */
class Plugin_Promo implements Registerable {

	private $plugin;
	private $plugin_id;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin    = $plugin;
		$this->plugin_id = $plugin->get_id();
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_action( 'barn2_after_plugin_settings', [ $this, 'render_promo' ], 10, 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_styles' ] );
	}

	/**
	 * Load the plugin promo CSS.
	 *
	 * @param string $hook
	 */
	public function load_styles( $hook ) {
		$parsed_url = wp_parse_url( $this->plugin->get_settings_page_url() );
		if ( isset( $parsed_url['query'] ) ) {
			parse_str( $parsed_url['query'], $args );

			if ( isset( $args['page'] ) && false !== strpos( $hook, $args['page'] ) ) {
				wp_enqueue_style( 'barn2-plugins-promo', plugins_url( 'lib/assets/css/admin/plugin-promo.min.css', $this->plugin->get_file() ), [], $this->plugin->get_version(), 'all' );
			}
		}
	}

	/**
	 * Return the plugin promo HTML.
	 *
	 * @param int $plugin_id
	 */
	public function render_promo( $plugin_id ) {
		if ( $plugin_id !== $this->plugin_id ) {
			return;
		}

		$promo_content = $this->get_promo_content();

		if ( ! empty( $promo_content ) ) {
			echo '<div id="barn2_plugins_promo" class="barn2-plugins-promo">' . wp_kses_post( $promo_content ) . '</div>';
		}
	}

	/**
	 * Retrieve the plugin promo content from the API.
	 *
	 * @return string|null
	 */
	private function get_promo_content() {
		if ( ( $promo_content = get_transient( 'barn2_plugin_promo_' . $this->plugin_id ) ) === false ) {
			$promo_response = wp_remote_get( Util::barn2_url( '/wp-json/barn2/v2/pluginpromo/' . $this->plugin_id . '?_=' . gmdate( 'mdY' ) ) );

			if ( wp_remote_retrieve_response_code( $promo_response ) !== 200 ) {
				return;
			}

			$promo_content = json_decode( wp_remote_retrieve_body( $promo_response ), true );

			set_transient( 'barn2_plugin_promo_' . $this->plugin_id, $promo_content, DAY_IN_SECONDS );
		}

		if ( empty( $promo_content ) || is_array( $promo_content ) ) {
			return;
		}

		return $promo_content;
	}

}
