<?php

namespace Barn2\Plugin\Document_Library\Dependencies\Lib\Admin;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Plugin;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Standard_Service;
/**
 * Renders the Help Scout Beacon on a plugin's admin screen(s).
 *
 * Loads the Beacon passively — no Beacon('identify') call is made, so no
 * customer data leaves the site unless the customer submits a message
 * themselves. Article suggestions are pulled from the Barn2 KB using the
 * existing featured-plugin taxonomy + the {featured} flag, so there is no
 * duplicate tagging.
 *
 * The Beacon's built-in search box is also restricted to the current plugin's
 * KB categories: search and preview XHRs are intercepted client-side and
 * redirected at the Barn2 KB with the plugin's hkb-tax filter, so customers
 * don't see unrelated articles from other plugins unless the restricted
 * search fails, in which case search falls back to the full Barn2 KB.
 *
 * A small inline stylesheet is also enqueued so the Beacon panel is never
 * hidden behind the WordPress admin bar on shorter screens (see
 * get_admin_bar_css()).
 *
 * Usage in a plugin's add_services() (or equivalent bootstrap):
 *
 *     $this->add_service(
 *         'helpscout_beacon',
 *         new \Barn2\Plugin\My_Plugin\Dependencies\Lib\Admin\Help_Scout_Beacon(
 *             $this,
 *             [
 *                 'screen_hooks' => [ 'my_settings_hook' ],   // $hook_suffix value(s) from admin_enqueue_scripts.
 *                 'capability'   => 'manage_options',         // Capability required to see the Beacon.
 *                 // 'plugin_slug' => 'my-plugin-slug',       // Optional. Override the directory-derived slug
 *                 //                                          // when the barn2.com download slug differs.
 *             ]
 *         )
 *     );
 *
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Help_Scout_Beacon implements Standard_Service, Registerable
{
    const BEACON_ID = 'ec3e5e00-934c-4c64-989a-551593ff2f0e';
    const KB_API_URL = 'https://barn2.com/wp-json/barn2/v3/kb/helpscout';
    const CACHE_TTL_SUCCESS = \DAY_IN_SECONDS;
    const CACHE_TTL_FAILURE = \HOUR_IN_SECONDS;
    const REMOTE_TIMEOUT = 2;
    /**
     * @var Plugin
     */
    private $plugin;
    /**
     * @var array
     */
    private $args;
    /**
     * @var string Barn2.com download slug for the plugin (e.g. "woocommerce-product-options").
     */
    private $plugin_slug;
    /**
     * @var string[] List of $hook_suffix values from admin_enqueue_scripts where the Beacon should appear.
     */
    private $screen_hooks;
    /**
     * @var string Capability required to see the Beacon (e.g. "manage_woocommerce" or "manage_options").
     */
    private $capability;
    /**
     * @param Plugin $plugin Plugin instance.
     * @param array  $args   {
     *     @type string   $plugin_slug  Barn2.com download slug. Optional; defaults to the
     *                                  plugin's directory basename via Plugin::get_slug().
     *                                  Pass this when the public download slug differs from
     *                                  the directory name (e.g. multiple plugin variants
     *                                  sharing one barn2.com product slug).
     *     @type string[] $screen_hooks $hook_suffix values where the Beacon should load. Required.
     *     @type string   $capability   Capability required. Defaults to 'manage_options'.
     * }
     */
    public function __construct(Plugin $plugin, array $args)
    {
        $this->plugin = $plugin;
        $this->args = $args;
        $this->plugin_slug = !empty($args['plugin_slug']) ? (string) $args['plugin_slug'] : $this->plugin->get_slug();
        $this->screen_hooks = isset($args['screen_hooks']) && \is_array($args['screen_hooks']) ? \array_values(\array_filter(\array_map('strval', $args['screen_hooks']))) : [];
        $this->capability = isset($args['capability']) ? (string) $args['capability'] : 'manage_options';
    }
    public function register()
    {
        if (empty($this->plugin_slug) || empty($this->screen_hooks)) {
            return;
        }
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    /**
     * Enqueue Beacon assets on the configured screen(s).
     *
     * @param string $hook_suffix Current admin screen hook.
     */
    public function enqueue_assets($hook_suffix)
    {
        if (!\in_array($hook_suffix, $this->screen_hooks, \true)) {
            return;
        }
        if (!\current_user_can($this->capability)) {
            return;
        }
        $tab = \filter_input(\INPUT_GET, 'tab');
        if (isset($this->args['tab']) && $this->args['tab'] !== $tab) {
            // The current tab is not the one where the beacon should appear.
            return;
        }
        $section = \filter_input(\INPUT_GET, 'section');
        if (isset($this->args['section']) && $this->args['section'] !== $section) {
            // The current section is not the one where the beacon should appear.
            return;
        }
        /**
         * Filter whether to render the Help Scout Beacon for this plugin.
         *
         * @param bool   $should_render Default true.
         * @param string $plugin_slug   The plugin slug.
         */
        if (!\apply_filters('barn2_helpscout_beacon_should_render', \true, $this->plugin_slug)) {
            return;
        }
        $config = $this->get_beacon_config();
        if (empty($config)) {
            return;
        }
        $handle = 'barn2-helpscout-beacon';
        \wp_enqueue_script($handle, \plugins_url('dependencies/barn2/barn2-lib/build/js/admin/help-scout-beacon.js', $this->plugin->get_file()), [], $this->plugin->get_version(), \true);
        \wp_add_inline_script($handle, 'var barn2HelpScoutBeacon = ' . \wp_json_encode($config, \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT) . ';', 'before');
        // Keep the Beacon clear of the WordPress admin bar (see get_admin_bar_css()).
        \wp_register_style($handle, \false, [], $this->plugin->get_version());
        \wp_enqueue_style($handle);
        \wp_add_inline_style($handle, $this->get_admin_bar_css());
    }
    /**
     * CSS to stop the Beacon being hidden behind the WordPress admin bar.
     *
     * The admin bar (#wpadminbar) is fixed to the top of the viewport at
     * z-index 99999. The Help Scout Beacon defaults to a lower z-index, so on
     * shorter screens the open panel slides up underneath the admin bar and its
     * header/close button become unreachable. We lift the Beacon above the bar
     * and cap the panel height so its top edge always stays below the bar
     * (32px wide screens, 46px below 783px — matching WP core's own values).
     *
     * The `.hsds-beacon .BeaconContainer` selector is the same one used by the
     * barn2.com theme, so it's known to target the current Beacon markup.
     *
     * @return string
     */
    private function get_admin_bar_css()
    {
        return '.hsds-beacon .BeaconContainer,' . '.hsds-beacon .BeaconFabButtonFrame{z-index:100000 !important;}' . 'body.admin-bar .hsds-beacon .BeaconContainer{max-height:calc(100vh - 132px) !important;}' . '@media screen and (max-width:782px){' . 'body.admin-bar .hsds-beacon .BeaconContainer{max-height:calc(100vh - 146px) !important;}' . '}';
    }
    /**
     * Look up the KB search endpoint + category term IDs for this plugin from barn2.com, with caching.
     *
     * Success cached 24h; failure cached 1h so we don't hammer the API on outage.
     *
     * @return array Empty array on failure; otherwise [beaconId, kbEndpoint, hkbTax].
     */
    private function get_beacon_config()
    {
        $cache_key = 'barn2_helpscout_beacon_' . \md5($this->plugin_slug);
        $cached = \get_transient($cache_key);
        if (\false !== $cached) {
            return \is_array($cached) ? $cached : [];
        }
        $response = \wp_remote_get(\add_query_arg('plugin', \rawurlencode($this->plugin_slug), self::KB_API_URL), ['timeout' => self::REMOTE_TIMEOUT]);
        if (\is_wp_error($response) || 200 !== \wp_remote_retrieve_response_code($response)) {
            \set_transient($cache_key, [], self::CACHE_TTL_FAILURE);
            return [];
        }
        $body = \json_decode(\wp_remote_retrieve_body($response), \true);
        if (!\is_array($body)) {
            \set_transient($cache_key, [], self::CACHE_TTL_FAILURE);
            return [];
        }
        $categories = isset($body['categories']) && \is_array($body['categories']) ? \array_values(\array_filter(\array_map('absint', $body['categories']))) : [];
        $endpoint = isset($body['endpoint']) ? \esc_url_raw($body['endpoint']) : '';
        if (empty($categories) || !$this->is_trusted_endpoint($endpoint)) {
            \set_transient($cache_key, [], self::CACHE_TTL_FAILURE);
            return [];
        }
        $config = ['beaconId' => self::BEACON_ID, 'kbEndpoint' => $endpoint, 'hkbTax' => \implode(',', $categories)];
        \set_transient($cache_key, $config, self::CACHE_TTL_SUCCESS);
        return $config;
    }
    /**
     * Whitelist the KB endpoint host to a Barn2 domain over HTTPS.
     *
     * @param string $url
     * @return bool
     */
    private function is_trusted_endpoint($url)
    {
        if (empty($url)) {
            return \false;
        }
        $parts = \wp_parse_url($url);
        if (empty($parts['host']) || empty($parts['scheme']) || 'https' !== $parts['scheme']) {
            return \false;
        }
        $host = $parts['host'];
        return 'barn2.com' === $host || \strlen($host) > 10 && \substr($host, -10) === '.barn2.com';
    }
}
