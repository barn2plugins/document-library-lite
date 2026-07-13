<?php

namespace Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\License\Admin;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Core_Service;
use WC_Admin_Settings;
use WP_Error;
use WP_REST_Request;
/**
 * Server-side write lock for expired licenses.
 *
 * Companion to {@see License_Expired_Overlay} (UI-only). Blocks the configured
 * write surfaces while the license is expired; reads are untouched so the
 * frontend keeps working.
 *
 * Config (all optional):
 *   - options      string[] Option keys; blocked at `pre_update_option_{key}`.
 *   - post_meta    string[] Meta keys; blocked at add/update/delete metadata.
 *   - ajax_actions string[] Logged-in admin-ajax actions (no `wp_ajax_` prefix); 403 before handler.
 *   - wc_settings  array[]  [ 'tab' => $tab, 'section' => $section, 'notice' => bool ].
 *                           Strips section's fields during save. `notice` shows a WC error.
 *   - rest_routes  array[]  [ 'namespace' => $ns (required), 'route' => $sub (optional), 'methods' => $arr (optional) ].
 *                           Blocks writes to $ns (and $sub when set), matched on whole path segments.
 *                           Default methods = POST/PUT/PATCH/DELETE.
 *   - message      string   403 message for ajax + REST. Default = generic 'barn2' string.
 *
 * Per-plugin filter: `barn2_license_guard_config_{plugin_id}`.
 *
 * @package Barn2\barn2-lib
 */
class License_Expired_Guard implements Registerable, Core_Service
{
    private $plugin;
    private $options;
    private $post_meta;
    private $ajax_actions;
    private $wc_settings;
    private $rest_routes;
    private $message;
    /**
     * Constructor.
     *
     * @param Licensed_Plugin $plugin The licensed plugin instance.
     * @param array           $config Guard config (options, post_meta, ajax_actions, wc_settings, rest_routes, message).
     */
    public function __construct(Licensed_Plugin $plugin, array $config = [])
    {
        $this->plugin = $plugin;
        $config = (array) \apply_filters('barn2_license_guard_config_' . $plugin->get_id(), $config, $plugin);
        $this->options = isset($config['options']) ? (array) $config['options'] : [];
        $this->post_meta = isset($config['post_meta']) ? (array) $config['post_meta'] : [];
        $this->ajax_actions = isset($config['ajax_actions']) ? (array) $config['ajax_actions'] : [];
        $this->wc_settings = isset($config['wc_settings']) ? (array) $config['wc_settings'] : [];
        $this->rest_routes = isset($config['rest_routes']) ? (array) $config['rest_routes'] : [];
        $this->message = isset($config['message']) ? (string) $config['message'] : '';
    }
    /**
     * Register the write-lock hooks when the license is expired.
     *
     * @return void
     */
    public function register()
    {
        if (!$this->is_expired()) {
            return;
        }
        foreach ($this->options as $option) {
            \add_filter('pre_update_option_' . $option, [$this, 'block_option'], 10, 2);
        }
        if (!empty($this->post_meta)) {
            \add_filter('add_post_metadata', [$this, 'block_post_meta'], 10, 4);
            \add_filter('update_post_metadata', [$this, 'block_post_meta'], 10, 4);
            \add_filter('delete_post_metadata', [$this, 'block_post_meta'], 10, 4);
        }
        foreach ($this->ajax_actions as $action) {
            \add_action('wp_ajax_' . $action, [$this, 'deny_ajax'], 0);
        }
        foreach ($this->wc_settings as $spec) {
            $tab = isset($spec['tab']) ? $spec['tab'] : '';
            if ('' === $tab) {
                continue;
            }
            \add_filter('woocommerce_get_settings_' . $tab, [$this, 'block_wc_settings'], 999);
        }
        if (!empty($this->rest_routes)) {
            \add_filter('rest_pre_dispatch', [$this, 'block_rest'], 10, 3);
        }
    }
    /**
     * Whether the plugin's license is currently expired.
     *
     * @return bool
     */
    private function is_expired()
    {
        $license = $this->plugin->get_license();
        return $license && $license->is_expired();
    }
    /**
     * Block an option update by returning the existing stored value.
     *
     * @param mixed $value     The new value being saved (discarded).
     * @param mixed $old_value The current stored value, returned unchanged.
     * @return mixed
     */
    public function block_option($value, $old_value)
    {
        unset($value);
        return $old_value;
    }
    /**
     * Block add/update/delete of configured post meta keys.
     *
     * @param null|bool $check      Short-circuit value passed by the metadata filter.
     * @param int       $object_id  The object ID (unused).
     * @param string    $meta_key   The meta key being written.
     * @param mixed     $meta_value The meta value (unused).
     * @return null|bool False to block a configured key, otherwise the original check.
     */
    public function block_post_meta($check, $object_id, $meta_key, $meta_value)
    {
        unset($object_id, $meta_value);
        if (\in_array($meta_key, $this->post_meta, \true)) {
            return \false;
        }
        return $check;
    }
    /**
     * Deny a configured admin-ajax action with a 403 response.
     *
     * @return void
     */
    public function deny_ajax()
    {
        \wp_send_json_error(['message' => $this->message], 403);
    }
    /**
     * Strip a WooCommerce settings section's fields on save so nothing persists.
     *
     * @param array $settings The settings fields for the current tab.
     * @return array The original settings, or [] when the matched section is saved.
     */
    public function block_wc_settings($settings)
    {
        // Filter fires on both render and save. Only strip on save (POST) so
        // the settings page still renders for the user.
        $method = isset($_SERVER['REQUEST_METHOD']) ? \strtoupper(\sanitize_text_field(\wp_unslash($_SERVER['REQUEST_METHOD']))) : '';
        if ('POST' !== $method) {
            return $settings;
        }
        global $current_section;
        foreach ($this->wc_settings as $spec) {
            $section = isset($spec['section']) ? $spec['section'] : '';
            if ((string) $current_section !== (string) $section) {
                continue;
            }
            if (!empty($spec['notice']) && \class_exists(WC_Admin_Settings::class)) {
                WC_Admin_Settings::add_error($this->message);
            }
            return [];
        }
        return $settings;
    }
    /**
     * Block configured REST write routes with a 403 error.
     *
     * @param mixed           $result  Existing dispatch result; non-null short-circuits.
     * @param mixed           $server  The REST server instance (unused).
     * @param WP_REST_Request $request The current REST request.
     * @return mixed WP_Error to block a matched write route, otherwise the original result.
     */
    public function block_rest($result, $server, $request)
    {
        unset($server);
        // Leave the result untouched if another handler already set it, or this
        // isn't a REST request.
        if (null !== $result || !$request instanceof WP_REST_Request) {
            return $result;
        }
        $route = \trim((string) $request->get_route(), '/');
        $method = \strtoupper((string) $request->get_method());
        foreach ($this->rest_routes as $spec) {
            $namespace = isset($spec['namespace']) ? \trim((string) $spec['namespace'], '/') : '';
            $route_pat = isset($spec['route']) ? \trim((string) $spec['route'], '/') : '';
            $methods = isset($spec['methods']) && !empty($spec['methods']) ? \array_map('strtoupper', (array) $spec['methods']) : ['POST', 'PUT', 'PATCH', 'DELETE'];
            // Act only on routes under a configured namespace.
            if ('' === $namespace || !$this->path_matches($route, $namespace)) {
                continue;
            }
            // When a sub-route is set, narrow to it within that namespace.
            if ('' !== $route_pat) {
                $remainder = (string) \substr($route, \strlen($namespace) + 1);
                if (!$this->path_matches($remainder, $route_pat)) {
                    continue;
                }
            }
            if (!\in_array($method, $methods, \true)) {
                continue;
            }
            return new WP_Error('barn2_license_expired', $this->message, ['status' => 403]);
        }
        return $result;
    }
    /**
     * Whether a route path equals a prefix or continues past it after a slash,
     * matching on whole path segments only.
     *
     * @param string $path   The route path (leading/trailing slashes trimmed).
     * @param string $prefix The namespace or sub-route prefix to test.
     * @return bool
     */
    private function path_matches($path, $prefix)
    {
        return $path === $prefix || 0 === \strpos($path, $prefix . '/');
    }
}
