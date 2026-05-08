<?php
/**
 * Plugin Name: PurpleBox Storage
 * Plugin URI: https://purplebox.ae
 * Description: Self-storage unit and tenant management for WordPress.
 * Version: 2.4.3
 * Author: PurpleBox
 * Text Domain: purplebox-storage
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PURPLEBOX_VERSION', '2.4.3');
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

/**
 * Helper: returns true if the user is a restricted PurpleBox role (Admin or Manager)
 * but NOT a full WP administrator.
 */
function purplebox_is_pb_manager($user = null) {
    if ($user === null) {
        $roles = (array) wp_get_current_user()->roles;
    } elseif ($user instanceof WP_User) {
        $roles = (array) $user->roles;
    } else {
        return false;
    }
    if (in_array('administrator', $roles, true)) {
        return false;
    }
    return in_array('purplebox_admin', $roles, true) || in_array('purplebox_manager', $roles, true);
}

/**
 * WooCommerce blocks wp-admin for users without edit_posts/manage_woocommerce.
 * Tell WooCommerce NOT to prevent PurpleBox Manager from accessing wp-admin.
 */
add_filter('woocommerce_prevent_admin_access', function ($prevent) {
    if (purplebox_is_pb_manager()) {
        return false;
    }
    return $prevent;
});

/**
 * wp-login.php redirect (standard WP login).
 */
add_filter('login_redirect', function ($redirect_to, $requested_redirect_to, $user) {
    if (purplebox_is_pb_manager($user)) {
        return admin_url('admin.php?page=purplebox-dashboard');
    }
    return $redirect_to;
}, 9999, 3);

/**
 * WooCommerce frontend login form redirect (/my-account login).
 */
add_filter('woocommerce_login_redirect', function ($redirect, $user) {
    if (purplebox_is_pb_manager($user)) {
        return admin_url('admin.php?page=purplebox-dashboard');
    }
    return $redirect;
}, 9999, 2);

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
