<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Purplebox_Contracts_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'contract',
            'plural'   => 'contracts',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'cb'             => '<input type="checkbox" />',
            'id'             => __('#', 'purplebox-storage'),
            'tenant_name'    => __('Tenant', 'purplebox-storage'),
            'unit_numbers'   => __('Units', 'purplebox-storage'),
            'move_in_date'   => __('Move In', 'purplebox-storage'),
            'move_out_date'  => __('Move Out', 'purplebox-storage'),
            'payment_method' => __('Payment', 'purplebox-storage'),
            'status'         => __('Status', 'purplebox-storage'),
        ];
    }

    public function get_sortable_columns() {
        return [
            'id'           => ['id', true],
            'move_in_date' => ['move_in_date', false],
        ];
    }

    public function get_bulk_actions() {
        return [
            'end' => __('End Contract', 'purplebox-storage'),
        ];
    }

    public function process_bulk_action() {
        if ($this->current_action() !== 'end') {
            return;
        }

        if (!wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'bulk-contracts')) {
            wp_die(__('Security check failed', 'purplebox-storage'));
        }

        $ids = array_map('absint', $_REQUEST['contract'] ?? []);
        foreach ($ids as $id) {
            Purplebox_DB::end_contract($id);
        }
    }

    public function prepare_items() {
        $this->process_bulk_action();

        $per_page = 200;
        $current_page = $this->get_pagenum();

        $args = [
            'status'   => sanitize_text_field($_REQUEST['contract_status'] ?? ''),
            'per_page' => $per_page,
            'page'     => $current_page,
        ];

        $this->items = Purplebox_DB::get_contracts($args);
        $total_items = Purplebox_DB::count_contracts($args);

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
        return sprintf('<input type="checkbox" name="contract[]" value="%d" />', $item['id']);
    }

    public function column_id($item) {
        $view_url = admin_url('admin.php?page=purplebox-contracts&action=view&contract_id=' . $item['id']);
        $end_url  = wp_nonce_url(
            admin_url('admin.php?page=purplebox-contracts&action=end&contract_id=' . $item['id']),
            'purplebox_end_contract_' . $item['id']
        );
        $delete_url = wp_nonce_url(
            admin_url('admin.php?page=purplebox-contracts&action=delete&contract_id=' . $item['id']),
            'purplebox_delete_contract_' . $item['id']
        );

        $actions = [
            'view' => sprintf('<a href="%s">%s</a>', esc_url($view_url), __('View', 'purplebox-storage')),
        ];

        if ($item['status'] === 'active') {
            $actions['end'] = sprintf(
                '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($end_url),
                esc_js(__('End this contract?', 'purplebox-storage')),
                __('End Contract', 'purplebox-storage')
            );
        }

        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
            esc_url($delete_url),
            esc_js(__('Delete this contract permanently?', 'purplebox-storage')),
            __('Delete', 'purplebox-storage')
        );

        return sprintf(
            '<a href="%s" class="row-title"><strong>#%d</strong></a>%s',
            esc_url($view_url),
            $item['id'],
            $this->row_actions($actions)
        );
    }

    public function column_tenant_name($item) {
        $name = esc_html($item['tenant_name'] ?? '—');
        $client_id = !empty($item['tenant_client_id']) ? ' <span style="color:#50575e; font-size:11px;">(' . esc_html($item['tenant_client_id']) . ')</span>' : '';

        if (!empty($item['tenant_id'])) {
            $url = admin_url('admin.php?page=purplebox-tenants&action=edit&tenant_id=' . $item['tenant_id']);
            return '<a href="' . esc_url($url) . '">' . $name . '</a>' . $client_id;
        }
        return $name . $client_id;
    }

    public function column_unit_numbers($item) {
        if (empty($item['unit_ids'])) {
            return '<span style="color:#50575e;">—</span>';
        }
        return esc_html(Purplebox_DB::get_unit_numbers_from_ids($item['unit_ids']));
    }

    public function column_move_in_date($item) {
        return esc_html($item['move_in_date'] ?? '—');
    }

    public function column_move_out_date($item) {
        if (empty($item['move_out_date'])) {
            return '<em style="color:#50575e;">' . __('Open-ended', 'purplebox-storage') . '</em>';
        }
        $date = $item['move_out_date'];
        $today = current_time('Y-m-d');
        // Flag if ending within 7 days
        if ($item['status'] === 'active' && $date >= $today && $date <= date('Y-m-d', strtotime('+7 days', strtotime($today)))) {
            return '<span style="color:#8a6500; font-weight:600;">' . esc_html($date) . '</span>';
        }
        return esc_html($date);
    }

    public function column_payment_method($item) {
        return esc_html($item['payment_method'] ?? '—');
    }

    public function column_status($item) {
        $status = esc_attr($item['status']);
        $classes = [
            'active'    => 'available',
            'ended'     => 'ended',
            'cancelled' => 'ended',
        ];
        $class = $classes[$status] ?? 'ended';
        return '<span class="pill ' . $class . '">' . esc_html(ucfirst($status)) . '</span>';
    }

    public function column_default($item, $column_name) {
        return esc_html($item[$column_name] ?? '—');
    }

    public function get_views() {
        $current  = sanitize_text_field($_REQUEST['contract_status'] ?? '');
        $base_url = admin_url('admin.php?page=purplebox-contracts');

        $total  = Purplebox_DB::count_contracts();
        $active = Purplebox_DB::count_contracts(['status' => 'active']);
        $ended  = Purplebox_DB::count_contracts(['status' => 'ended']);

        return [
            'all' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url($base_url), $current === '' ? 'current' : '', __('All', 'purplebox-storage'), $total
            ),
            'active' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url(add_query_arg('contract_status', 'active', $base_url)),
                $current === 'active' ? 'current' : '',
                __('Active', 'purplebox-storage'), $active
            ),
            'ended' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url(add_query_arg('contract_status', 'ended', $base_url)),
                $current === 'ended' ? 'current' : '',
                __('Ended', 'purplebox-storage'), $ended
            ),
        ];
    }
}
