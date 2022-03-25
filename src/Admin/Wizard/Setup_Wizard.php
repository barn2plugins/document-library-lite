<?php

namespace Barn2\Plugin\Document_Library\Admin\Wizard;

use Barn2\Plugin\Document_Library\Admin\Wizard\Steps,
	Barn2\DLW_Lib\Plugin\Plugin,
	Barn2\DLW_Lib\Registerable,
	Barn2\DLW_Lib\Util as Lib_Util;

/**
 * Main Setup Wizard Loader
 *
 * @package   Barn2/document-library-for-wordpress
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Setup_Wizard implements Registerable {

	private $plugin;
	private $wizard;

	/**
	 * Constructor.
	 *
	 * @param Licensed_Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {

		$this->plugin = $plugin;

		$steps = [
			new Steps\Welcome(),
			new Steps\Layout(),
			new Steps\Links(),
			new Steps\Behavior(),
			new Steps\Upsell(),
			new Steps\Completed(),
		];

		$wizard = new Wizard( $this->plugin, $steps, false );

		$wizard->configure(
			[
				'skip_url'    => admin_url( 'admin.php?page=document_library' ),
				'premium_url' => 'https://barn2.com/wordpress-plugins/document-library-pro/',
				'utm_id'      => 'dlw',
			]
		);

		$script_dependencies = Lib_Util::get_script_dependencies( $this->plugin, 'document-library-wizard.min.js' );
		$wizard->set_non_wc_asset(
			$plugin->get_dir_url() . 'assets/js/admin/document-library-wizard.min.js',
			$script_dependencies['dependencies'],
			$script_dependencies['version']
		);

		$this->wizard = $wizard;
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		$this->wizard->boot();
	}

}
