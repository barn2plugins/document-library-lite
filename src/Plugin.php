<?php

namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Simple_Plugin;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Admin\Help_Scout_Beacon;
use Barn2\Plugin\Document_Library\Admin\Wizard\Setup_Wizard;

/**
 * The main plugin class.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Plugin extends Simple_Plugin {

	const NAME    = 'Document Library Lite';
	const ITEM_ID = 425625;

	/**
	 * Services array.
	 *
	 * @var array $services
	 */
	private $services;

	/**
	 * Constructs and initializes the Document Library plugin instance.
	 *
	 * @param string $file    The main plugin __FILE__
	 * @param string $version The current plugin version
	 */
	public function __construct( $file = null, $version = '1.0' ) {
		parent::__construct(
			[
				'id'                 => self::ITEM_ID,
				'name'               => self::NAME,
				'version'            => $version,
				'file'               => $file,
				'settings_path'      => 'admin.php?page=document_library',
				'documentation_path' => 'kb/document-library-wordpress-documentation/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings',
			]
		);
	}

	/**
	 * Load the plugin.
	 */
	public function maybe_load_plugin() {
		// Don't load plugin if a paid plan (Pro, Essentials or Advanced) is active.
		if ( ! $this->is_paid_plan_active() ) {
			add_action( 'after_setup_theme', [ $this, 'start_standard_services' ] );
		}
	}

	/**
	 * Whether any paid Document Library plan is loaded.
	 *
	 * Checks the helper function each plan defines in its own namespace. The license status
	 * is deliberately ignored: an unlicensed plan is still loaded and would clash with Lite.
	 *
	 * @return bool
	 */
	private function is_paid_plan_active() {
		$functions = [
			'\\Barn2\\Plugin\\Document_Library_Pro\\document_library_pro',
			'\\Barn2\\Plugin\\Document_Library_Essentials\\document_library_pro',
			'\\Barn2\\Plugin\\Document_Library_Advanced\\document_library_pro',
		];

		foreach ( $functions as $function ) {
			if ( function_exists( $function ) ) {
				return true;
			}
		}

		return false;
	}

	public function add_services() {
		$this->add_service( 'plugin_setup', new Plugin_Setup( $this->get_file(), $this ), true );
		$this->add_service( 'ajax_handler', new Table\Ajax_Handler() );
		$this->add_service( 'wizard', new Setup_Wizard( $this ) );
		$this->add_service( 'post_type', new Post_Type() );
		$this->add_service( 'taxonomies', new Taxonomies() );
		$this->add_service( 'shortcode', new Document_Library_Shortcode() );
		$this->add_service( 'scripts', new Frontend_Scripts( $this ) );
		$this->add_service( 'review_notice', new Review_Notice( $this ) );
		$this->add_service( 'admin', new Admin\Admin_Controller( $this ) );
		$this->add_service(
			'helpscout_beacon',
			new Help_Scout_Beacon(
				$this,
				[
					'plugin_slug'  => 'document-library-pro',
					'screen_hooks' => [
						'toplevel_page_document_library',
						'documents_page_dlp_import',
						'documents_page_dll_protect',
					],
					'capability'   => 'manage_options',
				]
			)
		);
	}
}
