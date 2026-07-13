<?php

namespace Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\License\Admin;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Admin\Settings_API_Helper;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\License\License;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Registerable;
use Barn2\Plugin\Document_Library\Dependencies\Lib\Service\Core_Service;
use WC_Admin_Settings;
/**
 * Handles the display and saving of the license key on the plugin settings page.
 *
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 * @version   1.2
 */
class License_Key_Setting implements Registerable, License_Setting, Core_Service
{
    const OVERRIDE_HASH = 'caf9da518b5d4b46c2ef1f9d7cba50ad';
    const ACTIVATE_KEY = 'activate_key';
    const DEACTIVATE_KEY = 'deactivate_key';
    const CHECK_KEY = 'check_key';
    public $license;
    public $is_woocommerce;
    public $is_edd;
    private $saving_key = \false;
    private $deferred_message;
    public function __construct(License $license, $is_woocommerce = \false, $is_edd = \false)
    {
        $this->license = $license;
        $this->is_woocommerce = (bool) $is_woocommerce;
        $this->is_edd = (bool) $is_edd;
    }
    public function register()
    {
        \add_action('admin_init', [$this, 'process_license_action'], 5);
        // Safety net: if the masked key is saved back to the license option (e.g. via a plain
        // "Save changes"), restore the real stored key so the dots never overwrite it.
        \add_filter('pre_update_option_' . $this->get_license_setting_name(), [$this, 'preserve_masked_license_key'], 10, 2);
        if ($this->is_edd) {
            // Include EDD settings callbacks.
            include_once __DIR__ . '/edd-settings-functions.php';
            // Handle the license settings message for EDD.
            \add_filter('sanitize_option_edd_settings', [$this, 'handle_edd_license_message'], 20);
        } elseif ($this->is_woocommerce && !\has_action('woocommerce_admin_field_hidden')) {
            // Add hidden field to WooCommerce for license key override setting.
            \add_action('woocommerce_admin_field_hidden', [Settings_API_Helper::class, 'settings_field_hidden']);
        }
    }
    /**
     * Process a license action from the plugin license settings page (i.e. activate, deactivate or check license)
     */
    public function process_license_action()
    {
        // Match the capability each ecosystem gates its own settings save with, so we never block a
        // user who can legitimately reach the page (WC: manage_woocommerce, EDD: manage_shop_settings).
        if ($this->is_woocommerce) {
            $capability = 'manage_woocommerce';
        } elseif ($this->is_edd) {
            $capability = 'manage_shop_settings';
        } else {
            $capability = 'manage_options';
        }
        if (!\current_user_can($capability)) {
            return;
        }
        $has_license_action = $this->is_license_action(self::ACTIVATE_KEY) || $this->is_license_action(self::DEACTIVATE_KEY) || $this->is_license_action(self::CHECK_KEY);
        // CSRF protection: only act on a license action when the request carries our valid nonce.
        if ($has_license_action && !$this->verify_license_nonce()) {
            $this->add_settings_message('', __('Security check failed. Please reload the page and try again.', 'barn2'), \false);
            return;
        }
        if ($this->is_license_action(self::ACTIVATE_KEY)) {
            $license_setting = \filter_input(\INPUT_POST, $this->get_license_setting_name(), \FILTER_DEFAULT, \FILTER_REQUIRE_ARRAY);
            if (isset($license_setting['license'])) {
                $license = \sanitize_text_field($license_setting['license']);
                $license = $this->maybe_unmask($license);
                $activated = $this->activate_license($license);
                $this->add_settings_message(__('License key successfully activated.', 'barn2'), __('There was an error activating your license key.', 'barn2'), $activated);
            }
        } elseif ($this->is_license_action(self::DEACTIVATE_KEY)) {
            $deactivated = $this->license->deactivate();
            $this->add_settings_message(__('License key successfully deactivated.', 'barn2'), __('There was an error deactivating your license key, please try again.', 'barn2'), $deactivated);
        } elseif ($this->is_license_action(self::CHECK_KEY)) {
            $this->license->refresh();
            $this->add_settings_message(__('The license key looks good!', 'barn2'), __('There\'s a problem with your license key.', 'barn2'), $this->license->is_active());
        }
    }
    private function is_license_action($action)
    {
        return isset($_SERVER['REQUEST_METHOD']) && 'POST' === $_SERVER['REQUEST_METHOD'] && $this->get_license_setting_name() === \filter_input(\INPUT_POST, $action, \FILTER_DEFAULT);
    }
    public function get_license_setting_name()
    {
        return $this->license->get_setting_name();
    }
    /**
     * The nonce action for license operations, scoped per plugin (item ID) to avoid collisions
     * when more than one Barn2 license field appears on a page.
     *
     * @return string
     */
    private function get_nonce_action()
    {
        return 'barn2_license_action_' . \absint($this->license->get_item_id());
    }
    /**
     * The nonce field name, scoped per plugin (item ID).
     *
     * @return string
     */
    private function get_nonce_name()
    {
        return 'barn2_license_nonce_' . \absint($this->license->get_item_id());
    }
    /**
     * Build the hidden nonce field markup for the license form (used by the Settings API / EDD
     * render path, where it's echoed from settings_field_text).
     *
     * @return string
     */
    public function get_license_nonce_field()
    {
        return \wp_nonce_field($this->get_nonce_action(), $this->get_nonce_name(), \false, \false);
    }
    /**
     * Verify the license nonce on the current request.
     *
     * @return bool
     */
    private function verify_license_nonce()
    {
        $nonce_name = $this->get_nonce_name();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- this IS the nonce verification.
        $nonce = isset($_POST[$nonce_name]) && \is_string($_POST[$nonce_name]) ? \sanitize_text_field(\wp_unslash($_POST[$nonce_name])) : '';
        return (bool) \wp_verify_nonce($nonce, $this->get_nonce_action());
    }
    private function activate_license($license_key)
    {
        // Check if we're overriding the license activation.
        $override = \filter_input(\INPUT_POST, 'license_override', \FILTER_SANITIZE_SPECIAL_CHARS);
        if ($override && $license_key && self::OVERRIDE_HASH === \md5($override)) {
            $this->license->override($license_key, 'active');
            return \true;
        }
        return $this->license->activate($license_key);
    }
    private function add_settings_message($sucess_message, $error_message, $success = \true)
    {
        if ($this->is_woocommerce) {
            if ($success) {
                WC_Admin_Settings::add_message($sucess_message);
            } else {
                WC_Admin_Settings::add_error($error_message);
            }
        } else {
            $slug = 'license_message';
            $message = $success ? $sucess_message : $error_message;
            $type = $success ? 'updated' : 'error';
            if ($this->is_edd) {
                $this->deferred_message = ['slug' => $slug, 'message' => $message, 'type' => $type];
            } else {
                \add_settings_error($this->get_license_setting_name(), $slug, $message, $type);
            }
        }
    }
    public function get_license_key_setting()
    {
        $masked_key = $this->get_masked_license_key();
        $setting = [
            'title' => __('License key', 'barn2'),
            'type' => 'text',
            'id' => $this->get_license_setting_name() . '[license]',
            'desc' => $this->get_license_description(),
            'class' => 'regular-text barn2-license-key',
            // Show only the last 4 characters of the stored key (the rest masked). The full key is
            // never sent to the browser. Empty when no key is stored, so the field stays editable.
            //
            // The masked value is supplied to every rendering system without changing the field type
            // (so no renderer drops the field, and no global field-type hook is registered):
            //  - 'value'         => native WooCommerce settings use this verbatim (WC_Admin_Settings
            //                       only reads the option when 'value' is not already set).
            //  - 'display_value' => barn2-lib's own Settings API (Settings_API_Helper) and EDD.
            'value' => $masked_key,
            'display_value' => $masked_key,
            // CSRF nonce for the non-WooCommerce render path, echoed by settings_field_text. WooCommerce
            // plugins (whether they render natively or through the Settings API) instead get the nonce
            // from get_license_override_setting()'s hidden field, so it's never rendered twice.
            'license_nonce' => $this->is_woocommerce ? '' : $this->get_license_nonce_field(),
        ];
        if ($this->is_woocommerce) {
            $setting['desc_tip'] = __('The licence key is contained in your order confirmation email.', 'barn2');
        } elseif ($this->is_edd) {
            // EDD uses title case for setting names, so let's keep things consistent.
            $setting['title'] = __('License Key', 'barn2');
            // Set type to 'barn2_license' so the callback to render setting will be 'edd_barn2_license_callback'.
            // (EDD renders each field via its own per-field callback, so there's no cross-plugin hook clash.)
            $setting['type'] = 'barn2_license';
            // EDD uses 'name' instead of 'title'.
            $setting['name'] = $setting['title'];
            unset($setting['title']);
        }
        if ($this->is_license_setting_readonly()) {
            $setting['custom_attributes'] = ['readonly' => 'readonly'];
        }
        return \apply_filters('barn2_plugin_license_key_setting', $setting, $this);
    }
    /**
     * Retrieve the description for the license key input, to display on the settings page.
     *
     * @return string The license key status message
     */
    private function get_license_description()
    {
        $buttons = ['check' => $this->license_action_button(self::CHECK_KEY, __('Check', 'barn2')), 'activate' => $this->license_action_button(self::ACTIVATE_KEY, __('Activate', 'barn2')), 'deactivate' => $this->license_action_button(self::DEACTIVATE_KEY, __('Deactivate', 'barn2'))];
        $message = $this->license->get_status_help_text();
        if ($this->license->is_active()) {
            $message = \sprintf('<span class="barn2-license-key-status license-active" style="color:green;">✓&nbsp;%s</span>', $message);
        } elseif ($this->license->get_license_key()) {
            // If we have a license key and it's not active, mark it red for user to take action.
            if ($this->license->is_inactive() && $this->is_license_action('deactivate_key')) {
                // ...except if the user has just deactivated, in which case just show a plain confirmation message.
                $message = __('License key deactivated.', 'barn2');
            } else {
                $message = \sprintf('<span class="barn2-license-key-status license-inactive" style="color:red;">%s</span>', $message);
            }
        }
        if ($this->is_license_setting_readonly()) {
            unset($buttons['activate']);
        } else {
            unset($buttons['check'], $buttons['deactivate']);
        }
        return '<span class="submit">' . \implode('', $buttons) . '</span> ' . $message;
    }
    private function license_action_button($input_name, $button_text)
    {
        return \sprintf('<button type="submit" class="button barn2-license-action" name="%1$s" value="%2$s" style="margin-right:4px;">%3$s</button>', \esc_attr($input_name), \esc_attr($this->get_license_setting_name()), \esc_html($button_text));
    }
    private function is_license_setting_readonly()
    {
        return $this->license->is_active();
    }
    public function get_license_override_setting()
    {
        $override_code = \filter_input(\INPUT_GET, 'license_override', \FILTER_SANITIZE_SPECIAL_CHARS);
        if ($this->is_woocommerce) {
            // WooCommerce renders the licence text field itself, so it can't carry the nonce inline.
            // We attach it here instead, through the same hidden-field seam this method already uses
            // (woocommerce_admin_field_hidden -> settings_field_hidden). The override code (rare,
            // support-only) rides along as an extra hidden input when present.
            $field = ['type' => 'hidden', 'id' => $this->get_nonce_name(), 'default' => \wp_create_nonce($this->get_nonce_action())];
            if ($override_code) {
                $field['extra'] = ['license_override' => \sanitize_text_field($override_code)];
            }
            return $field;
        }
        // Non-WooCommerce: the nonce is echoed by settings_field_text, so this stays the override field.
        return $override_code ? ['type' => 'hidden', 'id' => 'license_override', 'default' => \sanitize_text_field($override_code)] : [];
    }
    public function save_posted_license_key()
    {
        if ($this->saving_key) {
            return;
        }
        $license_setting = \filter_input(\INPUT_POST, $this->get_license_setting_name(), \FILTER_DEFAULT, \FILTER_REQUIRE_ARRAY);
        if (!isset($license_setting['license'])) {
            return;
        }
        // CSRF protection: a posted license key must carry our valid nonce. Legitimate saves render
        // the license field, which always outputs the nonce; a forged save won't have it.
        if (!$this->verify_license_nonce()) {
            return;
        }
        $this->save_license_key($license_setting['license']);
    }
    /**
     * Save the specified license key.
     *
     * If there is a valid key currently active, the current key will be deactivated first
     * before activating the new one.
     *
     * @param string $license_key The license key to save.
     * @return string The license key.
     */
    public function save_license_key($license_key)
    {
        if ($this->saving_key) {
            return $license_key;
        }
        // phpcs:ignore WordPress.Security.NonceVerification
        if (\array_intersect([self::DEACTIVATE_KEY, self::ACTIVATE_KEY, self::CHECK_KEY], \array_keys($_POST))) {
            return $license_key;
        }
        $this->saving_key = \true;
        $license_key = \sanitize_text_field($license_key);
        $license_key = $this->maybe_unmask($license_key);
        // Deactivate old license key first if it was valid.
        if ($this->license->is_active() && $license_key !== $this->license->get_license_key()) {
            $this->license->deactivate();
        }
        // If new license key is different to current key, or current key isn't active, attempt to activate.
        if ($license_key !== $this->license->get_license_key() || !$this->license->is_active()) {
            $this->activate_license($license_key);
        }
        $this->saving_key = \false;
        return $license_key;
    }
    /**
     * Mask a license key for display, revealing only the last 4 characters.
     *
     * The full key is never sent to the browser. Keys of 4 characters or fewer are masked entirely.
     *
     * @param string $key The license key.
     * @return string The masked key, e.g. '••••••••••••AB12'. Empty string if no key.
     */
    private function mask_license_key($key)
    {
        $key = (string) $key;
        // Use multibyte functions when available, falling back to byte functions on hosts without
        // the mbstring extension. The two halves stay consistent, so the mask still round-trips.
        $has_mb = \function_exists('mb_strlen') && \function_exists('mb_substr');
        $length = $has_mb ? \mb_strlen($key) : \strlen($key);
        if (0 === $length) {
            return '';
        }
        if ($length <= 4) {
            return \str_repeat('•', $length);
        }
        $tail = $has_mb ? \mb_substr($key, -4) : \substr($key, -4);
        return \str_repeat('•', $length - 4) . $tail;
    }
    /**
     * Get the masked version of the currently stored license key.
     *
     * @return string The masked key, or empty string if no key is stored.
     */
    public function get_masked_license_key()
    {
        return $this->mask_license_key($this->license->get_license_key());
    }
    /**
     * Determine whether a submitted value is the mask of the currently stored key (i.e. unchanged).
     *
     * Only an exact match to the generated mask counts; arbitrary bullet-containing input does not.
     *
     * @param string $value The submitted value.
     * @return bool
     */
    private function is_masked_value($value)
    {
        $masked = $this->get_masked_license_key();
        return '' !== $masked && \trim((string) $value) === $masked;
    }
    /**
     * Swap a masked (unchanged) value back to the real stored key, so the dots are never
     * persisted or sent to the license server. A genuinely different value is returned untouched.
     *
     * @param string $value The submitted value.
     * @return string
     */
    private function maybe_unmask($value)
    {
        return $this->is_masked_value($value) ? $this->license->get_license_key() : $value;
    }
    /**
     * Storage safety net for writes to the license option. Fires on every update.
     *
     *  - CSRF: if a settings form is posting the license field but the request doesn't carry our
     *    valid nonce, this is a forged write (e.g. a plain WooCommerce "Save changes", which writes
     *    the option directly and bypasses process_license_action() / save_posted_license_key()).
     *    Keep the stored license data untouched. Programmatic writes (CLI, cron refresh, activation)
     *    don't post the license field, so they're unaffected.
     *  - Masking: a resubmitted masked value means "no change", so keep the existing data wholesale.
     *
     * @param mixed $value     The new option value.
     * @param mixed $old_value The existing option value.
     * @return mixed
     */
    public function preserve_masked_license_key($value, $old_value)
    {
        if (!\is_array($value) || !\is_array($old_value) || empty($old_value['license'])) {
            return $value;
        }
        // Block forged form writes: the license field is being posted without a valid nonce.
        if ($this->is_posting_license_field() && !$this->verify_license_nonce()) {
            return $old_value;
        }
        // Treat a resubmitted masked value as "no change".
        if (isset($value['license']) && \trim((string) $value['license']) === $this->mask_license_key($old_value['license'])) {
            return $old_value;
        }
        return $value;
    }
    /**
     * Whether the current request is submitting this plugin's license field via a settings form.
     *
     * @return bool
     */
    private function is_posting_license_field()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- presence check only; the nonce is verified separately.
        $posted = isset($_POST[$this->get_license_setting_name()]) ? $_POST[$this->get_license_setting_name()] : null;
        return \is_array($posted) && \array_key_exists('license', $posted);
    }
    public function handle_edd_license_message($options)
    {
        global $wp_settings_errors;
        if (!empty($this->deferred_message)) {
            // Clear any other messages (e.g. 'Settings Updated') so we only show our license message.
            $wp_settings_errors = [];
            // We need to use 'edd-notices' setting to get message to show in EDD settings pages.
            \add_settings_error('edd-notices', $this->deferred_message['slug'], $this->deferred_message['message'], $this->deferred_message['type']);
            $this->deferred_message = [];
        }
        return $options;
    }
}
