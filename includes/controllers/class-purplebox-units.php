<?php
if (!defined('ABSPATH')) {
    exit;
}

class Purplebox_Units_Controller {

    public static function render_list() {
        if (!current_user_can('manage_purplebox')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['unit_id'])) {
            $unit_id = absint($_GET['unit_id']);
            if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'purplebox_delete_unit_' . $unit_id)) {
                Purplebox_DB::delete_unit($unit_id);
                wp_redirect(admin_url('admin.php?page=purplebox-units&deleted=1'));
                exit;
            }
        }

        require_once PURPLEBOX_PLUGIN_DIR . 'includes/tables/class-purplebox-units-table.php';
        $table = new Purplebox_Units_Table();
        $table->prepare_items();

        include PURPLEBOX_PLUGIN_DIR . 'views/units-list.php';
    }

    public static function render_edit() {
        if (!current_user_can('manage_purplebox')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        $unit_id = absint($_GET['unit_id'] ?? 0);
        $unit = $unit_id ? Purplebox_DB::get_unit($unit_id) : null;
        $is_rented = $unit_id ? Purplebox_DB::is_unit_rented($unit_id) : false;

        include PURPLEBOX_PLUGIN_DIR . 'views/unit-edit.php';
    }

    public static function handle_save() {
        if (!wp_verify_nonce($_POST['purplebox_nonce'] ?? '', 'purplebox_save_unit')) {
            wp_die(__('Security check failed', 'purplebox-storage'));
        }

        $data = [
            'id'           => absint($_POST['unit_id'] ?? 0),
            'unit_number'  => sanitize_text_field($_POST['unit_number'] ?? ''),
            'display_name' => $_POST['display_name'] ?? '',
            'size_category'=> $_POST['size_category'] ?? '',
            'custom_size'  => $_POST['custom_size'] ?? '',
            'floor'        => $_POST['floor'] ?? 'Ground',
            'price'        => $_POST['price'] ?? 0,
            'discount_pct' => $_POST['discount_pct'] ?? '',
            'quantity'     => 1,
            'facility'     => $_POST['facility'] ?? '',
            'features'     => $_POST['features'] ?? [],
            'notes'          => $_POST['notes'] ?? '',
            'manual_status'  => $_POST['manual_status'] ?? '',
        ];

        $is_new = empty($data['id']);
        $id     = Purplebox_DB::save_unit($data);

        $msg = $is_new ? 'created' : 'updated';
        wp_redirect(admin_url('admin.php?page=purplebox-units&saved=' . $msg));
        exit;
    }
}
