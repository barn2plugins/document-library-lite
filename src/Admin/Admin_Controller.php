<?php
namespace Barn2\Plugin\Document_Library\Admin;

use Barn2\Plugin\Document_Library\Admin\Wizard\Setup_Wizard;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Util;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Plugin;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Service_Container;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Admin\Plugin_Promo;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Admin\Settings_API_Helper;
use Barn2\Plugin\Document_Library\Post_Type;

/**
 * Handles general admin functions.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Admin_Controller implements Registerable, Standard_Service {

	use Service_Container;

	private $plugin;
	private $settings_page;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function register() {
		$this->register_services();
		$this->start_all_services();
		// Extra links on Plugins page
		add_filter( 'plugin_action_links_' . $this->plugin->get_basename(), [ $this, 'add_settings_link' ] );
		add_filter( 'plugin_row_meta', [ $this, 'add_pro_version_link' ], 10, 2 );
		// Admin scripts
		add_action( 'admin_enqueue_scripts', [ $this, 'settings_page_scripts' ] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function add_services() {
		$this->add_service( 'menu', new Menu( $this->plugin ) );
		$this->add_service( 'plugin_promo', new Plugin_Promo( $this->plugin ) );
		$this->add_service( 'settings', new Settings( $this->plugin ) );
		$this->add_service( 'page/settings', new Page\Settings( $this->plugin ) );
		$this->add_service( 'page/import', new Page\Import( $this->plugin ) );
		$this->add_service( 'page/protect', new Page\Protect( $this->plugin ) );
		$this->add_service( 'page_list', new Page_List() );
		$this->add_service( 'metabox/document_link', new Metabox\Document_Link() );
		$this->add_service( 'media_library', new Media_Library() );
	}

	/**
	 * Adds a setting link on the Plugins list.
	 *
	 * @param array $links
	 * @return array
	 */
	public function add_settings_link( $links ) {
		array_unshift(
			$links,
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $this->plugin->get_settings_page_url() ),
				esc_html__( 'Settings', 'document-library-lite' )
			)
		);
		return $links;
	}

	/**
	 * Adds a Pro version link on the Plugins list.
	 *
	 * @param array $links
	 * @param string $file
	 * @return array
	 */
	public function add_pro_version_link( $links, $file ) {
		if ( $file === $this->plugin->get_basename() ) {
			$links[] = sprintf(
				'<a href="%1$s" target="_blank"><strong>%2$s</strong></a>',
				esc_url( 'https://barn2.com/wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=pluginsadmin&utm_content=dlw-plugins' ),
				esc_html__( 'Pro Version', 'document-library-lite' )
			);
		}

		return $links;
	}

	/**
	 * Enqueue the admin scripts and styles.
	 *
	 * @param string $hook
	 */
	public function settings_page_scripts( $hook ) {
		$screen = get_current_screen();

		// Main Settings Page
		if ( 'toplevel_page_document_library' === $hook ) {
			wp_enqueue_style( 'dlw-admin-settings', plugins_url( 'assets/css/admin/document-library-settings.css', $this->plugin->get_file() ), [], $this->plugin->get_version(), 'all' );
			wp_enqueue_script( 'dlw-admin-settings', plugins_url( 'assets/js/admin/document-library-settings.js', $this->plugin->get_file() ), [ 'jquery' ], $this->plugin->get_version(), true );
		}

		// Import and Protect Page
		if ( $this->str_ends_with( $hook, 'page_dlp_import' ) || $this->str_ends_with( $hook, 'page_dll_protect' ) ) {
			wp_enqueue_style( 'dlw-admin-import', plugins_url( 'assets/css/admin/document-library-import.css', $this->plugin->get_file() ), [], $this->plugin->get_version(), 'all' );
		}

		// Add - Edit Document Page
		if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) && is_object( $screen ) && Post_Type::POST_TYPE_SLUG === $screen->post_type ) {
			wp_enqueue_media();
			wp_enqueue_script( 'dlw-admin-post', $this->plugin->get_dir_url() . 'assets/js/admin/document-library-post.js', [ 'jquery' ], $this->plugin->get_version(), true );
			wp_localize_script(
				'dlw-admin-post',
				'dlwAdminObject',
				[
					'i18n' => [
						'select_file'  => __( 'Select File', 'document-library-lite' ),
						'add_file'     => __( 'Add File', 'document-library-lite' ),
						'replace_file' => __( 'Replace File', 'document-library-lite' ),
					],
				]
			);

			wp_enqueue_style( 'dlw-admin-post', $this->plugin->get_dir_url() . 'assets/css/admin/document-library-post.css', [], $this->plugin->get_version(), 'all' );
		}
	}

	/**
	 * Determins if a string ends with another string
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	private function str_ends_with( $haystack, $needle ) {
		$length = strlen( $needle );
		return $length > 0 ? substr( $haystack, -$length ) === $needle : true;
	}
}
