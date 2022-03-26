<?php

/**
 * @package   Barn2\setup-wizard
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
namespace Barn2\Plugin\Document_Library\Dependencies\Barn2\Setup_Wizard\Steps;

use Barn2\Plugin\Document_Library\Dependencies\Barn2\Setup_Wizard\Step;
/**
 * Handles the welcome step of the wizard for free plugins.
 */
class Welcome_Free extends Step
{
    /**
     * Initialize the step.
     */
    public function __construct()
    {
        $this->set_id('welcome_free');
        $this->set_name(esc_html__('Welcome', 'document-library-lite'));
    }
    /**
     * {@inheritdoc}
     */
    public function setup_fields()
    {
        $fields = [];
        return $fields;
    }
    /**
     * {@inheritdoc}
     */
    public function submit()
    {
        check_ajax_referer('barn2_setup_wizard_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            $this->send_error(__('You are not allowed to validate your license.', 'document-library-lite'));
        }
    }
}
