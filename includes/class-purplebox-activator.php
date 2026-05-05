<?php
if (!defined('ABSPATH')) {
    exit;
}

class Purplebox_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $units_table = $wpdb->prefix . 'purplebox_units';
        $tenants_table = $wpdb->prefix . 'purplebox_tenants';
        $contracts_table = $wpdb->prefix . 'purplebox_contracts';

        // Drop old tables if schema changed
        $old_version = get_option('purplebox_db_version', '0');
        if (version_compare($old_version, '2.0.0', '<')) {
            $wpdb->query("DROP TABLE IF EXISTS $contracts_table");
            $wpdb->query("DROP TABLE IF EXISTS $units_table");
            $wpdb->query("DROP TABLE IF EXISTS $tenants_table");
        }

        $sql = "CREATE TABLE $units_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            unit_number varchar(30) NOT NULL,
            display_name varchar(200) DEFAULT NULL,
            size_category varchar(30) NOT NULL,
            custom_size decimal(8,2) DEFAULT NULL,
            floor varchar(20) NOT NULL DEFAULT 'Ground',
            price decimal(10,2) NOT NULL DEFAULT 0,
            discounted_price decimal(10,2) DEFAULT NULL,
            facility varchar(100) NOT NULL DEFAULT 'PurpleBox Al Quoz',
            features text DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unit_number (unit_number)
        ) $charset_collate;

        CREATE TABLE $tenants_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            client_id varchar(20) NOT NULL,
            full_name varchar(200) NOT NULL,
            tenant_type varchar(20) NOT NULL DEFAULT 'individual',
            phones text NOT NULL,
            email varchar(100) NOT NULL,
            emirates_id varchar(30) DEFAULT NULL,
            eid_expiry date DEFAULT NULL,
            passport_number varchar(30) DEFAULT NULL,
            passport_expiry date DEFAULT NULL,
            nationality varchar(60) DEFAULT NULL,
            address text DEFAULT NULL,
            access_persons text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY client_id (client_id)
        ) $charset_collate;

        CREATE TABLE $contracts_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) unsigned NOT NULL,
            unit_ids text NOT NULL,
            move_in_date date NOT NULL,
            move_out_date date DEFAULT NULL,
            duration_weeks int(11) DEFAULT NULL,
            payment_method varchar(30) NOT NULL DEFAULT 'Cash',
            next_payment_date date DEFAULT NULL,
            auto_renew tinyint(1) NOT NULL DEFAULT 1,
            signed_pdf_path varchar(500) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tenant_id (tenant_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('purplebox_db_version', PURPLEBOX_VERSION);

        $upload_dir = wp_upload_dir();
        $purplebox_dir = $upload_dir['basedir'] . '/purplebox';
        if (!file_exists($purplebox_dir)) {
            wp_mkdir_p($purplebox_dir);
            wp_mkdir_p($purplebox_dir . '/contracts');
        }
    }
}
