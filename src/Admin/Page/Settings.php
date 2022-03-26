<?php
namespace Barn2\Plugin\Document_Library\Admin\Page;

use Barn2\Plugin\Document_Library\Admin\Settings_Tab,
	Barn2\DLW_Lib\Plugin\Plugin,
	Barn2\DLW_Lib\Registerable,
	Barn2\DLW_Lib\Service,
	Barn2\DLW_Lib\Conditional,
	Barn2\DLW_Lib\Util;

defined( 'ABSPATH' ) || exit;

/**
 * This class handles our plugin settings page in the admin.
 *
 * @package   Barn2/document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Settings implements Service, Registerable, Conditional {

	const MENU_SLUG = 'document_library';

	private $plugin;
	private $registered_settings = [];

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin              = $plugin;
		$this->registered_settings = $this->get_settings_tabs();
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_required() {
		return Util::is_admin();
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		$this->register_settings_tabs();

		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
	}

	/**
	 * Retrieves the settings tab classes.
	 *
	 * @return array
	 */
	private function get_settings_tabs() {
		$settings_tabs = [
			Settings_Tab\General::TAB_ID         => new Settings_Tab\General( $this->plugin ),
			Settings_Tab\Document_Table::TAB_ID  => new Settings_Tab\Document_Table( $this->plugin ),
			Settings_Tab\Document_Grid::TAB_ID   => new Settings_Tab\Document_Grid( $this->plugin ),
			Settings_Tab\Single_Document::TAB_ID => new Settings_Tab\Single_Document( $this->plugin ),
		];

		return $settings_tabs;
	}

	/**
	 * Register the settings tab classes.
	 */
	private function register_settings_tabs() {
		array_map(
			function( $setting_tab ) {
				if ( $setting_tab instanceof Registerable ) {
					$setting_tab->register();
				}
			},
			$this->registered_settings
		);
	}

	/**
	 * Register the Settings submenu page.
	 */
	public function add_settings_page() {
		add_submenu_page(
			'document_library',
			__( 'Document Library Lite Settings', 'document-library-lite' ),
			__( 'Settings', 'document-library-lite' ),
			'manage_options',
			'document_library',
			[ $this, 'render_settings_page' ],
			10
		);
	}

	/**
	 * Render the Settings page.
	 */
	public function render_settings_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		?>
		<div class="wrap barn2-plugins-settings">

			<?php if ( in_array( $active_tab, [ 'general', 'document_libraries' ], true ) ) { ?>
				<?php do_action( 'barn2_before_plugin_settings' ); ?>
			<?php } ?>

			<div class="barn2-settings-inner dlw-settings">
				<h1><?php esc_html_e( 'Document Library Settings', 'document-library-lite' ); ?></h1>

				<h2 class="nav-tab-wrapper">
					<?php
					foreach ( $this->registered_settings as $setting_tab ) {
						$active_class = $active_tab === $setting_tab->get_id() ? ' nav-tab-active' : '';
						?>
						<a href="<?php echo esc_url( add_query_arg( 'tab', $setting_tab->get_id(), $this->plugin->get_settings_page_url() ) ); ?>" class="<?php echo esc_attr( sprintf( 'nav-tab%s', $active_class ) ); ?>">
							<?php echo esc_html( $setting_tab->get_title() ); ?>
						</a>
						<?php
					}
					?>
				</h2>

				<form action="options.php" method="post">
					<?php
					settings_errors( 'document-library-lite' );
					settings_fields( $this->registered_settings[ $active_tab ]::OPTION_GROUP );
					do_settings_sections( $this->registered_settings[ $active_tab ]::MENU_SLUG );
					?>

					<?php if ( in_array( $active_tab, [ 'general', 'document_libraries' ], true ) ) { ?>
						<p class="submit">
							<input name="Submit" type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'document-library-lite' ); ?>" />
						</p>
					<?php } ?>
				</form>
			</div>

			<?php if ( in_array( $active_tab, [ 'general', 'document_libraries' ], true ) ) { ?>
				<?php do_action( 'barn2_after_plugin_settings', $this->plugin->get_id() ); ?>
			<?php } ?>

		</div>
		<?php
	}
}
