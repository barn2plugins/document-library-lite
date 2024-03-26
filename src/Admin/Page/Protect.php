<?php
namespace Barn2\Plugin\Document_Library\Admin\Page;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable,
	Barn2\Plugin\Document_Library\Dependencies\Lib\Service,
	Barn2\Plugin\Document_Library\Dependencies\Lib\Conditional,
	Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Plugin,
	Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;

defined( 'ABSPATH' ) || exit;

/**
 * This class handles our plugin protect page in the admin.
 *
 * @package   Barn2/document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Protect implements Service, Registerable, Conditional {

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
		if ( Lib_Util::is_barn2_plugin_active( 'Barn2\Plugin\Password_Protected_Categories\ppc' ) ) {
			return;
		}

		add_action( 'admin_menu', [ $this, 'add_protect_page' ] );
	}

	/**
	 * Add the Import sub menu page.
	 */
	public function add_protect_page() {
		add_submenu_page(
			'document_library',
			__( 'Protect', 'document-library-lite' ),
			__( 'Protect', 'document-library-lite' ),
			'manage_options',
			'dlp_protect',
			[ $this, 'render' ],
			12
		);
	}

	/**
	 * Render the import page.
	 */
	public function render() {
		?>
		<div class="wrap dlw-settings">
			<h1><?php esc_html_e( 'Create private document libraries with Password Protected Categories', 'document-library-lite' ); ?></h1>

			<?php
			printf(
				'<div class="promo-wrapper">
					<p class="promo">' .
						/* translators: %1: Document Library Pro link start, %2: Document Library Pro link end */
						esc_html__( 'Do you need to control who can access your documents? You can easily do this with the %1$sPassword Protected Categories%2$s plugin', 'document-library-lite' ) .
					'</p>
					<p class="promo">' .
						esc_html__( 'Password Protected Categories lets you restrict access to any or all of your document categories - either to specific users, roles, or to anyone with the password.', 'document-library-lite' ) .
					'</p>
					<a class="promo" href="%3$s" target="_blank"><img class="promo" src="%4$s" /></a>
				</div>',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				Lib_Util::format_link_open( Lib_Util::barn2_url( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings' ), true ),
				'</a>',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				Lib_Util::barn2_url( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings' ),
				esc_url( $this->plugin->get_dir_url() . '/assets/images/promo-grid.png' )
			);
			?>

		<?php
	}
}
