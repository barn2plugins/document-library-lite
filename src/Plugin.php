<?php

namespace Barn2\Plugin\Document_Library;

use Barn2\DLW_Lib\Plugin\Simple_Plugin;
use Barn2\DLW_Lib\Registerable;
use Barn2\DLW_Lib\Service;
use Barn2\DLW_Lib\Service_Provider;
use Barn2\DLW_Lib\Util;

/**
 * The main plugin class.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Plugin extends Simple_Plugin implements Registerable, Service_Provider {

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
				'documentation_path' => 'kb/document-library-wordpress-documentation/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings'
			]
		);

		// Services
		$this->services['post_type']     = new Post_Type();
		$this->services['taxonomies']    = new Taxonomies();
		$this->services['shortcode']     = new Document_Library_Shortcode();
		$this->services['scripts']       = new Frontend_Scripts( $this );
		$this->services['review_notice'] = new Review_Notice( $this );

		// Admin only services
		if ( Util::is_admin() ) {
			$this->services['admin'] = new Admin\Admin_Controller( $this );
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		$plugin_setup = new Admin\Plugin_Setup( $this->get_file(), $this );
		$plugin_setup->register();

		add_action( 'init', [ $this, 'maybe_load_plugin' ] );
	}

	/**
	 * Load the plugin.
	 */
	public function maybe_load_plugin() {
		// Don't load plugin if Pro version active
		if ( function_exists( '\Barn2\Plugin\Document_Library_Pro\document_library_pro' ) ) {
			return;
		}

		add_action( 'init', [ $this, 'load_textdomain' ] );

		Util::register_services( $this->services );
	}

	/**
	 * Load the textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'document-library-lite', false, $this->get_slug() . '/languages' );
	}

	/**
	 * Retrieve a plugin service.
	 *
	 * @param string $id
	 * @return Service
	 */
	public function get_service( $id ) {
		if ( isset( $this->services[ $id ] ) ) {
			return $this->services[ $id ];
		}

		return null;
	}

	/**
	 * Retrieve the plugin services.
	 *
	 * @return array
	 */
	public function get_services() {
		return $this->services;
	}

}
