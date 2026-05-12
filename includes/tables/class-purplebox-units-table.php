<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Purplebox_Units_Table extends WP_List_Table {

    private $rented_counts = [];

    public function __construct() {
        parent::__construct([
            'singular' => 'unit',
            'plural'   => 'units',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'unit_number'   => __('Unit #', 'purplebox-storage'),
            'display_name'  => __('Name / Label', 'purplebox-storage'),
            'size_category' => __('Size', 'purplebox-storage'),
            'floor'         => __('Floor', 'purplebox-storage'),
            'price'         => __('Price (AED)', 'purplebox-storage'),
            'stock'         => __('Stock', 'purplebox-storage'),
            'status'        => __('Status', 'purplebox-storage'),
        ];
    }

    public function get_sortable_columns() {
        return [
            'unit_number'   => ['unit_number', true],
            'size_category' => ['size_category', false],
            'floor'         => ['floor', false],
            'price'         => ['price', false],
        ];
    }

    public function get_bulk_actions() {
        return [
            'delete' => __('Delete', 'purplebox-storage'),
        ];
    }

    public function process_bulk_action() {
        if ($this->current_action() !== 'delete') {
            return;
        }

        if (!wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'bulk-units')) {
            wp_die(__('Security check failed', 'purplebox-storage'));
        }

        $ids = array_map('absint', $_REQUEST['unit'] ?? []);
        foreach ($ids as $id) {
            Purplebox_DB::delete_unit($id);
        }
    }

    public function prepare_items() {
        $this->process_bulk_action();
        $this->rented_counts = Purplebox_DB::get_rented_count_per_unit();

        $per_page = 30;
        $current_page = $this->get_pagenum();

        $args = [
            'search'   => sanitize_text_field($_REQUEST['s'] ?? ''),
            'floor'    => sanitize_text_field($_REQUEST['floor_filter'] ?? ''),
            'size'     => sanitize_text_field($_REQUEST['size'] ?? ''),
            'orderby'  => sanitize_text_field($_REQUEST['orderby'] ?? 'unit_number'),
            'order'    => sanitize_text_field($_REQUEST['order'] ?? 'ASC'),
            'per_page' => $per_page,
            'page'     => $current_page,
        ];

        $this->items = Purplebox_DB::get_units($args);
        $total_items = Purplebox_DB::count_units($args);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);

        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns(),
        ];
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="unit[]" value="%d" />', $item['id']);
    }

    public function column_unit_number($item) {
        $edit_url = admin_url('admin.php?page=purplebox-unit-edit&unit_id=' . $item['id']);
        $delete_url = wp_nonce_url(
            admin_url('admin.php?page=purplebox-units&action=delete&unit_id=' . $item['id']),
            'purplebox_delete_unit_' . $item['id']
        );

        $actions = [
            'edit'   => sprintf('<a href="%s">%s</a>', esc_url($edit_url), __('Edit', 'purplebox-storage')),
            'delete' => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($delete_url),
                esc_js(__('Delete this unit permanently?', 'purplebox-storage')),
                __('Delete', 'purplebox-storage')
            ),
        ];

        return sprintf(
            '<a href="%s" class="row-title"><strong>%s</strong></a>%s',
            esc_url($edit_url),
            esc_html($item['unit_number']),
            $this->row_actions($actions)
        );
    }

    public function column_display_name($item) {
        if (!empty($item['display_name'])) {
            return esc_html($item['display_name']);
        }
        return '<span style="color:#50575e;">—</span>';
    }

    public function column_size_category($item) {
        $label = esc_html($item['size_category']);
        if ($item['size_category'] === 'Custom' && !empty($item['custom_size'])) {
            $label .= ' (' . esc_html($item['custom_size']) . ' sq.ft.)';
        }
        return $label;
    }

    public function column_floor($item) {
        return esc_html($item['floor']);
    }

    public function column_price($item) {
        if (empty($item['price'])) {
            return '<span style="color:#50575e;">—</span>';
        }
        $out = 'AED ' . number_format((float) $item['price'], 2);
        if (!empty($item['discounted_price'])) {
            $out = '<span style="text-decoration:line-through; color:#50575e; font-size:11px;">AED ' . number_format((float) $item['price'], 2) . '</span><br>'
                 . '<strong style="color:#00691f;">AED ' . number_format((float) $item['discounted_price'], 2) . '</strong>';
        }
        return $out;
    }

    public function column_stock($item) {
        $total  = max(1, (int) ($item['quantity'] ?? 1));
        if (!empty($item['manual_status']) && $item['manual_status'] === 'rented') {
            $avail = 0;
        } else {
            $rented = $this->rented_counts[(int) $item['id']] ?? 0;
            $avail  = max(0, $total - $rented);
        }

        if ($avail === 0) {
            $color = '#b32d2e';
        } elseif ($avail <= max(1, (int) ($total * 0.25))) {
            $color = '#8a6500';
        } else {
            $color = '#00691f';
        }
        return sprintf(
            '<span style="font-weight:600; color:%s;">%d / %d</span>',
            esc_attr($color),
            $avail,
            $total
        );
    }

    public function column_status($item) {
        if (!empty($item['manual_status']) && $item['manual_status'] === 'rented') {
            return '<span class="pill" style="background:#fef3cd; color:#856404;">' . __('Manual', 'purplebox-storage') . '</span>';
        }
        $total     = max(1, (int) ($item['quantity'] ?? 1));
        $rented    = $this->rented_counts[(int) $item['id']] ?? 0;
        $is_rented = $rented >= $total;
        if ($is_rented) {
            return '<span class="pill" style="background:#e8f0fe; color:#1e4ea1;">' . __('Rented', 'purplebox-storage') . '</span>';
        }
        return '<span class="pill available">' . __('Available', 'purplebox-storage') . '</span>';
    }

    public function column_features($item) {
        if (empty($item['features'])) {
            return '<span style="color:#50575e;">—</span>';
        }
        $features = json_decode($item['features'], true);
        if (!is_array($features) || empty($features)) {
            return '<span style="color:#50575e;">—</span>';
        }
        return esc_html(implode(', ', $features));
    }

    public function column_default($item, $column_name) {
        return esc_html($item[$column_name] ?? '—');
    }

    public function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }
        ?>
        <div class="alignleft actions">
            <select name="size">
                <option value=""><?php esc_html_e('All sizes', 'purplebox-storage'); ?></option>
                <?php
                $sizes = ['Locker', '25 sq.ft.', '35 sq.ft.', '50 sq.ft.', '75 sq.ft.', '100 sq.ft.', '150 sq.ft.', '200 sq.ft.', 'Custom'];
                $current = sanitize_text_field($_REQUEST['size'] ?? '');
                foreach ($sizes as $size) {
                    printf('<option value="%s" %s>%s</option>', esc_attr($size), selected($current, $size, false), esc_html($size));
                }
                ?>
            </select>
            <select name="floor_filter">
                <option value=""><?php esc_html_e('All floors', 'purplebox-storage'); ?></option>
                <?php
                $floors = ['F1', 'F2', 'F3'];
                $current_floor = sanitize_text_field($_REQUEST['floor_filter'] ?? '');
                foreach ($floors as $floor) {
                    printf('<option value="%s" %s>%s</option>', esc_attr($floor), selected($current_floor, $floor, false), esc_html($floor));
                }
                ?>
            </select>
            <?php submit_button(__('Filter', 'purplebox-storage'), '', 'filter_action', false); ?>
        </div>
        <?php
    }
}
