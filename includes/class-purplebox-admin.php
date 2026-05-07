<?php
if (!defined('ABSPATH')) {
    exit;
}

class Purplebox_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'handle_form_submissions']);
        add_action('admin_notices', [$this, 'expiry_notices']);
        add_action('wp_ajax_purplebox_search_tenants', [$this, 'ajax_search_tenants']);
        add_action('wp_ajax_purplebox_available_units', [$this, 'ajax_available_units']);
    }

    public function expiry_notices() {
        // Only show on PurpleBox pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'purplebox') === false) {
            return;
        }

        $expiring = Purplebox_DB::get_expiring_contracts(15);
        if (empty($expiring)) {
            return;
        }

        foreach ($expiring as $c) {
            $units     = Purplebox_DB::get_unit_numbers_from_ids($c['unit_ids'] ?? '[]');
            $days_left = (int) $c['days_left'];
            $view_url  = admin_url('admin.php?page=purplebox-contracts&action=view&contract_id=' . $c['id']);
            $date      = date('d/m/Y', strtotime($c['move_out_date']));

            if ($days_left === 0) {
                $when = __('expires <strong>today</strong>', 'purplebox-storage');
                $type = 'notice-error';
            } elseif ($days_left === 1) {
                $when = __('expires <strong>tomorrow</strong>', 'purplebox-storage');
                $type = 'notice-error';
            } elseif ($days_left <= 7) {
                $when = sprintf(__('expires in <strong>%d days</strong> (%s)', 'purplebox-storage'), $days_left, $date);
                $type = 'notice-warning';
            } else {
                $when = sprintf(__('expires in <strong>%d days</strong> (%s)', 'purplebox-storage'), $days_left, $date);
                $type = 'notice-info';
            }

            printf(
                '<div class="notice %s is-dismissible"><p>⚠️ <strong>%s</strong> — %s — %s. <a href="%s">%s</a></p></div>',
                esc_attr($type),
                esc_html($c['tenant_name']),
                esc_html($units),
                wp_kses($when, ['strong' => []]),
                esc_url($view_url),
                esc_html__('View Contract', 'purplebox-storage')
            );
        }
    }

    public function register_menus() {
        add_menu_page(
            __('PurpleBox', 'purplebox-storage'),
            __('PurpleBox', 'purplebox-storage'),
            'manage_options',
            'purplebox-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-building',
            26
        );

        add_submenu_page(
            'purplebox-dashboard',
            __('Dashboard', 'purplebox-storage'),
            __('Dashboard', 'purplebox-storage'),
            'manage_options',
            'purplebox-dashboard',
            [$this, 'render_dashboard']
        );

        add_submenu_page(
            'purplebox-dashboard',
            __('Storage Inventory', 'purplebox-storage'),
            __('Inventory', 'purplebox-storage'),
            'manage_options',
            'purplebox-units',
            [$this, 'render_units']
        );

        add_submenu_page(
            'purplebox-dashboard',
            __('Add Inventory', 'purplebox-storage'),
            __('Add Inventory', 'purplebox-storage'),
            'manage_options',
            'purplebox-unit-edit',
            [$this, 'render_unit_edit']
        );

        add_submenu_page(
            'purplebox-dashboard',
            __('Tenants', 'purplebox-storage'),
            __('Tenants', 'purplebox-storage'),
            'manage_options',
            'purplebox-tenants',
            [$this, 'render_tenants']
        );

        add_submenu_page(
            'purplebox-dashboard',
            __('Contracts', 'purplebox-storage'),
            __('Contracts', 'purplebox-storage'),
            'manage_options',
            'purplebox-contracts',
            [$this, 'render_contracts']
        );

        add_submenu_page(
            'purplebox-dashboard',
            __('New Contract', 'purplebox-storage'),
            __('New Contract', 'purplebox-storage'),
            'manage_options',
            'purplebox-contract-new',
            [$this, 'render_contract_new']
        );

        add_submenu_page(
            'purplebox-dashboard',
            __('Reports', 'purplebox-storage'),
            __('📊 Reports', 'purplebox-storage'),
            'manage_options',
            'purplebox-reports',
            [$this, 'render_reports']
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'purplebox') === false) {
            return;
        }

        wp_enqueue_style(
            'purplebox-admin',
            PURPLEBOX_PLUGIN_URL . 'assets/css/purplebox-admin.css',
            [],
            PURPLEBOX_VERSION
        );

        wp_enqueue_script(
            'purplebox-admin',
            PURPLEBOX_PLUGIN_URL . 'assets/js/purplebox-admin.js',
            ['jquery'],
            PURPLEBOX_VERSION,
            true
        );

        wp_localize_script('purplebox-admin', 'purplebox', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('purplebox_ajax'),
        ]);

        wp_enqueue_media();
    }

    public function handle_form_submissions() {
        if (!isset($_POST['purplebox_action'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        $action = sanitize_text_field($_POST['purplebox_action']);

        switch ($action) {
            case 'save_unit':
                require_once PURPLEBOX_PLUGIN_DIR . 'includes/controllers/class-purplebox-units.php';
                Purplebox_Units_Controller::handle_save();
                break;
            case 'save_tenant':
                require_once PURPLEBOX_PLUGIN_DIR . 'includes/controllers/class-purplebox-tenants.php';
                Purplebox_Tenants_Controller::handle_save();
                break;
            case 'save_contract':
                require_once PURPLEBOX_PLUGIN_DIR . 'includes/controllers/class-purplebox-contracts.php';
                Purplebox_Contracts_Controller::handle_save();
                break;
        }
    }

    public function render_dashboard() {
        require_once PURPLEBOX_PLUGIN_DIR . 'includes/controllers/class-purplebox-dashboard.php';
        Purplebox_Dashboard_Controller::render();
    }

    public function render_units() {
        require_once PURPLEBOX_PLUGIN_DIR . 'includes/controllers/class-purplebox-units.php';
        Purplebox_Units_Controller::render_list();
    }

    public function render_unit_edit() {
        require_once PURPLEBOX_PLUGIN_DIR . 'includes/controllers/class-purplebox-units.php';
        Purplebox_Units_Controller::render_edit();
    }

    public function render_tenants() {
        require_once PURPLEBOX_PLUGIN_DIR . 'includes/controllers/class-purplebox-tenants.php';
        Purplebox_Tenants_Controller::render_list();
    }

    public function render_contracts() {
        require_once PURPLEBOX_PLUGIN_DIR . 'includes/controllers/class-purplebox-contracts.php';
        Purplebox_Contracts_Controller::render_list();
    }

    public function render_contract_new() {
        require_once PURPLEBOX_PLUGIN_DIR . 'includes/controllers/class-purplebox-contracts.php';
        Purplebox_Contracts_Controller::render_wizard();
    }

    public function render_reports() {
        require_once PURPLEBOX_PLUGIN_DIR . 'includes/controllers/class-purplebox-reports.php';
        Purplebox_Reports_Controller::render();
    }

    public function ajax_search_tenants() {
        check_ajax_referer('purplebox_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $search = sanitize_text_field($_GET['q'] ?? '');
        $tenants = Purplebox_DB::get_tenants([
            'search'   => $search,
            'per_page' => 10,
            'status'   => 'active',
        ]);

        wp_send_json_success($tenants);
    }

    public function ajax_available_units() {
        check_ajax_referer('purplebox_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $units = Purplebox_DB::get_units([
            'status'   => 'available',
            'per_page' => 100,
        ]);

        wp_send_json_success($units);
    }
}
