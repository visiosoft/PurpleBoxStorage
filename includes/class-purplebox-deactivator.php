<?php
if (!defined('ABSPATH')) {
    exit;
}

class Purplebox_Deactivator {

    public static function deactivate() {
        delete_transient('purplebox_dashboard_stats');
    }
}
