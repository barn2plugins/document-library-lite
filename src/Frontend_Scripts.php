<?php

namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Plugin;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Util;

/**
 * Registers the frontend styles and scripts for the post tables.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Frontend_Scripts implements Registerable, Standard_Service {

	const DATATABLES_VERSION = '1.11.3';
	const PHOTOSWIPE_VERSION = '4.1.3';

	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
	}

	/**
	 * Register the frontend styles.
	 */
	public function register_styles() {

		wp_register_style( 'jquery-datatables-dlw', plugins_url( 'assets/js/datatables/datatables.min.css', $this->plugin->get_file() ), [], self::DATATABLES_VERSION );
		wp_register_style( 'document-library', plugins_url( "assets/css/document-library-main.css", $this->plugin->get_file() ), [ 'jquery-datatables-dlw' ], $this->plugin->get_version() );
		wp_register_style( 'photoswipe', plugins_url( 'assets/js/photoswipe/photoswipe.min.css', $this->plugin->get_file() ), [], self::PHOTOSWIPE_VERSION );
		wp_register_style( 'photoswipe-default-skin', plugins_url( 'assets/js/photoswipe/default-skin/default-skin.min.css', $this->plugin->get_file() ), [ 'photoswipe' ], self::PHOTOSWIPE_VERSION );
	}

	/**
	 * Register the frontend scripts.
	 */
	public function register_scripts() {
		$suffix = Util::get_script_suffix();

		wp_register_script( 'jquery-datatables-dlw', plugins_url( "assets/js/datatables/datatables{$suffix}.js", $this->plugin->get_file() ), [ 'jquery' ], self::DATATABLES_VERSION, true );
		wp_register_script( 'document-library', plugins_url( "assets/js/document-library-main.js", $this->plugin->get_file() ), [ 'jquery', 'jquery-datatables-dlw' ], $this->plugin->get_version(), true );
		wp_register_script( 'photoswipe', plugins_url( 'assets/js/photoswipe/photoswipe.min.js', $this->plugin->get_file() ), [], self::PHOTOSWIPE_VERSION, true );
		wp_register_script( 'photoswipe-ui-default', plugins_url( 'assets/js/photoswipe/photoswipe-ui-default.min.js', $this->plugin->get_file() ), [ 'photoswipe' ], self::PHOTOSWIPE_VERSION, true );
	}

	/**
	 * Load photoswipe assets.
	 *
	 * @param book $enabled
	 */
	public static function load_photoswipe_resources( $enabled ) {
		if ( ! $enabled ) {
			return;
		}

		wp_enqueue_style( 'photoswipe-default-skin' );
		wp_enqueue_script( 'photoswipe-ui-default' );

		add_action( 'wp_footer', [ self::class, 'load_photoswipe_template' ] );
	}

	/**
	 * Load photoswipe template.
	 */
	public static function load_photoswipe_template() {
		if ( $located = locate_template( 'dlw_templates/photoswipe.php', false ) ) {
			include_once $located;
		} else {
			include_once plugin_dir_path( PLUGIN_FILE ) . 'templates/photoswipe.php';
		}
	}
}
