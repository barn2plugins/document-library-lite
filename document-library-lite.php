<?php
/**
 * The main plugin file for Document Library Lite.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 *
 * @wordpress-plugin
 * Plugin Name:     Document Library Lite - Manage & Display Downloads
 * Plugin URI:      https://wordpress.org/plugins/document-library-lite/
 * Description:     Add documents and display them in a searchable document library.
 * Version:         1.1.0
 * Author:          Barn2 Plugins
 * Author URI:      https://barn2.com
 * Text Domain:     document-library-lite
 * Domain Path:     /languages
 * 
 * Requires at least:     6.1
 * Requires PHP:          7.4
 *
 * Copyright:       Barn2 Media Ltd
 * License:         GNU General Public License v3.0
 * License URI:     https://www.gnu.org/licenses/gpl.html
 */

namespace Barn2\Plugin\Document_Library;

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PLUGIN_VERSION = '1.1.0';
const PLUGIN_FILE    = __FILE__;

// Autoloader.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Helper function to access the shared plugin instance.
 *
 * @return Plugin
 */
function document_library() {
	return Plugin_Factory::create( PLUGIN_FILE, PLUGIN_VERSION );
}

// Load the plugin.
document_library()->register();
