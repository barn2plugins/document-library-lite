<?php
namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Util\Options;
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
	 * {@inheritdoc}
	 */
	public function register() {
		add_shortcode( self::SHORTCODE, [ $this, 'do_shortcode' ] );
	}

	/**
	 * Handles our document library shortcode.
	 *
	 * @param array $atts The shortcode attributes specified by the user.
	 * @param string $content The content between the open and close shortcode tags (not used)
	 * @return string The shortcode output
	 */
	public function do_shortcode( $atts, $content = '' ) {
		// Load the scripts and styles.
		if ( apply_filters( 'document_library_table_load_scripts', true ) ) {
			wp_enqueue_style( 'document-library' );
			wp_enqueue_script( 'document-library' );
		}
		
		// Parse attributes
		$atts = Options::handle_shortcode_attribute_aliases( $atts );
		$atts = shortcode_atts( Options::get_defaults(), $atts, self::SHORTCODE );
		$table = new Simple_Document_Library( $atts );
		
		Frontend_Scripts::load_photoswipe_resources( $table->args['lightbox'] );

		// Create table and return output
		ob_start(); ?>

		<table <?php echo $table->get_attributes() ?>>
			<?php
			echo $table->get_headers();
			?>
			<tbody>
				<?php
				if( ! $atts[ 'lazy_load' ] ) {
					echo $table->get_table( 'html' );
				}
				?>
			</tbody>
		</table>
		
		<?php 
		return ob_get_clean();
	}

}
