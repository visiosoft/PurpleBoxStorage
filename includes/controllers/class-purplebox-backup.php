<?php
if (!defined('ABSPATH')) exit;

class Purplebox_Backup_Controller {

    public static function render() {
        if (!current_user_can('manage_purplebox')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        global $wpdb;
        $stats = [
            'units'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}purplebox_units"),
            'tenants'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}purplebox_tenants"),
            'contracts' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}purplebox_contracts"),
        ];

        include PURPLEBOX_PLUGIN_DIR . 'views/backup.php';
    }

    public static function handle_export() {
        if (!current_user_can('manage_purplebox')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        if (!wp_verify_nonce($_POST['purplebox_nonce'] ?? '', 'purplebox_export_backup')) {
            wp_die(__('Security check failed', 'purplebox-storage'));
        }

        global $wpdb;

        $data = [
            'plugin'      => 'purplebox-storage',
            'version'     => PURPLEBOX_VERSION,
            'exported_at' => current_time('mysql'),
            'site_url'    => get_site_url(),
            'units'       => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}purplebox_units ORDER BY id ASC", ARRAY_A) ?? [],
            'tenants'     => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}purplebox_tenants ORDER BY id ASC", ARRAY_A) ?? [],
            'contracts'   => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}purplebox_contracts ORDER BY id ASC", ARRAY_A) ?? [],
        ];

        $filename = 'purplebox-backup-' . date('Y-m-d-His') . '.json';

        // Clear any existing output buffers
        while (ob_get_level()) ob_end_clean();

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function handle_import() {
        if (!current_user_can('manage_purplebox')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        if (!wp_verify_nonce($_POST['purplebox_nonce'] ?? '', 'purplebox_import_backup')) {
            wp_die(__('Security check failed', 'purplebox-storage'));
        }

        // Validate file upload
        if (empty($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(admin_url('admin.php?page=purplebox-backup&import_error=no_file'));
            exit;
        }

        $content = file_get_contents($_FILES['backup_file']['tmp_name']);
        if ($content === false) {
            wp_redirect(admin_url('admin.php?page=purplebox-backup&import_error=read_failed'));
            exit;
        }

        $data = json_decode($content, true);
        if (!$data || !is_array($data) || !isset($data['units'], $data['tenants'], $data['contracts'])) {
            wp_redirect(admin_url('admin.php?page=purplebox-backup&import_error=invalid_file'));
            exit;
        }

        if (($data['plugin'] ?? '') !== 'purplebox-storage') {
            wp_redirect(admin_url('admin.php?page=purplebox-backup&import_error=wrong_plugin'));
            exit;
        }

        $mode = sanitize_key($_POST['import_mode'] ?? 'skip'); // 'skip' or 'overwrite'

        global $wpdb;

        $results = [
            'units_imported'     => 0, 'units_skipped'      => 0,
            'tenants_imported'   => 0, 'tenants_skipped'    => 0,
            'contracts_imported' => 0, 'contracts_skipped'  => 0,
        ];

        // ── Import Units ──────────────────────────────────────────────────
        $units_table = $wpdb->prefix . 'purplebox_units';
        $unit_cols   = self::get_table_columns($units_table);

        foreach ((array) $data['units'] as $row) {
            $row = self::filter_columns($row, $unit_cols);
            unset($row['created_at'], $row['updated_at']);

            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $units_table WHERE unit_number = %s",
                $row['unit_number'] ?? ''
            ));

            if ($existing_id) {
                if ($mode === 'skip') {
                    $results['units_skipped']++;
                    continue;
                }
                $id = $row['id'] ?? $existing_id;
                unset($row['id']);
                $wpdb->update($units_table, $row, ['id' => $existing_id]);
            } else {
                unset($row['id']);
                $wpdb->insert($units_table, $row);
            }
            $results['units_imported']++;
        }

        // ── Import Tenants ────────────────────────────────────────────────
        $tenants_table = $wpdb->prefix . 'purplebox_tenants';
        $tenant_cols   = self::get_table_columns($tenants_table);

        foreach ((array) $data['tenants'] as $row) {
            $row = self::filter_columns($row, $tenant_cols);
            unset($row['created_at'], $row['updated_at']);

            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tenants_table WHERE client_id = %s",
                $row['client_id'] ?? ''
            ));

            if ($existing_id) {
                if ($mode === 'skip') {
                    $results['tenants_skipped']++;
                    continue;
                }
                unset($row['id']);
                $wpdb->update($tenants_table, $row, ['id' => $existing_id]);
            } else {
                unset($row['id']);
                $wpdb->insert($tenants_table, $row);
            }
            $results['tenants_imported']++;
        }

        // ── Import Contracts ──────────────────────────────────────────────
        $contracts_table = $wpdb->prefix . 'purplebox_contracts';
        $contract_cols   = self::get_table_columns($contracts_table);

        foreach ((array) $data['contracts'] as $row) {
            $row = self::filter_columns($row, $contract_cols);
            unset($row['created_at'], $row['updated_at']);

            $orig_id     = absint($row['id'] ?? 0);
            $existing_id = $orig_id ? $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $contracts_table WHERE id = %d",
                $orig_id
            )) : null;

            if ($existing_id) {
                if ($mode === 'skip') {
                    $results['contracts_skipped']++;
                    continue;
                }
                unset($row['id']);
                $wpdb->update($contracts_table, $row, ['id' => $existing_id]);
            } else {
                unset($row['id']);
                $wpdb->insert($contracts_table, $row);
            }
            $results['contracts_imported']++;
        }

        $query = http_build_query(array_merge(
            ['page' => 'purplebox-backup', 'imported' => 1],
            $results
        ));
        wp_redirect(admin_url('admin.php?' . $query));
        exit;
    }

    /** Return array of column names for a given table. */
    private static function get_table_columns($table) {
        global $wpdb;
        $rows = $wpdb->get_results("SHOW COLUMNS FROM $table", ARRAY_A) ?? [];
        return array_column($rows, 'Field');
    }

    /** Strip keys from $row that don't exist in $allowed_cols. */
    private static function filter_columns(array $row, array $allowed_cols) {
        return array_intersect_key($row, array_flip($allowed_cols));
    }
}
