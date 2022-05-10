<?php

namespace Barn2\DLW_Lib\Admin;

use Barn2\DLW_Lib\Plugin\Plugin;
use Barn2\DLW_Lib\Registerable;
use Barn2\DLW_Lib\Util;

/**
 * Provides functions to add the plugin promo to the plugin settings page in the WordPress admin.
 *
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 * @version   1.2
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
			// Promo content is sanitized via barn2_kses_post.
			// phpcs:ignore WordPress.Security.EscapeOutput
			echo '<div id="barn2_plugins_promo" class="barn2-plugins-promo">' . Util::barn2_kses_post( $promo_content ) . '</div>';
		}
	}

	/**
	 * Backward compatibility wrapper for get_sidebar_content.
	 *
	 * @return string
	 */
	private function get_promo_content() {
		return static::get_sidebar_content( $this->plugin );
	}

	/**
	 * Retrieve the plugin promo content from the API.
	 *
	 * @param Plugin $plugin The plugin object.
	 * @return string The promo sidebar content.
	 */
	public static function get_sidebar_content( Plugin $plugin ) {
		$review_content = get_transient( 'barn2_plugin_review_banner_' . $plugin->get_id() );
		$promo_content  = get_transient( 'barn2_plugin_promo_' . $plugin->get_id() );

		if ( false === $review_content ) {
			$review_content_url = Util::barn2_url( '/wp-json/barn2/v2/pluginpromo/' . $plugin->get_id() . '?_=' . gmdate( 'mdY' ) );
			$review_content_url = add_query_arg(
				[
					'source'   => urlencode( get_bloginfo( 'url' ) ),
					'template' => 'review_request',
				],
				$review_content_url
			);

			$review_response = wp_remote_get(
				$review_content_url,
				[
					'sslverify' => defined( 'WP_DEBUG' ) && WP_DEBUG ? false : true,
				]
			);

			if ( 200 !== wp_remote_retrieve_response_code( $review_response ) ) {
				$review_content = '';
			} else {
				$review_content = json_decode( wp_remote_retrieve_body( $review_response ) );
				set_transient( 'barn2_plugin_review_banner_' . $plugin->get_id(), $review_content, 7 * DAY_IN_SECONDS );
			}
		}

		if ( false === $promo_content ) {
			$promo_content_url = Util::barn2_url( '/wp-json/barn2/v2/pluginpromo/' . $plugin->get_id() . '?_=' . gmdate( 'mdY' ) );
			$plugin_dir        = WP_PLUGIN_DIR;
			$current_plugins   = get_plugins();
			$barn2_installed   = [];

			foreach ( $current_plugins as $slug => $data ) {
				if ( false !== stripos( $data['Author'], 'document-library-lite' ) ) {

					if ( is_readable( "$plugin_dir/$slug" ) ) {
						$plugin_contents = file_get_contents( "$plugin_dir/$slug" );

						if ( preg_match( '/namespace ([0-9A-Za-z_\\\]+);/', $plugin_contents, $namespace ) ) {
							$classname = $namespace[1] . '\Plugin';

							if ( class_exists( $classname ) && defined( "$classname::ITEM_ID" ) ) {
								if ( $id = ( $classname::ITEM_ID ?? null ) ) {
									$barn2_installed[] = $id;
								}
							}
						}
					}
				}
			}

			if ( $barn2_installed ) {
				$promo_content_url = add_query_arg( 'plugins_installed', implode( ',', $barn2_installed ), $promo_content_url );
			}

			$promo_content_url = add_query_arg( 'source', urlencode( get_bloginfo( 'url' ) ), $promo_content_url );

			$promo_response = wp_remote_get(
				$promo_content_url,
				[
					'sslverify' => defined( 'WP_DEBUG' ) && WP_DEBUG ? false : true,
				]
			);

			if ( 200 !== wp_remote_retrieve_response_code( $promo_response ) ) {
				$promo_content = '';
			} else {
				$promo_content = json_decode( wp_remote_retrieve_body( $promo_response ) );
				set_transient( 'barn2_plugin_promo_' . $plugin->get_id(), $promo_content, 7 * DAY_IN_SECONDS );
			}
		}

		return $review_content . $promo_content;

	}

}
