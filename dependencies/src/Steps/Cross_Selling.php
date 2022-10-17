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
 * Handles the cross selling step of the wizard.
 */
class Cross_Selling extends Step
{
    /**
     * Initialize the step.
     */
    public function __construct()
    {
        $this->set_id('more');
        $this->set_name(esc_html__('More', 'document-library-lite'));
        $this->set_title(esc_html__('Extra features', 'document-library-lite'));
        $this->set_description(esc_html__('Enhance your site with these fantastic plugins from Barn2.', 'document-library-lite'));
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
