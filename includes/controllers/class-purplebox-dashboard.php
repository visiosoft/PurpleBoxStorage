<?php
if (!defined('ABSPATH')) {
    exit;
}

class Purplebox_Dashboard_Controller {

    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        $stats       = Purplebox_DB::get_dashboard_stats();
        $availability = Purplebox_DB::get_availability_by_size();
        $activity    = Purplebox_DB::get_recent_activity(10);
        $upcoming    = Purplebox_DB::get_upcoming_availability();

        include PURPLEBOX_PLUGIN_DIR . 'views/dashboard.php';
    }
}
