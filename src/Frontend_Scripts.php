<?php

namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Plugin;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Util;
use Barn2\Plugin\Document_Library\Util\Options;

/**
 * Registers the frontend styles and scripts for the post tables.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Frontend_Scripts implements Registerable, Standard_Service {

	const DATATABLES_VERSION = '1.11.3';
	const PHOTOSWIPE_VERSION = '4.1.3';

	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
	}

	/**
	 * Register the frontend styles.
	 */
	public function register_styles() {

		wp_register_style( 'jquery-datatables-dlw', plugins_url( 'assets/js/datatables/datatables.min.css', $this->plugin->get_file() ), [], self::DATATABLES_VERSION );
		wp_register_style( 'document-library', plugins_url( 'assets/css/document-library-main.css', $this->plugin->get_file() ), [ 'jquery-datatables-dlw' ], $this->plugin->get_version() );
		wp_register_style( 'photoswipe', plugins_url( 'assets/js/photoswipe/photoswipe.min.css', $this->plugin->get_file() ), [], self::PHOTOSWIPE_VERSION );
		wp_register_style( 'photoswipe-default-skin', plugins_url( 'assets/js/photoswipe/default-skin/default-skin.min.css', $this->plugin->get_file() ), [ 'photoswipe' ], self::PHOTOSWIPE_VERSION );
	}

	/**
	 * Register the frontend scripts.
	 */
	public function register_scripts() {
		$suffix = Util::get_script_suffix();

		wp_register_script( 'jquery-datatables-dlw', plugins_url( "assets/js/datatables/datatables{$suffix}.js", $this->plugin->get_file() ), [ 'jquery' ], self::DATATABLES_VERSION, true );
		wp_register_script( 'document-library', plugins_url( 'assets/js/document-library-main.js', $this->plugin->get_file() ), [ 'jquery', 'jquery-datatables-dlw' ], $this->plugin->get_version(), true );
		wp_register_script( 'photoswipe', plugins_url( 'assets/js/photoswipe/photoswipe.min.js', $this->plugin->get_file() ), [], self::PHOTOSWIPE_VERSION, true );
		wp_register_script( 'photoswipe-ui-default', plugins_url( 'assets/js/photoswipe/photoswipe-ui-default.min.js', $this->plugin->get_file() ), [ 'photoswipe' ], self::PHOTOSWIPE_VERSION, true );

		$script_params = [
			'language' => apply_filters(
				'document_library_lite_language_defaults',
				[
					'infoFiltered'      => __( '(_MAX_ in total)', 'document-library-lite' ),
					'lengthMenu'        => __( 'Show _MENU_ entries', 'document-library-lite' ),
					'search'            => apply_filters( 'document_library_lite_search_label', __( 'Search:', 'document-library-lite' ) ),
					'loadingRecords'    => __( 'Loading...', 'document-library-lite' ),
					'searchPlaceholder' => apply_filters( 'document_library_lite_search_placeholder', '' ),
					'emptyTable'        => __( 'No data available in table', 'document-library-lite' ),
					'paginate'          => [
						'first'    => __( 'First', 'document-library-lite' ),
						'last'     => __( 'Last', 'document-library-lite' ),
						'next'     => __( 'Next', 'document-library-lite' ),
						'previous' => __( 'Previous', 'document-library-lite' ),
					],
					'info'              => __( 'Showing _START_ to _END_ of _TOTAL_ entries', 'document-library-lite' ),
					'infoEmpty'         => __( 'Showing 0 to 0 of 0 entries', 'document-library-lite' ),
					'thousands'         => _x( ',', 'thousands separator', 'document-library-lite' ),
					'decimal'           => _x( '.', 'decimal mark', 'document-library-lite' ),
					'aria'              => [
						/* translators: ARIA text for sorting column in ascending order */
						'sortAscending'  => __( ': activate to sort column ascending', 'document-library-lite' ),
						/* translators: ARIA text for sorting column in descending order */
						'sortDescending' => __( ': activate to sort column descending', 'document-library-lite' ),
					],
					'zeroRecords'       => __( 'No matching records found', 'document-library-lite' ),
					'filterBy'          => apply_filters( 'document_library_lite_search_filter_label', '' ),
					'emptyFilter'       => __( 'No results found', 'document-library-lite' ),
					'resetButton'       => apply_filters( 'document_library_lite_reset_button', __( 'Reset', 'document-library-lite' ) ),
				]
			),
		];

		wp_add_inline_script(
			'document-library',
			sprintf( 'var data_table_params = %s;', wp_json_encode( apply_filters( 'document_library_script_params', $script_params ) ) ),
			'before'
		);
	}

	/**
	 * Load photoswipe assets.
	 *
	 * @param book $enabled
	 */
	public static function load_photoswipe_resources( $enabled ) {
		if ( ! $enabled ) {
			return;
		}

		wp_enqueue_style( 'photoswipe-default-skin' );
		wp_enqueue_script( 'photoswipe-ui-default' );

		add_action( 'wp_footer', [ self::class, 'load_photoswipe_template' ] );
	}

	/**
	 * Load photoswipe template.
	 */
	public static function load_photoswipe_template() {
		if ( $located = locate_template( 'dlw_templates/photoswipe.php', false ) ) {
			include_once $located;
		} else {
			include_once plugin_dir_path( PLUGIN_FILE ) . 'templates/photoswipe.php';
		}
	}
}
