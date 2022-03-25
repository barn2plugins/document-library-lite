<?php
namespace Barn2\Plugin\Document_Library\Admin\Page;

use Barn2\DLW_Lib\Registerable,
	Barn2\DLW_Lib\Service,
	Barn2\DLW_Lib\Conditional,
	Barn2\DLW_Lib\Plugin\Plugin,
	Barn2\DLW_Lib\Util as Lib_Util;

defined( 'ABSPATH' ) || exit;

/**
 * This class handles our plugin import page in the admin.
 *
 * @package   Barn2/document-library-for-wordpress
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Import implements Service, Registerable, Conditional {

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
	public function is_required() {
		return Lib_Util::is_admin();
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_action( 'admin_menu', [ $this, 'add_import_page' ] );
	}

	/**
	 * Add the Import sub menu page.
	 */
	public function add_import_page() {
		add_submenu_page(
			'document_library',
			__( 'Document Library Importing', 'document-library-for-wordpress' ),
			__( 'Import', 'document-library-for-wordpress' ),
			'manage_options',
			'dlp_import',
			[ $this, 'render' ],
			11
		);
	}

	/**
	 * Render the import page.
	 */
	public function render() {
		?>
		<div class="wrap dlw-settings">
			<h1><?php esc_html_e( 'Import documents', 'document-library-for-wordpress' ); ?></h1>

			<?php
			printf(
				'<div class="promo-wrapper"><p class="promo">' .
				/* translators: %1: Document Library Pro link start, %2: Document Library Pro link end */
				esc_html__( 'Lots of documents? Upgrade to %1$sDocument Library Pro%2$s to add documents in bulk - either by using drag-and-drop to upload documents directly to WordPress, or by importing documents from a CSV file. It’s a fantastic time-saver for adding multiple documents at once.', 'document-library-for-wordpress' ) .
				'</p>' .
				'<img class="promo" src="%3$s" /></div>',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				Lib_Util::format_link_open( Lib_Util::barn2_url( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings' ), true ),
				'</a>',
				esc_url( $this->plugin->get_dir_url() . '/assets/images/promo-import.png' )
			);
			?>

		<?php
	}
}
