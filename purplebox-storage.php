<?php
/**
 * Plugin Name: PurpleBox Storage
 * Plugin URI: https://purplebox.ae
 * Description: Self-storage unit and tenant management for WordPress.
 * Version: 2.3.4
 * Author: PurpleBox
 * Text Domain: purplebox-storage
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PURPLEBOX_VERSION', '2.3.4');
define('PURPLEBOX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PURPLEBOX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PURPLEBOX_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Remove any old broken plugin entries left over from previous installs.
add_action('init', function () {
    $old_entries = [
        'purplebox-plugin/purplebox-storage.php',
    ];
    $active = get_option('active_plugins', []);
    $changed = false;
    foreach ($old_entries as $old) {
        if (($key = array_search($old, $active, true)) !== false) {
            unset($active[$key]);
            $changed = true;
        }
    }
    if ($changed) {
        update_option('active_plugins', array_values($active));
    }
});

require_once PURPLEBOX_PLUGIN_DIR . 'includes/class-purplebox-activator.php';
require_once PURPLEBOX_PLUGIN_DIR . 'includes/class-purplebox-deactivator.php';
require_once PURPLEBOX_PLUGIN_DIR . 'includes/class-purplebox-db.php';
require_once PURPLEBOX_PLUGIN_DIR . 'includes/class-purplebox-admin.php';

register_activation_hook(__FILE__, ['Purplebox_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['Purplebox_Deactivator', 'deactivate']);

add_action('plugins_loaded', function () {
    global $wpdb;
    // Full upgrade on version change
    if (get_option('purplebox_db_version') !== PURPLEBOX_VERSION) {
        Purplebox_Activator::activate();
    } else {
        // Always ensure critical columns exist (guards against failed migrations)
        Purplebox_Activator::ensure_columns(
            $wpdb->prefix . 'purplebox_units',
            $wpdb->prefix . 'purplebox_tenants'
        );
    }
    new Purplebox_Admin();
});
