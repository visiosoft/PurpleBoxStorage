<?php
if (!defined('ABSPATH')) {
    exit;
}

class Purplebox_Tenants_Controller {

    public static function render_list() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['tenant_id'])) {
            self::render_edit();
            return;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['tenant_id'])) {
            $tenant_id = absint($_GET['tenant_id']);
            if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'purplebox_delete_tenant_' . $tenant_id)) {
                Purplebox_DB::delete_tenant($tenant_id);
                wp_redirect(admin_url('admin.php?page=purplebox-tenants&deleted=1'));
                exit;
            }
        }

        require_once PURPLEBOX_PLUGIN_DIR . 'includes/tables/class-purplebox-tenants-table.php';
        $table = new Purplebox_Tenants_Table();
        $table->prepare_items();

        include PURPLEBOX_PLUGIN_DIR . 'views/tenants-list.php';
    }

    public static function render_edit() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        $tenant_id = absint($_GET['tenant_id'] ?? 0);
        $tenant = $tenant_id ? Purplebox_DB::get_tenant($tenant_id) : null;

        // Decode phones JSON for the view
        if ($tenant && !empty($tenant['phones'])) {
            $tenant['phones_array'] = json_decode($tenant['phones'], true) ?: [];
        } else {
            $tenant['phones_array'] = [''];
        }

        // Decode access_persons JSON for the view
        if ($tenant && !empty($tenant['access_persons'])) {
            $tenant['access_persons_array'] = json_decode($tenant['access_persons'], true) ?: [];
        } else {
            $tenant['access_persons_array'] = [];
        }

        include PURPLEBOX_PLUGIN_DIR . 'views/tenant-edit.php';
    }

    public static function handle_save() {
        if (!wp_verify_nonce($_POST['purplebox_nonce'] ?? '', 'purplebox_save_tenant')) {
            wp_die(__('Security check failed', 'purplebox-storage'));
        }

        $data = [
            'id'               => absint($_POST['tenant_id'] ?? 0),
            'full_name'        => $_POST['full_name'] ?? '',
            'tenant_type'      => $_POST['tenant_type'] ?? 'individual',
            'phones'           => $_POST['phones'] ?? [''],
            'email'            => $_POST['email'] ?? '',
            'emirates_id'      => $_POST['emirates_id'] ?? '',
            'eid_expiry'       => $_POST['eid_expiry'] ?? '',
            'passport_number'  => $_POST['passport_number'] ?? '',
            'passport_expiry'  => $_POST['passport_expiry'] ?? '',
            'nationality'      => $_POST['nationality'] ?? '',
            'address'          => $_POST['address'] ?? '',
            'status'           => $_POST['status'] ?? 'active',
            'access_name'      => $_POST['access_name'] ?? [],
            'access_phone'     => $_POST['access_phone'] ?? [],
            'access_relation'  => $_POST['access_relation'] ?? [],
            'access_id_type'   => $_POST['access_id_type'] ?? [],
            'access_id_number' => $_POST['access_id_number'] ?? [],
        ];

        $id = Purplebox_DB::save_tenant($data);

        wp_redirect(admin_url('admin.php?page=purplebox-tenants&action=edit&tenant_id=' . $id . '&saved=1'));
        exit;
    }
}
