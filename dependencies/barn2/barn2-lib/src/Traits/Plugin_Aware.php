<?php

namespace Barn2\Plugin\Document_Library\Dependencies\Lib\Traits;

use Barn2\Plugin\Document_Library\Dependencies\Lib\Plugin\Plugin;
/**
 * Trait to provide a Plugin instance to classes.
 * 
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
trait Plugin_Aware
{
    /**
     * Instance of the Plugin class.
     * 
     * @var Plugin
     */
    protected $plugin;
    /**
     * Set the Plugin instance.
     * 
     * @param Plugin $plugin
     */
    public function set_plugin(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }
    /**
     * Get the Plugin instance.
     * 
     * @return Plugin
     */
    public function get_plugin()
    {
        return $this->plugin;
    }
}
