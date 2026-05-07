<?php
if (!defined('ABSPATH')) {
    exit;
}

class Purplebox_Deactivator {

    public static function deactivate() {
        delete_transient('purplebox_dashboard_stats');

        // Remove PurpleBox roles
        remove_role('purplebox_admin');
        remove_role('purplebox_manager');

        // Remove custom capability from administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap('manage_purplebox');
        }
    }
}
