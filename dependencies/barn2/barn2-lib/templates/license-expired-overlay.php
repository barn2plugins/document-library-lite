<?php

namespace Barn2\Plugin\Document_Library\Dependencies;

/**
 * License expired overlay template.
 *
 * Rendered by {@see \Barn2\Lib\Plugin\License\Admin\License_Expired_Overlay::render()}.
 * Override via the `barn2_license_expired_overlay_template` filter.
 *
 * @var \Barn2\Lib\Plugin\Licensed_Plugin $plugin          The licensed plugin.
 * @var \Barn2\Lib\Plugin\License\License $license         The plugin license.
 * @var string                            $setting_name    The license setting name (form field base).
 * @var string                            $target_selector CSS selector to mount inside, or '' for fullscreen.
 *
 * @package Barn2\barn2-lib
 */
\defined('ABSPATH') || exit;
$current_key = $license->get_license_key();
$renewal_url = $license->get_renewal_url();
$override_code = \filter_input(\INPUT_GET, 'license_override', \FILTER_SANITIZE_SPECIAL_CHARS);
$what_url = 'https://barn2.com/kb/license-key-expiry/';
$target_attr = !empty($target_selector) ? \sprintf(' data-target="%s"', \esc_attr($target_selector)) : '';
?>
<div class="barn2-license-overlay"<?php 
echo $target_attr;
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>>
	<div class="barn2-license-overlay__backdrop"></div>
	<div class="barn2-license-overlay__card" role="dialog" aria-modal="true" aria-labelledby="barn2-license-overlay__title">
		<span class="barn2-license-overlay__icon" aria-hidden="true">
			<svg width="20" height="23" viewBox="0 0 20 23" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M18.0738 9.99459L16.3071 9.9664L16.3087 5.6635C16.3094 3.98685 15.4912 2.49743 14.0726 1.40151C12.9156 0.507697 11.5129 0.0350949 9.95789 0.00178794C7.9746 -0.0407974 6.22136 0.679109 4.91841 1.97225C3.85611 3.02666 3.32347 4.33384 3.32034 5.76128L3.31137 9.96533L1.58095 9.99209C0.776822 10.0045 0.014133 10.6868 0.013046 11.4311L1.51096e-06 21.2382C-0.0012214 22.1565 0.739999 22.8677 1.81303 22.8667L18.0792 22.8507C18.7915 22.85 19.5965 22.1915 19.5966 21.601L19.6 11.251C19.6003 10.5896 18.7243 10.0053 18.0739 9.99483L18.0738 9.99459ZM6.57546 9.97413L6.58442 5.66088C6.58769 4.09188 8.0617 2.86226 9.78899 2.84799C11.5489 2.83347 13.0547 4.07273 13.0567 5.66314L13.0625 9.97496L6.57532 9.97401L6.57546 9.97413Z" fill="white"/>
			</svg>
		</span>
		<h2 id="barn2-license-overlay__title" class="barn2-license-overlay__title">
			<?php 
esc_html_e('Your license needs renewing', 'barn2');
?>
		</h2>
		<p class="barn2-license-overlay__desc">
			<?php 
esc_html_e('The plugin is still working correctly for your visitors. To make any changes in the admin, please renew your license key.', 'barn2');
?>
		</p>
		<?php 
if ($renewal_url) {
    ?>
			<a class="barn2-license-overlay__cta button button-primary" href="<?php 
    echo \esc_url($renewal_url);
    ?>" target="_blank" rel="noopener noreferrer">
				<?php 
    esc_html_e('Renew today and save 20%', 'barn2');
    ?>
			</a>
		<?php 
}
?>

		<a class="barn2-license-overlay__what" href="<?php 
echo \esc_url($what_url);
?>" target="_blank" rel="noopener noreferrer">
			<?php 
esc_html_e('What this means', 'barn2');
?>
		</a>


		<hr class="barn2-license-overlay__divider" />

		<p class="barn2-license-overlay__updateLabel">
			<?php 
esc_html_e('Already renewed your license? Please update it below.', 'barn2');
?>
		</p>
		<form method="post" class="barn2-license-overlay__form" action="">
			<div class="barn2-license-overlay__inputGroup">
				<label for="barn2-license-overlay__key" class="screen-reader-text">
					<?php 
esc_html_e('License key', 'barn2');
?>
				</label>

				<input
					type="text"
					id="barn2-license-overlay__key"
					class="barn2-license-overlay__input"
					name="<?php 
echo \esc_attr($setting_name);
?>[license]"
					value="<?php 
echo \esc_attr($current_key);
?>"
					placeholder="<?php 
esc_attr_e('Enter your license key', 'barn2');
?>"
					autocomplete="off"
					spellcheck="false" />

				<?php 
if ($override_code) {
    ?>
					<input type="hidden" name="license_override" value="<?php 
    echo \esc_attr($override_code);
    ?>" />
				<?php 
}
?>

				<button
					type="submit"
					name="activate_key"
					value="<?php 
echo \esc_attr($setting_name);
?>"
					class="barn2-license-overlay__check button">
					<?php 
esc_html_e('Check', 'barn2');
?>
				</button>
			</div>
		</form>
	</div>
</div>
<?php 
