<?php

namespace Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\License\Admin;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Core_Service;
/**
 * Renders a blocking overlay on plugin admin screens when the license is expired.
 *
 * The overlay covers the underlying settings UI and presents a license-key
 * activation form. The activation form reuses the existing
 * {@see License_Key_Setting::process_license_action()} handler, so no new
 * endpoint or security pathway is introduced.
 *
 * The existing top-of-page expired-license notice (from {@see License_Notices})
 * is suppressed on screens where the overlay renders.
 *
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 * @version   1.0
 */
class License_Expired_Overlay implements Registerable, Core_Service
{
    /**
     * The licensed plugin instance.
     *
     * @var Licensed_Plugin
     */
    private $plugin;
    /**
     * Screen specs where the overlay should render.
     *
     * Each entry may be:
     *   - string $hook_suffix (e.g. 'woocommerce_page_wc-settings'), OR
     *   - [ 'hook' => $hook, 'tab' => $tab, 'section' => $section, 'page' => $page, 'target' => $css_selector ]
     *     where 'hook'/'tab'/'section'/'page' are matched (omit = wildcard) and
     *     'target' is the CSS selector the overlay is rendered inside (omit = fullscreen).
     *
     * @var array
     */
    private $screens;
    /**
     * Target CSS selector for the currently matched screen, or '' if fullscreen.
     *
     * @var string
     */
    private $matched_target = '';
    /**
     * Constructor.
     *
     * @param Licensed_Plugin $plugin  The licensed plugin instance.
     * @param array           $screens Screen specs where the overlay should render.
     */
    public function __construct(Licensed_Plugin $plugin, array $screens = [])
    {
        $this->plugin = $plugin;
        $this->screens = $screens;
    }
    /**
     * Register the overlay hooks.
     *
     * @return void
     */
    public function register()
    {
        if (\wp_doing_ajax() || \wp_doing_cron()) {
            return;
        }
        \add_action('current_screen', [$this, 'maybe_attach']);
    }
    /**
     * Attach the overlay hooks when the current screen should render it.
     *
     * @return void
     */
    public function maybe_attach()
    {
        if (!$this->should_render()) {
            return;
        }
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        \add_action('admin_footer', [$this, 'render'], 5);
        \add_filter('barn2_plugin_hide_license_notices', [$this, 'suppress_existing_notice'], 10, 2);
    }
    /**
     * Determine whether the overlay should render on the current screen.
     *
     * @return bool
     */
    public function should_render()
    {
        if (!\is_admin()) {
            return \false;
        }
        $license = $this->plugin->get_license();
        if (!$license || !$license->is_expired()) {
            return \false;
        }
        return $this->is_plugin_screen();
    }
    /**
     * Check whether the current admin screen matches one of the configured specs.
     *
     * @return bool
     */
    private function is_plugin_screen()
    {
        $screens = (array) \apply_filters('barn2_license_overlay_screens_' . $this->plugin->get_id(), $this->screens, $this->plugin);
        if (empty($screens)) {
            return \false;
        }
        if (!\function_exists('get_current_screen')) {
            return \false;
        }
        $current = \get_current_screen();
        if (!$current) {
            return \false;
        }
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only screen detection, no state change.
        $get_tab = isset($_GET['tab']) ? \sanitize_text_field(\wp_unslash($_GET['tab'])) : null;
        $get_section = isset($_GET['section']) ? \sanitize_text_field(\wp_unslash($_GET['section'])) : null;
        $get_page = isset($_GET['page']) ? \sanitize_text_field(\wp_unslash($_GET['page'])) : null;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        foreach ($screens as $spec) {
            if (\is_string($spec)) {
                $spec = ['hook' => $spec];
            }
            if (!\is_array($spec)) {
                continue;
            }
            $hook = isset($spec['hook']) ? $spec['hook'] : '';
            $tab = \array_key_exists('tab', $spec) ? $spec['tab'] : null;
            $section = \array_key_exists('section', $spec) ? $spec['section'] : null;
            $page = \array_key_exists('page', $spec) ? $spec['page'] : null;
            $target = isset($spec['target']) ? (string) $spec['target'] : '';
            if ($hook && $hook !== $current->id) {
                continue;
            }
            if (null !== $tab && $tab !== $get_tab) {
                continue;
            }
            if (null !== $section && $section !== $get_section) {
                continue;
            }
            if (null !== $page && $page !== $get_page) {
                continue;
            }
            $this->matched_target = $target;
            return \true;
        }
        return \false;
    }
    /**
     * Suppress the existing expired-license notice for this plugin.
     *
     * @param bool   $hide   Whether the notice is currently hidden.
     * @param object $plugin The plugin the notice belongs to.
     * @return bool
     */
    public function suppress_existing_notice($hide, $plugin)
    {
        if ($plugin && \method_exists($plugin, 'get_id') && $plugin->get_id() === $this->plugin->get_id()) {
            return \true;
        }
        return $hide;
    }
    /**
     * Enqueue the overlay styles and scripts.
     *
     * @return void
     */
    public function enqueue_assets()
    {
        $version = $this->plugin->get_version();
        \wp_enqueue_style('barn2-license-expired-overlay', \plugins_url('dependencies/barn2/barn2-lib/build/css/barn2-license-expired-overlay-styles.css', $this->plugin->get_file()), [], $version);
        \wp_enqueue_script('barn2-license-expired-overlay', \plugins_url('dependencies/barn2/barn2-lib/build/js/admin/barn2-license-expired-overlay.js', $this->plugin->get_file()), [], $version, \true);
    }
    /**
     * Render the overlay markup in the admin footer.
     *
     * @return void
     */
    public function render()
    {
        $plugin = $this->plugin;
        $license = $plugin->get_license();
        $setting_name = $plugin->get_license_setting()->get_license_setting_name();
        $target_selector = $this->matched_target;
        // Host plugin's template wins; fall back to barn2-lib's default.
        $default_template = $plugin->get_dir_path() . 'templates/license-expired-overlay.php';
        if (!\is_readable($default_template)) {
            $default_template = \dirname(__DIR__, 4) . '/templates/license-expired-overlay.php';
        }
        $template = \apply_filters('barn2_license_expired_overlay_template', $default_template, $plugin);
        // Bail if no readable template was found.
        if (!\is_string($template) || !\is_readable($template)) {
            return;
        }
        \ob_start();
        include $template;
        $markup = \ob_get_clean();
        echo \apply_filters('barn2_license_expired_overlay_markup', $markup, $plugin);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
