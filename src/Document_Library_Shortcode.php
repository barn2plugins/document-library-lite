<?php
namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Util\Options;
use Barn2\Plugin\Document_Library\Table\Config_Builder;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;

/**
 * This class handles the posts table shortcode registration.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Document_Library_Shortcode implements Registerable, Standard_Service {

	const SHORTCODE = 'doc_library';

	/**
	 * Stores the number of tables on this page. Used to generate the table ID.
	 *
	 * @var int
	 */
	private static $table_count = 1;

	/**
	 * Stores script params for all tables on the page.
	 *
	 * @var array
	 */
	private static $script_params = [];

	/**
	 * Tracks whether the global grid AJAX params have been localized.
	 *
	 * @var bool
	 */
	private static $grid_params_printed = false;

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_shortcode( self::SHORTCODE, [ $this, 'do_shortcode' ] );
		add_action( 'wp_footer', [ $this, 'print_script_params' ], 5 );
	}

	/**
	 * Handles our document library shortcode.
	 *
	 * @param array $atts The shortcode attributes specified by the user.
	 * @param string $content The content between the open and close shortcode tags (not used)
	 * @return string The shortcode output
	 */
	public function do_shortcode( $atts, $content = '' ) {
		// Parse attributes
		$atts = Options::handle_shortcode_attribute_aliases( $atts );
		$atts = shortcode_atts( Options::get_defaults(), $atts, self::SHORTCODE );

		// Store the configuration securely and get a unique ID
		$table_id = Config_Builder::store( $atts );

		// Render a grid instead of a table when the layout is set to 'grid'.
		if ( isset( $atts['layout'] ) && $atts['layout'] === 'grid' ) {
			return $this->do_grid_shortcode( $atts, $table_id );
		}

		$table = new Simple_Document_Library( $atts, $table_id );
		
		// Determine sort order - if not set or empty, use automatic based on sort_by
		$sort_order = $table->args['sort_order'];
		if ( empty( $sort_order ) || ! in_array( $sort_order, [ 'asc', 'desc' ], true ) ) {
			$sort_order = ( $table->get_orderby() === 'date' ) ? 'desc' : 'asc';
		}

		// Load the scripts and styles.
		if ( apply_filters( 'document_library_table_load_scripts', true ) ) {
			wp_enqueue_style( 'document-library' );
			wp_enqueue_script( 'document-library' );
			
			// Store table-specific params to be output in footer
			self::$script_params[ $table_id ] = [
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'ajax_nonce'  => wp_create_nonce( 'dll_load_posts' ),
				'ajax_action' => 'dll_load_posts',
				'lazy_load'   => $table->args['lazy_load'],
				'columns'	  => $table->get_columns(),
				'table_id'    => $table_id,
				'sort_by'     => $table->get_orderby(),
				'sort_order'  => $sort_order,
			];
		}

		Frontend_Scripts::load_photoswipe_resources( $table->args['lightbox'] );

		// Create table and return output
		ob_start(); ?>
		<input type="hidden" name="category-search-<?php echo esc_attr( $table_id ) ?>" value="<?php echo esc_attr( $table->args['doc_category'] ); ?>" class="category-search-<?php echo esc_attr( $table_id ) ?>">
		<table <?php echo $table->get_attributes() ?>>
			<?php
			echo $table->get_headers();
			?>
			<tbody>
				<?php
				if( ! $table->args['lazy_load'] ) {
					echo $table->get_table( 'html' );
				}
				?>
			</tbody>
		</table>
		<?php 
		return ob_get_clean();
	}

	/**
	 * Handles rendering the document library as a grid.
	 *
	 * @param array  $atts     The parsed shortcode attributes.
	 * @param string $table_id The stored configuration ID.
	 * @return string The grid HTML output.
	 */
	private function do_grid_shortcode( $atts, $table_id ) {
		$grid = new Simple_Document_Grid( $atts, $table_id );

		if ( apply_filters( 'document_library_grid_load_scripts', true ) ) {
			wp_enqueue_style( 'document-library-grid' );
			wp_enqueue_script( 'document-library-grid' );

			// Localize the shared grid AJAX params once per page.
			if ( ! self::$grid_params_printed ) {
				wp_localize_script(
					'document-library-grid',
					'document_library_grid_params',
					apply_filters(
						'document_library_grid_script_params',
						[
							'ajax_url'    => admin_url( 'admin-ajax.php' ),
							'ajax_nonce'  => wp_create_nonce( 'dll_load_grid' ),
							'ajax_action' => 'dll_load_grid',
						]
					)
				);

				self::$grid_params_printed = true;
			}
		}

		Frontend_Scripts::load_photoswipe_resources( $grid->args['lightbox'] );

		return $grid->get_container();
	}

	/**
	 * Print script params in the footer before scripts are printed.
	 */
	public function print_script_params() {
		if ( ! empty( self::$script_params ) && wp_script_is( 'document-library', 'enqueued' ) ) {
			// Output params for each table separately
			foreach ( self::$script_params as $table_id => $params ) {
				wp_localize_script(
					'document-library',
					'document_library_params_' . sanitize_key( $table_id ),
					apply_filters( 'document_library_script_params', $params, $table_id )
				);
			}
		}
	}

}
