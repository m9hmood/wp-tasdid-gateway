<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://mahmoodshakir.com/
 * @since             1.0.0
 * @package           Tasdid_Gateway
 *
 * @wordpress-plugin
 * Plugin Name:       Tasdid Gateway: UnOfficial Gateway Integration
 * Plugin URI:        https://mahmoodshakir.com/
 * Description:       Add Tasdid as payment method for Wordpress easily.
 * Version:           1.3.2
 * Author:            Mahmood A.Shakir
 * Author URI:        https://mahmoodshakir.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       tasdid-gateway
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('TASDID_GATEWAY_VERSION', '1.3.2');


/**
 * Api URL
 */
define('TASDID_API_DOMAIN', 'https://tasdid.net/v1/api');
define('TESTING_TASDID_API_DOMAIN', 'https://api-uat.tasdid.net/v1/api');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-tasdid-gateway-activator.php
 */
function activate_tasdid_gateway()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-tasdid-gateway-activator.php';
    Tasdid_Gateway_Activator::activate();
}


/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-tasdid-gateway-deactivator.php
 */
function deactivate_tasdid_gateway()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-tasdid-gateway-deactivator.php';
    Tasdid_Gateway_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_tasdid_gateway');
register_deactivation_hook(__FILE__, 'deactivate_tasdid_gateway');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-tasdid-gateway.php';


/**
 * Update checker for plugin
 *
 * @since 1.0.0
 */
if (is_admin()) {
    if (!class_exists('Puc_v4_Factory')) {
        require_once plugin_dir_path(__FILE__) . 'includes/libraries/plugin-update-checker-4.9/plugin-update-checker.php';
        $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
            'https://plugins.mahmoodshakir.com/tasdid.json',
            __FILE__, //Full path to the main plugin file or functions.php.
            'tasdid-gateway'
        );
    }
}


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_tasdid_gateway()
{
    if (class_exists('WC_Payment_Gateway')) {
        $plugin = new Tasdid_Gateway();
        $plugin->run();
    } else {
        add_action('admin_notices', function () {
            ?>
            <div class="error notice">
                <p><?php echo __('Sorry, you need to install Woocommerce plugin to use TASDID Gateway') ?></p>
            </div>
            <?php
        });
    }
}

add_filter('plugins_loaded', 'run_tasdid_gateway');
