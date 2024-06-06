<?php

namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Simple_Plugin;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Util;
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
				'documentation_path' => 'kb/document-library-wordpress-documentation/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings'
			]
		);
	}

	/**
	 * Load the plugin.
	 */
	public function maybe_load_plugin() {
		// Don't load plugin if Pro version active
		if ( ! Util::is_barn2_plugin_active('\\Barn2\\Plugin\\Posts_Table_Pro\\dlp') ) {
      add_action('after_setup_theme', [$this, 'start_standard_services']);
		}
	}

	public function add_services() {
		$this->add_service( 'plugin_setup', new Plugin_Setup( $this->get_file(), $this ), true );
		$this->add_service( 'wizard', new Setup_Wizard( $this ) );
		$this->add_service( 'post_type', new Post_Type() );
		$this->add_service( 'taxonomies', new Taxonomies() );
		$this->add_service( 'shortcode', new Document_Library_Shortcode() );
		$this->add_service( 'scripts', new Frontend_Scripts( $this ) );
		$this->add_service( 'review_notice', new Review_Notice( $this ) );
		$this->add_service( 'admin', new Admin\Admin_Controller( $this ) );

	}

}
