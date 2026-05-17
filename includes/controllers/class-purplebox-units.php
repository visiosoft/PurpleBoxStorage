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

        // One-time Excel seed import
        if (isset($_GET['action']) && $_GET['action'] === 'seed_import') {
            if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'purplebox_seed_import')) {
                $result = self::run_seed_import();
                wp_redirect(admin_url('admin.php?page=purplebox-units&seed_imported=' . $result['inserted'] . '&seed_skipped=' . $result['skipped'] . '&seed_rented=' . $result['rented']));
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

    /**
     * One-time seed import from Excel inventory data.
     */
    public static function run_seed_import() {
        global $wpdb;
        $table = $wpdb->prefix . 'purplebox_units';

        $units = [
            ['F1',1,154,2880,'done',0],['F1',2,154,2880,'done',0],['F1',3,154,2880,'done',0],['F1',4,154,2880,'done',0],
            ['F1',5,154,2880,'done (cancel electrical outlet and paint)',0],['F1',6,154,2880,'used',1],
            ['F1',7,25,750,'done',0],['F1',8,25,750,'done',0],['F1',9,42,null,'done',0],['F1',10,78,1200,'used',1],
            ['F1',11,null,null,'still not built yet',0],['F1',12,110,1600,'done',0],['F1',13,110,1600,'done',0],
            ['F1',14,190,2800,'done',0],['F1',15,190,2800,'done',0],['F1',16,190,2800,'done',0],
            ['F1',17,156,2880,'done',0],['F1',18,150,2880,'done',0],['F1',19,70,1200,'done',0],
            ['F1',20,55,990,'done',0],['F1',21,40,990,'done',0],['F1',22,78,1440,'done (used store)',1],
            ['F1',23,84,1200,'done',0],['F1',24,77,1200,'done',0],
            ['F1',25,24,750,'done',0],['F1',26,24,750,'done',0],['F1',27,24,750,'done',0],
            ['F1',28,24,750,'done',0],['F1',29,24,750,'done',0],['F1',30,24,750,'done',0],
            ['F1',31,140,2640,'done',0],
            ['F1',32,25,750,'done',0],['F1',33,25,750,'done',0],['F1',34,25,750,'done',0],['F1',35,25,750,'done',0],
            ['F1',36,190,2880,'done',0],['F1',37,190,2880,'done',0],['F1',38,190,2880,'done',0],
            ['F1',39,190,2880,'done',0],['F1',40,190,2880,'done',0],['F1',41,190,2880,'done',0],
            ['F1',42,190,2880,'done (pending cancel fire alarm)',0],['F1',43,190,2880,'done',0],
            ['F1',44,190,2880,'pending epoxy paint (fire alarm store)',0],
            // F2
            ['F2',1,49,990,'missing ceiling and sprinkler',0],['F2',2,49,990,'missing ceiling and sprinkler',0],
            ['F2',3,49,990,'missing ceiling and sprinkler',0],['F2',4,49,990,'missing ceiling and sprinkler',0],
            ['F2',5,45,990,'missing ceiling and sprinkler',0],['F2',6,45,990,'missing ceiling and sprinkler',0],
            ['F2',7,35,770,'missing ceiling and sprinkler',0],['F2',8,35,770,'missing ceiling and sprinkler',0],
            ['F2',9,50,990,'missing ceiling and sprinkler',0],['F2',10,50,990,'missing ceiling and sprinkler',0],
            ['F2',11,50,990,'missing ceiling and sprinkler',0],['F2',12,50,990,'missing ceiling and sprinkler',0],
            ['F2',13,50,990,'store (pending epoxy paint)',0],['F2',14,75,1320,'done (fire hose reel)',0],
            ['F2',15,154,2640,'done',0],['F2',16,154,2640,'done',0],
            ['F2',17,47,990,'not yet installed',0],['F2',18,35,770,'not yet installed',0],
            ['F2',19,47,990,'not yet installed',0],['F2',20,null,null,'not yet installed',0],
            ['F2',21,null,null,'not yet installed',0],
            ['F2',22,35,770,'missing ceiling and sprinkler',0],['F2',23,34,770,'missing ceiling and sprinkler',0],
            ['F2',24,85,1320,'missing ceiling and sprinkler',0],['F2',25,85,1320,'missing ceiling and sprinkler',0],
            ['F2',26,85,1320,'missing ceiling and sprinkler',0],
            ['F2',27,32,770,'missing ceiling and sprinkler',0],['F2',28,32,770,'missing ceiling and sprinkler',0],
            ['F2',29,32,770,'missing ceiling and sprinkler',0],['F2',30,32,770,'missing ceiling and sprinkler',0],
            ['F2',31,32,770,'missing ceiling and sprinkler',0],
            ['F2',32,45,990,'missing ceiling and sprinkler',0],['F2',33,45,990,'missing ceiling and sprinkler',0],
            ['F2',34,45,990,'missing ceiling and sprinkler',0],['F2',35,35,770,'missing ceiling and sprinkler',0],
            ['F2',36,27,687,'missing ceiling and sprinkler',0],['F2',37,27,687,'missing ceiling and sprinkler',0],
            ['F2',38,98,1760,'missing ceiling and sprinkler',0],['F2',39,69,1320,'missing ceiling and sprinkler',0],
            ['F2',40,98,1760,'missing ceiling and sprinkler',0],['F2',41,98,1760,'missing ceiling and sprinkler',0],
            ['F2',42,98,1760,'missing ceiling and sprinkler',0],
            ['F2',43,145,2640,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',44,145,2640,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',45,145,2640,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',46,145,2640,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',47,11,440,'not yet installed',0],['F2',48,11,440,'not yet installed',0],
            ['F2',49,11,440,'not yet installed',0],['F2',50,11,440,'not yet installed',0],
            ['F2',51,11,440,'not yet installed',0],['F2',52,11,440,'not yet installed',0],
            ['F2',53,11,440,'not yet installed',0],['F2',54,11,440,'not yet installed',0],
            ['F2',55,11,440,'not yet installed',0],['F2',56,11,440,'not yet installed',0],
            ['F2',57,45,990,'not yet installed',0],['F2',58,31,770,'not yet installed',0],
            ['F2',59,45,990,'not yet installed',0],
            ['F2',60,45,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',61,49,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',62,49,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',63,49,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',64,49,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',65,49,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',66,50,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',67,42,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',68,46,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',69,96,1760,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',70,47,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',71,47,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',72,34,770,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',73,34,770,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',74,98,1760,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',75,35,770,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',76,35,770,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',77,50,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',78,50,990,'missing ceiling and sprinkler and epoxy paint',0],
            ['F2',79,36,770,'',0],['F2',80,36,770,'',0],['F2',81,36,770,'',0],['F2',82,36,770,'',0],
            ['F2',83,36,770,'',0],['F2',84,36,770,'',0],['F2',85,36,770,'',0],['F2',86,36,770,'',0],
            ['F2',87,25,687,'',0],['F2',88,46,990,'',0],['F2',89,67,1320,'',0],['F2',90,44,990,'',0],
            ['F2',91,33,770,'',0],['F2',92,49,990,'',0],
            ['F2',93,94,1760,'',0],['F2',94,94,1760,'',0],['F2',95,94,1760,'',0],
            ['F2',96,94,1760,'',0],['F2',97,94,1760,'',0],
            ['F2',98,76,1320,'',0],['F2',99,76,1320,'',0],['F2',100,76,1320,'',0],
            ['F2',101,76,1320,'',0],['F2',102,76,1320,'',0],['F2',103,76,1320,'',0],
            ['F2',104,38,770,'',0],['F2',105,35,770,'',0],['F2',106,75,1320,'',0],
            ['F2',107,100,1760,'',0],['F2',108,100,1760,'',0],['F2',109,100,1760,'',0],
            ['F2',110,60,990,'',0],['F2',111,37,770,'',0],
        ];

        $size_map = function($sqf) {
            if ($sqf === null) return 'Custom';
            if ($sqf <= 8)   return '10 sq.ft.';
            if ($sqf <= 15)  return 'Locker';
            if ($sqf <= 30)  return '25 sq.ft.';
            if ($sqf <= 40)  return '35 sq.ft.';
            if ($sqf <= 60)  return '50 sq.ft.';
            if ($sqf <= 85)  return '75 sq.ft.';
            if ($sqf <= 120) return '100 sq.ft.';
            if ($sqf <= 160) return '150 sq.ft.';
            return '200 sq.ft.';
        };

        $inserted = 0; $skipped = 0; $rented = 0;

        foreach ($units as [$floor, $num, $sqf, $price, $notes, $used]) {
            $unit_number = $floor . '-' . str_pad($num, 2, '0', STR_PAD_LEFT);

            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE unit_number = %s", $unit_number));
            if ($exists) { $skipped++; continue; }

            $wpdb->insert($table, [
                'unit_number'   => $unit_number,
                'size_category' => $size_map($sqf),
                'custom_size'   => $sqf,
                'floor'         => $floor,
                'price'         => $price ?? 0,
                'quantity'      => 1,
                'facility'      => 'PurpleBox Al Quoz',
                'notes'         => $notes,
                'manual_status' => $used ? 'rented' : null,
            ]);
            $inserted++;
            if ($used) $rented++;
        }

        return ['inserted' => $inserted, 'skipped' => $skipped, 'rented' => $rented];
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
            'floor'        => $_POST['floor'] ?? 'F1',
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
