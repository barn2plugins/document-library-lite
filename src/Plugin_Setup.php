<?php

namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Admin\Wizard\Starter;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Plugin;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Plugin_Activation_Listener;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;

/**
 * Plugin Setup
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Plugin_Setup implements Plugin_Activation_Listener, Registerable, Standard_Service {
	/**
	 * Plugin's entry file
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Plugin instance
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Wizard starter.
	 *
	 * @var Starter
	 */
	private $starter;

	/**
	 * Constructor.
	 *
	 * @param mixed $file
	 * @param Plugin $plugin
	 */
	public function __construct( $file, Plugin $plugin ) {
		$this->file    = $file;
		$this->plugin  = $plugin;
		$this->starter = new Starter( $this->plugin );
	}

	/**
	 * Register the service.
	 */
	public function register() {
		register_activation_hook( $this->file, [ $this, 'on_activate' ] );
		add_action( 'admin_init', [ $this, 'after_plugin_activation' ] );
	}

	/**
	 * On activation.
	 *
	 * @param mixed $network_wide
	 */
	public function on_activate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide && is_super_admin() ) {
			$sites = get_sites();

			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );

				add_option( 'dlp_should_flush_rewrite_rules', true );
				$this->create_pages();

				restore_current_blog();
			}
		} else {
			add_option( 'dlp_should_flush_rewrite_rules', true );
			$this->create_pages();

			/**
			 * Determine if setup wizard should run.
			 */
			if ( $this->starter->should_start() ) {
				$this->starter->create_transient();
			}
		}
	}

	/**
	 * Do nothing.
	 *
	 * @param bool $network_wide
	 */
	public function on_deactivate( $network_wide ) {
		flush_rewrite_rules();
	}

	/**
	 * Create pages that the plugin relies on, storing page IDs in variables.
	 */
	public function create_pages() {
		$pages = [
			'document_page' => [
				'name'    => _x( 'document-library', 'Page slug', 'document-library-lite' ),
				'title'   => _x( 'Document Library', 'Page title', 'document-library-lite' ),
				'content' => '<!-- wp:shortcode -->[doc_library]<!-- /wp:shortcode -->',
			]
		];

		foreach ( $pages as $key => $page ) {
			Lib_Util::create_page(
				esc_sql( $page['name'] ),
				'dlp_' . $key,
				$page['title'],
				$page['content'],
				''
			);
		}
	}

	/**
	 * Detect the transient and redirect to wizard.
	 *
	 * @return void
	 */
	public function after_plugin_activation() {

		if ( ! $this->starter->detected() ) {
			return;
		}

		$this->starter->delete_transient();
		$this->starter->create_option();
		$this->starter->redirect();
	}
}
