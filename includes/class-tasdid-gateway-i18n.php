<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Tasdid_Gateway
 * @subpackage Tasdid_Gateway/includes
 * @author     Mahmood Abbas <contact@mahmoodshakir.com>
 */
defined('ABSPATH') || exit;

class Tasdid_Gateway_i18n
{
    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain('tasdid-gateway', false, dirname(plugin_basename(__FILE__)) . '/../languages/');
    }
}
