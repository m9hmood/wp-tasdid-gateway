<?php


/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Tasdid_Gateway
 * @subpackage Tasdid_Gateway/includes
 * @author     Mahmood Abbas <contact@mahmoodshakir.com>
 */
defined('ABSPATH') || exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class Tasdid_Gateway_Activator
{

    /**
     * Initialize the plugin table on plugin activation 
     *
     * @since    1.0.0
     */
    public static function activate()
    {

    }
}
