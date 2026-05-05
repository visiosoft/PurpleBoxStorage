<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Purplebox_Tenants_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'tenant',
            'plural'   => 'tenants',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'client_id'    => __('Client ID', 'purplebox-storage'),
            'full_name'    => __('Name', 'purplebox-storage'),
            'tenant_type'  => __('Type', 'purplebox-storage'),
            'phones'       => __('Phone(s)', 'purplebox-storage'),
            'email'        => __('Email', 'purplebox-storage'),
            'active_units' => __('Active Units', 'purplebox-storage'),
            'status'       => __('Status', 'purplebox-storage'),
        ];
    }

    public function get_sortable_columns() {
        return [
            'client_id'   => ['client_id', true],
            'full_name'   => ['full_name', false],
            'tenant_type' => ['tenant_type', false],
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

        if (!wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'bulk-tenants')) {
            wp_die(__('Security check failed', 'purplebox-storage'));
        }

        $ids = array_map('absint', $_REQUEST['tenant'] ?? []);
        foreach ($ids as $id) {
            Purplebox_DB::delete_tenant($id);
        }
    }

    public function prepare_items() {
        $this->process_bulk_action();

        $per_page = 20;
        $current_page = $this->get_pagenum();

        $args = [
            'search'   => sanitize_text_field($_REQUEST['s'] ?? ''),
            'status'   => sanitize_text_field($_REQUEST['tenant_status'] ?? ''),
            'type'     => sanitize_text_field($_REQUEST['tenant_type'] ?? ''),
            'orderby'  => sanitize_text_field($_REQUEST['orderby'] ?? 'full_name'),
            'order'    => sanitize_text_field($_REQUEST['order'] ?? 'ASC'),
            'per_page' => $per_page,
            'page'     => $current_page,
        ];

        $this->items = Purplebox_DB::get_tenants($args);
        $total_items = Purplebox_DB::count_tenants($args);

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
        return sprintf('<input type="checkbox" name="tenant[]" value="%d" />', $item['id']);
    }

    public function column_client_id($item) {
        return '<code style="font-size:12px;">' . esc_html($item['client_id']) . '</code>';
    }

    public function column_full_name($item) {
        $edit_url    = admin_url('admin.php?page=purplebox-tenants&action=edit&tenant_id=' . $item['id']);
        $delete_url  = wp_nonce_url(
            admin_url('admin.php?page=purplebox-tenants&action=delete&tenant_id=' . $item['id']),
            'purplebox_delete_tenant_' . $item['id']
        );
        $contract_url = admin_url('admin.php?page=purplebox-contract-new&tenant_id=' . $item['id']);

        $actions = [
            'edit'     => sprintf('<a href="%s">%s</a>', esc_url($edit_url), __('Edit', 'purplebox-storage')),
            'contract' => sprintf('<a href="%s">%s</a>', esc_url($contract_url), __('New Contract', 'purplebox-storage')),
            'delete'   => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($delete_url),
                esc_js(__('Delete this tenant?', 'purplebox-storage')),
                __('Delete', 'purplebox-storage')
            ),
        ];

        return sprintf(
            '<a href="%s" class="row-title">%s</a>%s',
            esc_url($edit_url),
            esc_html($item['full_name']),
            $this->row_actions($actions)
        );
    }

    public function column_tenant_type($item) {
        return esc_html(ucfirst($item['tenant_type']));
    }

    public function column_phones($item) {
        if (empty($item['phones'])) {
            return '<span style="color:#50575e;">—</span>';
        }
        $phones = json_decode($item['phones'], true);
        if (!is_array($phones) || empty($phones)) {
            return esc_html($item['phones']);
        }
        $links = array_map(function($p) {
            return '<a href="tel:' . esc_attr($p) . '">' . esc_html($p) . '</a>';
        }, $phones);
        return implode('<br>', $links);
    }

    public function column_email($item) {
        if (empty($item['email'])) {
            return '<span style="color:#50575e;">—</span>';
        }
        return '<a href="mailto:' . esc_attr($item['email']) . '">' . esc_html($item['email']) . '</a>';
    }

    public function column_active_units($item) {
        $units = Purplebox_DB::get_active_units_for_tenant($item['id']);
        if (empty($units)) {
            return '<span style="color:#50575e;">—</span>';
        }

        $labels = [];
        foreach ($units as $u) {
            $url = admin_url('admin.php?page=purplebox-unit-edit&unit_id=' . $u['id']);
            $labels[] = '<a href="' . esc_url($url) . '">' . esc_html($u['unit_number']) . '</a>';
        }
        return implode(', ', $labels);
    }

    public function column_status($item) {
        $status = esc_attr($item['status']);
        return '<span class="pill ' . $status . '">' . esc_html(ucfirst($status)) . '</span>';
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
            <select name="tenant_type">
                <option value=""><?php esc_html_e('All types', 'purplebox-storage'); ?></option>
                <option value="individual" <?php selected($_REQUEST['tenant_type'] ?? '', 'individual'); ?>><?php esc_html_e('Individual', 'purplebox-storage'); ?></option>
                <option value="company" <?php selected($_REQUEST['tenant_type'] ?? '', 'company'); ?>><?php esc_html_e('Company', 'purplebox-storage'); ?></option>
            </select>
            <?php submit_button(__('Filter', 'purplebox-storage'), '', 'filter_action', false); ?>
        </div>
        <?php
    }

    public function get_views() {
        $current = sanitize_text_field($_REQUEST['tenant_status'] ?? '');
        $total   = Purplebox_DB::count_tenants();
        $active  = Purplebox_DB::count_tenants(['status' => 'active']);
        $ended   = Purplebox_DB::count_tenants(['status' => 'ended']);
        $b2b     = Purplebox_DB::count_tenants(['type' => 'company']);

        $base_url = admin_url('admin.php?page=purplebox-tenants');

        return [
            'all' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url($base_url), $current === '' ? 'current' : '', __('All', 'purplebox-storage'), $total
            ),
            'active' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url(add_query_arg('tenant_status', 'active', $base_url)),
                $current === 'active' ? 'current' : '',
                __('Active', 'purplebox-storage'), $active
            ),
            'ended' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url(add_query_arg('tenant_status', 'ended', $base_url)),
                $current === 'ended' ? 'current' : '',
                __('Ended', 'purplebox-storage'), $ended
            ),
            'b2b' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url(add_query_arg('tenant_type', 'company', $base_url)),
                '',
                __('B2B', 'purplebox-storage'), $b2b
            ),
        ];
    }
}
