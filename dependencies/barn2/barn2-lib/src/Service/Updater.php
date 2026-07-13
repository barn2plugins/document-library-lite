<?php

namespace Barn2\Plugin\Document_Library\Dependencies\Lib\Service;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Plugin;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Plugin_Activation_Listener;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Admin\Notices;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Conditional;
/**
 * Handles plugin updates that are defined on the $updates property.
 *
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 * @version   1.0
 */
abstract class Updater implements Standard_Service, Registerable, Plugin_Activation_Listener
{
    /**
     * Callbacks functions that are called on a plugin update.
     *
     * Please note that these functions are invoked when a plugin is updated from a previous version,
     * but NOT when the plugin is newly installed.
     *
     * The array keys should contain the version number, and it MUST be sorted from low to high.
     *
     * Example:
     *
     * '1.11.0' => [
     *      'update_1_11_0_do_something',
     *      'update_1_11_0_do_something_else',
     *  ],
     *  '1.23.0' => [
     *      'update_1_23_0_do_something',
     *  ],
     *
     * @var array
     */
    public static $updates = [];
    /**
     * Plugin instance.
     *
     * @var Plugin
     */
    protected $plugin;
    /**
     * The class options.
     *
     * See the get_default_options method to verify the array structure.
     *
     * @var array
     */
    public $options = [];
    /**
     * The raw options supplied by the consumer.
     *
     * Kept separately from $options (which holds the defaults merged with the
     * consumer values) so we can tell which notice strings were explicitly
     * overridden and therefore should not be replaced by the translated defaults.
     *
     * @var array
     */
    private $custom_options = [];
    /**
     * Constructor.
     *
     * @param Plugin $plugin
     * @param array  $options {
     *     Optional. An array of additional options to change the default values.
     *
     *     @type string $version_option_name       Option name to store the version value on the options DB table. Default '<plugin_slug>_version'.
     *     @type array  $needs_update_db_notice    "Database update required" notice config. Accepts 'title', 'message', 'buttons', 'type', 'capability', 'dismissible' and 'additional_classes' keys. 'title' and 'message' may also be a callable returning the string, invoked lazily so the consumer can defer its own __() call.
     *     @type array  $update_db_complete_notice "Database update complete" notice config. Accepts the same keys as $needs_update_db_notice.
     * }
     */
    public function __construct(Plugin $plugin, $options = null)
    {
        $this->plugin = $plugin;
        $this->custom_options = \is_array($options) ? $options : [];
        $this->set_options($options);
    }
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        // Check the plugin's version and show the update admin notice message.
        \add_action('admin_init', [$this, 'check_version']);
        // Check for update completion
        \add_action('admin_init', [$this, 'check_update_complete']);
        \add_action('admin_post_' . $this->plugin->get_slug() . '_update_db', [$this, 'handle_update_request']);
        \add_action('wp_ajax_barn2_updater_dismiss_notice', [$this, 'ajax_maybe_dismiss_notice'], 1);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_updater_scripts']);
    }
    /**
     * Enqueues scripts for the updater notice.
     */
    public function enqueue_updater_scripts()
    {
        if ($this->is_update_complete()) {
            \wp_enqueue_script('updater-notice', \plugins_url('dependencies/barn2/barn2-lib/build/js/admin/updater-notice.js', $this->plugin->get_file()), ['jquery'], $this->plugin->get_version(), \true);
        }
    }
    /**
     * Gets the default options.
     *
     * @return array
     */
    public function get_default_options()
    {
        return ['version_option_name' => $this->plugin->get_slug() . '_version', 'needs_update_db_notice' => ['title' => '%1$s database update required', 'message' => '<p>%1$s has been updated! To keep things running smoothly, we have to update your database to the newest version. The database update process runs in the background and may take a little while, so please be patient.</p>', 'type' => 'warning', 'capability' => 'install_plugins', 'dismissible' => \false, 'additional_classes' => [], 'buttons' => ['update-db' => ['value' => 'Update Database', 'href' => '#', 'class' => 'button-primary'], 'learn-more' => ['value' => 'Learn more about updates', 'href' => 'https://barn2.com/kb/learn-more-about-updates/', 'target' => '_blank', 'class' => 'button-secondary', 'style' => 'margin-left: 8px;']]], 'update_db_complete_notice' => ['title' => '%1$s database update done', 'message' => '<p>%1$s database update complete. Thank you for updating to the latest version!</p>', 'type' => 'success', 'capability' => 'install_plugins', 'dismissible' => \true, 'additional_classes' => ['barn2-update-complete-notice'], 'buttons' => []]];
    }
    /**
     * Sets the final options.
     *
     * @param array $options An array of options to override the default values.
     */
    public function set_options($options)
    {
        $this->options = \array_replace_recursive($this->get_default_options(), $options ?? []);
    }
    /**
     * Gets the notice options with their strings resolved (translated + plugin name substituted).
     *
     * The default title/message strings are translated here (lazily) so they are only
     * loaded once the text domain is available. When a consumer supplied its own string
     * via the constructor $options argument, that string is used as-is — it is the
     * consumer's job to wrap it in __() with their own text domain — and the plugin name
     * is still substituted into it, so overrides may keep using the %1$s/%s placeholder.
     *
     * A consumer override may also be a callable returning the string. It is invoked here
     * (i.e. lazily, on admin_init) so the consumer can defer its own __() call past the
     * point WordPress considers safe for loading text domains.
     *
     * @return array
     */
    private function get_translated_options()
    {
        $translated_options = $this->options;
        $default_strings = ['needs_update_db_notice' => [
            /* translators: %1$s: plugin name */
            'title' => __('%1$s database update required'),
            /* translators: %1$s: plugin name */
            'message' => __('<p>%1$s has been updated! To keep things running smoothly, we have to update your database to the newest version. The database update process runs in the background and may take a little while, so please be patient.</p>'),
        ], 'update_db_complete_notice' => [
            /* translators: %1$s: plugin name */
            'title' => __('%1$s database update done'),
            /* translators: %1$s: plugin name */
            'message' => __('<p>%1$s database update complete. Thank you for updating to the latest version!</p>'),
        ]];
        foreach ($default_strings as $notice_key => $strings) {
            foreach ($strings as $string_key => $default_string) {
                $is_overridden = isset($this->custom_options[$notice_key][$string_key]);
                $value = $is_overridden ? $translated_options[$notice_key][$string_key] : $default_string;
                if (!\is_string($value) && \is_callable($value)) {
                    $value = \call_user_func($value);
                }
                $translated_options[$notice_key][$string_key] = \sprintf((string) $value, $this->plugin->get_name());
            }
        }
        return $translated_options;
    }
    /**
     * Builds the options array passed to Notices::add() for a single notice.
     *
     * @param array $notice The resolved notice config (a sub-array of get_translated_options()).
     *
     * @return array
     */
    private function build_notice_args(array $notice)
    {
        $args = [];
        foreach (['type', 'capability', 'dismissible', 'additional_classes', 'buttons'] as $key) {
            if (\array_key_exists($key, $notice)) {
                $args[$key] = $notice[$key];
            }
        }
        return $args;
    }
    /**
     * Checks the plugin's version and shows the update admin notice message if an update is required.
     */
    public function check_version()
    {
        if ($this->needs_update() && !\defined('IFRAME_REQUEST')) {
            $this->show_notice();
        }
    }
    /**
     * Show the update admin notice message.
     */
    public function show_notice()
    {
        if ($this->is_upgrade_locked()) {
            return;
        }
        if (!$this->needs_update()) {
            return;
        }
        // Removes all old dismissed admin notice messages status.
        \delete_option('barn2_notice_dismissed_' . $this->plugin->get_slug() . '_update_db_complete_notice');
        $nonce_url = \wp_nonce_url(\add_query_arg(['action' => $this->plugin->get_slug() . '_update_db', 'return_url' => \admin_url()], \admin_url('admin-post.php')), $this->plugin->get_slug() . '_update_db');
        // Get the resolved notice config.
        $notice = $this->get_translated_options()['needs_update_db_notice'];
        // Point the primary action button at the nonce URL (if the consumer kept it).
        if (isset($notice['buttons']['update-db']) && \is_array($notice['buttons']['update-db'])) {
            $notice['buttons']['update-db']['href'] = $nonce_url;
        }
        $admin_notice = new Notices();
        // Show update required notice
        $admin_notice->add($this->plugin->get_slug() . '_needs_update_db_notice', $notice['title'], $notice['message'], $this->build_notice_args($notice));
        $admin_notice->boot();
    }
    /**
     * Handles the AJAX request to dismiss the update complete notice.
     */
    public function ajax_maybe_dismiss_notice()
    {
        $notice_id = isset($_POST['id']) ? \sanitize_text_field(\wp_unslash($_POST['id'])) : '';
        // This handler is only interested in the '_update_db_complete_notice' for the current plugin.
        // If it's not that notice, it does nothing and returns, allowing other handlers to process it.
        if ($notice_id !== $this->plugin->get_slug() . '_update_db_complete_notice') {
            return;
        }
        if (!isset($_POST['nonce']) || !\wp_verify_nonce(\sanitize_text_field($_POST['nonce']), 'barn2_dismiss_admin_notice_' . $notice_id)) {
            return;
        }
        if (!\current_user_can('manage_options')) {
            return;
        }
        \update_option('barn2_notice_dismissed_' . $this->plugin->get_slug() . '_update_db_complete_notice', \true);
    }
    /**
     *  Checks if an update was just completed and shows the success notice.
     */
    public function check_update_complete()
    {
        if (!$this->is_update_complete()) {
            return;
        }
        $notice = $this->get_translated_options()['update_db_complete_notice'];
        $admin_notice = new Notices();
        $admin_notice->add($this->plugin->get_slug() . '_update_db_complete_notice', $notice['title'], $notice['message'], $this->build_notice_args($notice));
        $admin_notice->boot();
    }
    /**
     * Runs all the required update callback functions.
     */
    private function update()
    {
        $db_version = $this->get_current_database_version();
        $code_version = $this->get_current_code_version();
        // Runs the required updates.
        foreach (self::get_update_callbacks() as $version => $update_callbacks) {
            if (\version_compare($db_version, $version, '<')) {
                self::update_version($version);
                if ($this->update_db_version($version)) {
                    $db_version = $version;
                }
            }
        }
        if (\version_compare($code_version, $db_version, '>')) {
            $this->update_db_version($code_version);
        }
        /**
         * Fires after the plugin is updated.
         *
         * @param string $db_version The version of the plugin as stored in the database.
         * @param string $code_version The version of the plugin as stored in the code.
         */
        \do_action($this->plugin->get_slug() . '_updated', $db_version, $code_version, $this->plugin);
    }
    /**
     * Updates a specific version.
     *
     * @param string $version The version to update to.
     */
    public static function update_version($version = '')
    {
        if (isset(static::$updates[$version])) {
            foreach (static::$updates[$version] as $function) {
                if (\method_exists(\get_called_class(), $function)) {
                    static::$function();
                }
            }
        }
    }
    /**
     * Updates the version on the DB.
     *
     * @param string|null $version Version number or null to use the current plugin version.
     *
     * @return bool
     */
    public function update_db_version($version = null) : bool
    {
        return \update_option($this->options['version_option_name'], \is_null($version) ? $this->get_current_code_version() : $version);
    }
    /**
     * Get the list of update callbacks from the plugin Update class.
     *
     * @return array
     */
    public static function get_update_callbacks() : array
    {
        return static::$updates;
    }
    /**
     * Gets the latest update version from the plugin Update class.
     *
     * @return string
     */
    public function get_latest_update_version()
    {
        return \array_key_last(self::get_update_callbacks());
    }
    /**
     * Gets the current plugin version as stored in the database.
     *
     * @return string
     */
    public function get_current_database_version()
    {
        return \get_option($this->options['version_option_name']);
    }
    /**
     * Gets the current plugin version as stored in the code.
     *
     * @return string
     */
    public function get_current_code_version() : string
    {
        return $this->plugin->get_version();
    }
    /**
     * Condition to verify if it's a new plugin installation and not an update.
     *
     * @return bool
     */
    public function is_new_install() : bool
    {
        return $this->get_current_database_version() === \false ? \true : \false;
    }
    /**
     * Verifies if it needs to update.
     *
     * @return bool
     */
    public function needs_update()
    {
        $db_version = $this->get_current_database_version();
        $latest_update_version = $this->get_latest_update_version();
        return !$this->is_new_install() && $latest_update_version !== null && \version_compare($db_version, $latest_update_version, '<');
    }
    /**
     * Handles the update request on the backend.
     */
    public function handle_update_request()
    {
        if (!\current_user_can('manage_options')) {
            \wp_die(esc_html__('You do not have sufficient permissions to perform this action.'));
        }
        // If already locked, stop.
        if ($this->is_upgrade_locked()) {
            \wp_die(esc_html__('An upgrade is already in progress. Please wait until it completes.'));
        }
        \check_admin_referer($this->plugin->get_slug() . '_update_db');
        // Set the lock so no one else can start an update in parallel.
        $this->set_upgrade_lock();
        $this->update();
        // Clear the lock once done.
        $this->clear_upgrade_lock();
        $return_url = !empty($_GET['return_url']) ? \esc_url_raw(\urldecode($_GET['return_url'])) : \admin_url();
        // There might be several Barn2 plugins installed, so the param should be the plugin's slug.
        $return_url = \add_query_arg('barn2_db_updated', $this->plugin->get_slug(), $return_url);
        \wp_safe_redirect($return_url);
        exit;
    }
    /**
     * Verifies if the update is complete and the completion notice should be shown.
     *
     * An update is considered "complete" for notice purposes if:
     * 1. The redirect URL parameter 'barn2_db_updated' is present and matches the plugin slug.
     * 2. The plugin database does not require further updates.
     * 3. The 'update_db_complete_notice' for this plugin has not been dismissed.
     *
     * @return bool
     */
    public function is_update_complete() : bool
    {
        // Check if we are on the specific redirect URL after an update attempt.
        $is_correct_redirect_page = isset($_GET['barn2_db_updated']) && \sanitize_key($_GET['barn2_db_updated']) === $this->plugin->get_slug();
        if (!$is_correct_redirect_page) {
            return \false;
        }
        // Check if the "update complete" notice has already been dismissed for this plugin.
        $notice_dismissed_option_name = 'barn2_notice_dismissed_' . $this->plugin->get_slug() . '_update_db_complete_notice';
        $is_notice_dismissed = (bool) \get_option($notice_dismissed_option_name, \false);
        // The notice should only show if the update is truly done and the notice hasn't been dismissed.
        return !$this->needs_update() && !$is_notice_dismissed;
    }
    /**
     * This runs on 2 occasions, when the plugin is installed for the first time
     * And when the plugin is updated for the first time after introducing the Updater class.
     * This will run all the necessary updates so far, regardless of user confirmation.
     *
     * @param bool $network_wide Whether the plugin is being activated network-wide.
     */
    public function on_activate($network_wide)
    {
        if ($this->is_new_install()) {
            $this->update_db_version('1.0.0');
            // Run all the updates so far.
            $this->update();
            // Update the DB version to the current code version.
            $this->update_db_version();
        }
    }
    /**
     * Runs when the plugin is deactivated.
     *
     * @param bool $network_wide Whether the plugin is being deactivated network-wide.
     */
    public function on_deactivate($network_wide)
    {
    }
    /**
     * Sets an upgrade lock to prevent parallel updates.
     * The lock includes a timestamp for automatic expiration.
     */
    public function set_upgrade_lock() : bool
    {
        // Store the current timestamp as the lock value
        return \update_option($this->plugin->get_slug() . '_upgrade_lock', \time());
    }
    /**
     * Checks if an upgrade lock is set and still valid.
     * Locks automatically expire after 10 minutes to prevent permanent locks.
     */
    public function is_upgrade_locked() : bool
    {
        $lock_time = \get_option($this->plugin->get_slug() . '_upgrade_lock', \false);
        // If no lock exists
        if (!$lock_time) {
            return \false;
        }
        // Check if the lock has expired (10 minute timeout)
        $lock_timeout = 10 * \MINUTE_IN_SECONDS;
        if (\time() - $lock_time > $lock_timeout) {
            // Lock has expired, clear it
            $this->clear_upgrade_lock();
            return \false;
        }
        return \true;
    }
    /**
     * Removes the upgrade lock.
     */
    public function clear_upgrade_lock()
    {
        \delete_option($this->plugin->get_slug() . '_upgrade_lock');
    }
}
