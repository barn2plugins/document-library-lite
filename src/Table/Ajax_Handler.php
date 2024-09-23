<?php

namespace Barn2\Plugin\Document_Library\Table;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Util;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\Document_Library\Frontend_Scripts;
use Barn2\Plugin\Document_Library\Simple_Document_Library;
use Barn2\Plugin\Document_Library\Util\Options;

/**
 * The main plugin class.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Ajax_Handler implements Standard_Service {

    const SHORTCODE = 'doc_library';

    public function __construct() {
        add_action( 'wp_ajax_dll_load_posts', [ $this, 'load_posts' ] );
        add_action( 'wp_ajax_nopriv_dll_load_posts', [ $this, 'load_posts' ] );

    }
    
    public function register() {
    }

    public function load_posts() {
		$args = Options::handle_shortcode_attribute_aliases( $_POST[ 'args' ] );
        $args = shortcode_atts( Options::get_defaults(), $args, self::SHORTCODE );

        $table = new simple_Document_Library( $args );
        $response = $table->get_table( 'array' );

        // Return the response as JSON
        wp_send_json($response);   
    
    }

}
