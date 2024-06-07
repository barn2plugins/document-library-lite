<?php

namespace Barn2\Plugin\Document_Library\Admin\Wizard;

use Barn2\Plugin\Document_Library\Admin\Wizard\Steps;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Plugin;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Setup_Wizard as Wizard;

/**
 * Main Setup Wizard Loader
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Setup_Wizard implements Registerable, Standard_Service {

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
			new Steps\Upsell( $this->plugin ),
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
		
		$wizard->add_restart_link( '', '' );

		$this->wizard = $wizard;
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		$this->wizard->boot();
	}

}
