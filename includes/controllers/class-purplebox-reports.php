<?php
if (!defined('ABSPATH')) exit;

class Purplebox_Reports_Controller {

    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        $tab = sanitize_key($_GET['tab'] ?? 'inventory');

        // Per-tab filters from GET
        $filters = [
            'floor'    => sanitize_text_field($_GET['floor']    ?? ''),
            'size'     => sanitize_text_field($_GET['size']     ?? ''),
            'status'   => sanitize_text_field($_GET['status']   ?? ''),
            'expiring' => absint($_GET['expiring'] ?? 0),
        ];

        $data = [];

        switch ($tab) {
            case 'inventory':
                $data = Purplebox_DB::get_report_inventory($filters);
                break;
            case 'contracts':
                if ($filters['status'] === '') $filters['status'] = 'active';
                $data = Purplebox_DB::get_report_contracts($filters);
                break;
            case 'tenants':
                $data = Purplebox_DB::get_report_tenants($filters);
                break;
            case 'forecast':
                $data = Purplebox_DB::get_report_forecast();
                break;
            case 'occupancy':
                $data = Purplebox_DB::get_report_occupancy();
                break;
        }

        include PURPLEBOX_PLUGIN_DIR . 'views/reports.php';
    }
}
