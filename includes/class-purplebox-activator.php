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
            discount_pct decimal(5,2) DEFAULT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            unit_group varchar(100) DEFAULT NULL,
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

        // Safety net: ensure columns added in later versions exist
        // (covers cases where dbDelta silently skipped them)
        self::ensure_columns($units_table, $tenants_table);

        update_option('purplebox_db_version', PURPLEBOX_VERSION);

        // Add manage_purplebox capability to administrators
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_purplebox');
        }

        // PurpleBox Admin — full plugin access, no other WP admin sections
        if (!get_role('purplebox_admin')) {
            add_role('purplebox_admin', 'PurpleBox Admin', [
                'read'             => true,
                'manage_purplebox' => true,
                'upload_files'     => true,
            ]);
        }

        // PurpleBox Manager — limited plugin access (no units/inventory)
        if (!get_role('purplebox_manager')) {
            add_role('purplebox_manager', 'PurpleBox Manager', [
                'read'             => true,
                'manage_purplebox' => true,
                'upload_files'     => true,
            ]);
        }

        $upload_dir = wp_upload_dir();
        $purplebox_dir = $upload_dir['basedir'] . '/purplebox';
        if (!file_exists($purplebox_dir)) {
            wp_mkdir_p($purplebox_dir);
            wp_mkdir_p($purplebox_dir . '/contracts');
        }
    }

    /**
     * Directly ALTER TABLE to add any columns that may be missing.
     * Safe to run repeatedly — checks existence first.
     */
    public static function ensure_columns($units_table, $tenants_table) {
        global $wpdb;

        $contracts_table = $wpdb->prefix . 'purplebox_contracts';

        // Guard: if tables don't exist yet, skip — activate() will create them.
        if (!$wpdb->get_var("SHOW TABLES LIKE '$units_table'") ||
            !$wpdb->get_var("SHOW TABLES LIKE '$tenants_table'")) {
            return;
        }

        $units_columns = array_column(
            $wpdb->get_results("SHOW COLUMNS FROM $units_table", ARRAY_A) ?? [],
            'Field'
        );
        $tenants_columns = array_column(
            $wpdb->get_results("SHOW COLUMNS FROM $tenants_table", ARRAY_A) ?? [],
            'Field'
        );

        // Units table additions
        $units_add = [
            'display_name'     => "ALTER TABLE $units_table ADD COLUMN display_name varchar(200) DEFAULT NULL AFTER unit_number",
            'discounted_price' => "ALTER TABLE $units_table ADD COLUMN discounted_price decimal(10,2) DEFAULT NULL AFTER price",
            'discount_pct'     => "ALTER TABLE $units_table ADD COLUMN discount_pct decimal(5,2) DEFAULT NULL AFTER discounted_price",
            'quantity'         => "ALTER TABLE $units_table ADD COLUMN quantity int(11) NOT NULL DEFAULT 1 AFTER discount_pct",
            'unit_group'       => "ALTER TABLE $units_table ADD COLUMN unit_group varchar(100) DEFAULT NULL AFTER quantity",
        ];
        foreach ($units_add as $col => $sql) {
            if (!in_array($col, $units_columns)) {
                $wpdb->query($sql);
            }
        }

        // Tenants table additions
        $tenants_add = [
            'passport_number' => "ALTER TABLE $tenants_table ADD COLUMN passport_number varchar(30) DEFAULT NULL AFTER eid_expiry",
            'passport_expiry' => "ALTER TABLE $tenants_table ADD COLUMN passport_expiry date DEFAULT NULL AFTER passport_number",
            'access_persons'  => "ALTER TABLE $tenants_table ADD COLUMN access_persons text DEFAULT NULL AFTER address",
        ];
        foreach ($tenants_add as $col => $sql) {
            if (!in_array($col, $tenants_columns)) {
                $wpdb->query($sql);
            }
        }

        // Contracts table additions
        if ($wpdb->get_var("SHOW TABLES LIKE '$contracts_table'")) {
            $contracts_columns = array_column(
                $wpdb->get_results("SHOW COLUMNS FROM $contracts_table", ARRAY_A) ?? [],
                'Field'
            );
            $contracts_add = [
                'first_payment_date' => "ALTER TABLE $contracts_table ADD COLUMN first_payment_date date DEFAULT NULL AFTER move_out_date",
            ];
            foreach ($contracts_add as $col => $sql) {
                if (!in_array($col, $contracts_columns)) {
                    $wpdb->query($sql);
                }
            }
        }
    }
}
