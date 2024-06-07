<?php

namespace Barn2\Plugin\Document_Library\Admin\Wizard;

use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Starter as Setup_Wizard_Starter;

/**
 * Setup Wizard Starter
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Starter extends Setup_Wizard_Starter {

	/**
	 * Determine if the conditions to start the wizard are met.
	 *
	 * @return boolean
	 */
	public function should_start() {
		$setup_happened = get_option( 'document-library-lite-setup-wizard_completed' ) ?: false;
		return ! $setup_happened;
	}

	/** 
	 * Add an option so the setup wizard doesn't run after reactivating 
	 * 
	 * @return void
	 */ 
	public function create_option() {
		update_option( "document-library-lite-setup-wizard_completed", true );
	}
}
