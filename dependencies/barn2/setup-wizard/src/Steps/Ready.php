<?php

/**
 * @package   Barn2\setup-wizard
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
namespace Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Steps;

use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Step;
/**
 * Handles the last step of the wizard.
 */
class Ready extends Step
{
    /**
     * Initialize the step.
     */
    public function init()
    {
        $this->set_id('ready');
        $this->set_name(esc_html__('Ready', 'barn2-setup-wizard'));
        $this->set_title(esc_html__('Setup Complete', 'barn2-setup-wizard'));
    }
    /**
     * {@inheritdoc}
     */
    public function setup_fields()
    {
        return [];
    }
    /**
     * {@inheritdoc}
     */
    public function submit($values)
    {
    }
}
