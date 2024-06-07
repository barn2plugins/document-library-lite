<?php

namespace Barn2\Plugin\Document_Library\Admin\Settings_Tab;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Admin\Settings_API_Helper;
use	Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;

/**
 * Document Table Setting Tab
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Document_Grid implements Registerable {
	const TAB_ID       = 'document_grid';
	const OPTION_GROUP = 'document_library_pro_grid';
	const MENU_SLUG    = 'dlp-settings-grid';

	private $plugin;
	private $id;
	private $title;
	private $default_settings;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin           = $plugin;
		$this->id               = 'document_grid';
		$this->title            = __( 'Document Grid', 'document-library-lite' );
		$this->default_settings = [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register the Settings with WP Settings API.
	 */
	public function register_settings() {

		Settings_API_Helper::add_settings_section(
			'dlp_grid_title',
			self::MENU_SLUG,
			'',
			[ $this, 'grid_settings_title' ],
			[]
		);

	}

	/**
	 * Get the Settings Tab description.
	 */
	public function grid_settings_title() {
		printf(
			'<div class="promo-wrapper"><p class="promo">' .
			/* translators: %1: Document Library Pro link start, %2: Document Library Pro link end */
			esc_html__( 'Upgrade to %1$sDocument Library Pro%2$s to display documents in a beautiful grid layout:', 'document-library-lite' ) .
			'</p>' .
			'<a class="promo" href="%3$s" target="_blank"><img class="promo" src="%4$s" />%2$s</div>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::format_link_open( Lib_Util::barn2_url( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings' ), true ),
			'</a>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			Lib_Util::barn2_url( 'wordpress-plugins/document-library-pro/?utm_source=settings&utm_medium=settings&utm_campaign=settingsinline&utm_content=dlw-settings' ),
			esc_url( $this->plugin->get_dir_url() . '/assets/images/promo-grid.png' )
		);
	}


	/**
	 * Get the Tab title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Get the Tab ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}
}
