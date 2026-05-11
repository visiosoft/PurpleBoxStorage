<?php
if (!defined('ABSPATH')) {
    exit;
}

class Purplebox_DB {

    private static function units_table() {
        global $wpdb;
        return $wpdb->prefix . 'purplebox_units';
    }

    private static function tenants_table() {
        global $wpdb;
        return $wpdb->prefix . 'purplebox_tenants';
    }

    private static function contracts_table() {
        global $wpdb;
        return $wpdb->prefix . 'purplebox_contracts';
    }

    // ─── Units (Individual units with number + price) ───

    public static function get_units($args = []) {
        global $wpdb;
        $table = self::units_table();

        $defaults = [
            'search'   => '',
            'floor'    => '',
            'size'     => '',
            'status'   => '',
            'orderby'  => 'unit_number',
            'order'    => 'ASC',
            'per_page' => 50,
            'page'     => 1,
        ];
        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $values = [];

        if ($args['floor']) {
            $where[] = 'floor = %s';
            $values[] = $args['floor'];
        }

        if ($args['size']) {
            $where[] = 'size_category = %s';
            $values[] = $args['size'];
        }

        if ($args['search']) {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(unit_number LIKE %s OR size_category LIKE %s)';
            $values[] = $like;
            $values[] = $like;
        }

        $allowed_orderby = ['unit_number', 'size_category', 'floor', 'price', 'created_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'unit_number';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $offset = ($args['page'] - 1) * $args['per_page'];
        $where_sql = implode(' AND ', $where);

        if (!empty($values)) {
            $sql = $wpdb->prepare(
                "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d",
                array_merge($values, [$args['per_page'], $offset])
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $args['per_page'],
                $offset
            );
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public static function count_units($args = []) {
        global $wpdb;
        $table = self::units_table();

        $where = ['1=1'];
        $values = [];

        if (!empty($args['floor'])) {
            $where[] = 'floor = %s';
            $values[] = $args['floor'];
        }

        if (!empty($args['size'])) {
            $where[] = 'size_category = %s';
            $values[] = $args['size'];
        }

        if (!empty($args['search'])) {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(unit_number LIKE %s OR size_category LIKE %s)';
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = implode(' AND ', $where);

        if (!empty($values)) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE $where_sql",
                $values
            ));
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where_sql");
    }

    public static function get_unit($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::units_table() . " WHERE id = %d", $id), ARRAY_A);
    }

    public static function save_unit($data) {
        global $wpdb;
        $table = self::units_table();

        // Compute discounted_price from discount_pct if provided
        $price        = floatval($data['price'] ?? 0);
        $discount_pct = isset($data['discount_pct']) && $data['discount_pct'] !== '' ? floatval($data['discount_pct']) : null;
        $disc_price   = ($discount_pct !== null && $price > 0)
                        ? round($price * (1 - $discount_pct / 100), 2)
                        : null;

        $fields = [
            'unit_number'      => sanitize_text_field($data['unit_number'] ?? ''),
            'display_name'     => !empty($data['display_name']) ? sanitize_text_field($data['display_name']) : null,
            'size_category'    => sanitize_text_field($data['size_category'] ?? ''),
            'custom_size'      => !empty($data['custom_size']) ? floatval($data['custom_size']) : null,
            'floor'            => sanitize_text_field($data['floor'] ?? 'Ground'),
            'price'            => $price,
            'discount_pct'     => $discount_pct,
            'discounted_price' => $disc_price,
            'quantity'         => max(1, absint($data['quantity'] ?? 1)),
            'facility'         => sanitize_text_field($data['facility'] ?? 'PurpleBox Al Quoz'),
            'features'         => !empty($data['features']) ? wp_json_encode($data['features']) : null,
            'notes'            => sanitize_textarea_field($data['notes'] ?? ''),
            'manual_status'    => !empty($data['manual_status']) ? sanitize_text_field($data['manual_status']) : null,
        ];

        if (!empty($data['id'])) {
            $wpdb->update($table, $fields, ['id' => absint($data['id'])]);
            return absint($data['id']);
        }

        $wpdb->insert($table, $fields);
        return $wpdb->insert_id;
    }

    public static function delete_unit($id) {
        global $wpdb;
        return $wpdb->delete(self::units_table(), ['id' => absint($id)]);
    }

    /**
     * Check if a unit is currently rented (part of any active contract).
     */
    public static function is_unit_rented($unit_id) {
        $unit = self::get_unit($unit_id);
        if (!empty($unit['manual_status']) && $unit['manual_status'] === 'rented') {
            return true;
        }
        $quantity = max(1, (int) ($unit['quantity'] ?? 1));
        $counts   = self::get_rented_count_per_unit();
        return ($counts[(int) $unit_id] ?? 0) >= $quantity;
    }

    /**
     * How many slots are still available for a unit.
     */
    public static function get_unit_available_slots($unit_id, $rented_counts = null) {
        $unit = self::get_unit($unit_id);
        if (!empty($unit['manual_status']) && $unit['manual_status'] === 'rented') {
            return 0;
        }
        $quantity = max(1, (int) ($unit['quantity'] ?? 1));
        if ($rented_counts === null) {
            $rented_counts = self::get_rented_count_per_unit();
        }
        return max(0, $quantity - ($rented_counts[(int) $unit_id] ?? 0));
    }

    /**
     * Get all available (not rented) units.
     */
    public static function get_available_units() {
        $all_units     = self::get_units(['per_page' => 1000]);
        $rented_counts = self::get_rented_count_per_unit();
        $available     = [];
        foreach ($all_units as $unit) {
            if (!empty($unit['manual_status']) && $unit['manual_status'] === 'rented') {
                continue;
            }
            $qty    = max(1, (int) ($unit['quantity'] ?? 1));
            $rented = $rented_counts[(int) $unit['id']] ?? 0;
            if ($rented < $qty) {
                $unit['available_slots'] = $qty - $rented;
                $available[] = $unit;
            }
        }
        return $available;
    }

    /**
     * Returns [unit_id => rented_count] for all active contracts.
     */
    public static function get_rented_count_per_unit() {
        global $wpdb;
        $table    = self::contracts_table();
        $rows     = $wpdb->get_col("SELECT unit_ids FROM $table WHERE status = 'active'") ?? [];
        $counts   = [];
        foreach ($rows as $json) {
            $ids = json_decode($json, true);
            if (!is_array($ids)) continue;
            foreach ($ids as $id) {
                $id = (int) $id;
                $counts[$id] = ($counts[$id] ?? 0) + 1;
            }
        }
        return $counts;
    }

    /**
     * Get rented unit IDs from all active contracts (unique list).
     */
    public static function get_all_rented_unit_ids() {
        return array_keys(self::get_rented_count_per_unit());
    }

    // ─── Tenants ───

    public static function generate_client_id() {
        global $wpdb;
        $table = self::tenants_table();
        $last = $wpdb->get_var("SELECT client_id FROM $table ORDER BY id DESC LIMIT 1");

        if ($last && preg_match('/PB-(\d+)/', $last, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1001;
        }

        return 'PB-' . $next;
    }

    public static function get_tenants($args = []) {
        global $wpdb;
        $table = self::tenants_table();

        $defaults = [
            'search'   => '',
            'status'   => '',
            'type'     => '',
            'orderby'  => 'full_name',
            'order'    => 'ASC',
            'per_page' => 20,
            'page'     => 1,
        ];
        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $values = [];

        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ($args['type']) {
            $where[] = 'tenant_type = %s';
            $values[] = $args['type'];
        }

        if ($args['search']) {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(full_name LIKE %s OR phones LIKE %s OR email LIKE %s OR client_id LIKE %s)';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $allowed_orderby = ['full_name', 'client_id', 'tenant_type', 'status', 'created_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'full_name';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $offset = ($args['page'] - 1) * $args['per_page'];
        $where_sql = implode(' AND ', $where);

        if (!empty($values)) {
            $sql = $wpdb->prepare(
                "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d",
                array_merge($values, [$args['per_page'], $offset])
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $args['per_page'],
                $offset
            );
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public static function count_tenants($args = []) {
        global $wpdb;
        $table = self::tenants_table();

        $where = ['1=1'];
        $values = [];

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['type'])) {
            $where[] = 'tenant_type = %s';
            $values[] = $args['type'];
        }

        if (!empty($args['search'])) {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(full_name LIKE %s OR phones LIKE %s OR email LIKE %s OR client_id LIKE %s)';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = implode(' AND ', $where);

        if (!empty($values)) {
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE $where_sql", $values));
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where_sql");
    }

    public static function get_tenant($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::tenants_table() . " WHERE id = %d", $id), ARRAY_A);
    }

    public static function save_tenant($data) {
        global $wpdb;
        $table = self::tenants_table();

        $phones = $data['phones'] ?? [];
        if (is_array($phones)) {
            $phones = array_filter(array_map('sanitize_text_field', $phones));
            $phones = wp_json_encode(array_values($phones));
        } else {
            $phones = wp_json_encode([sanitize_text_field($phones)]);
        }

        // Build access_persons JSON
        $access_names     = array_values($data['access_name'] ?? []);
        $access_phones    = array_values($data['access_phone'] ?? []);
        $access_relations = array_values($data['access_relation'] ?? []);
        $access_id_types  = array_values($data['access_id_type'] ?? []);
        $access_id_nums   = array_values($data['access_id_number'] ?? []);
        $access_persons   = [];
        foreach ($access_names as $i => $name) {
            $name = sanitize_text_field($name);
            if ($name === '') continue;
            $access_persons[] = [
                'name'      => $name,
                'phone'     => sanitize_text_field($access_phones[$i] ?? ''),
                'relation'  => sanitize_text_field($access_relations[$i] ?? ''),
                'id_type'   => sanitize_text_field($access_id_types[$i] ?? ''),
                'id_number' => sanitize_text_field($access_id_nums[$i] ?? ''),
            ];
        }

        $fields = [
            'full_name'        => sanitize_text_field($data['full_name'] ?? ''),
            'tenant_type'      => sanitize_text_field($data['tenant_type'] ?? 'individual'),
            'phones'           => $phones,
            'email'            => sanitize_email($data['email'] ?? ''),
            'emirates_id'      => sanitize_text_field($data['emirates_id'] ?? ''),
            'eid_expiry'       => !empty($data['eid_expiry']) ? sanitize_text_field($data['eid_expiry']) : null,
            'passport_number'  => sanitize_text_field($data['passport_number'] ?? ''),
            'passport_expiry'  => !empty($data['passport_expiry']) ? sanitize_text_field($data['passport_expiry']) : null,
            'nationality'      => sanitize_text_field($data['nationality'] ?? ''),
            'address'          => sanitize_textarea_field($data['address'] ?? ''),
            'access_persons'   => !empty($access_persons) ? wp_json_encode($access_persons) : null,
            'status'           => sanitize_text_field($data['status'] ?? 'active'),
        ];

        if (!empty($data['id'])) {
            $wpdb->update($table, $fields, ['id' => absint($data['id'])]);
            return absint($data['id']);
        }

        $fields['client_id'] = !empty($data['client_id']) ? sanitize_text_field($data['client_id']) : self::generate_client_id();
        $wpdb->insert($table, $fields);
        return $wpdb->insert_id;
    }

    public static function delete_tenant($id) {
        global $wpdb;
        return $wpdb->delete(self::tenants_table(), ['id' => absint($id)]);
    }

    // ─── Contracts ───

    public static function get_contracts($args = []) {
        global $wpdb;
        $ct = self::contracts_table();
        $tt = self::tenants_table();

        $defaults = [
            'status'   => '',
            'per_page' => 20,
            'page'     => 1,
        ];
        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $values = [];

        if ($args['status']) {
            $where[] = "c.status = %s";
            $values[] = $args['status'];
        }

        $offset = ($args['page'] - 1) * $args['per_page'];
        $where_sql = implode(' AND ', $where);

        if (!empty($values)) {
            $sql = $wpdb->prepare(
                "SELECT c.*, t.full_name as tenant_name, t.client_id as tenant_client_id
                 FROM $ct c
                 LEFT JOIN $tt t ON c.tenant_id = t.id
                 WHERE $where_sql
                 ORDER BY c.created_at DESC
                 LIMIT %d OFFSET %d",
                array_merge($values, [$args['per_page'], $offset])
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT c.*, t.full_name as tenant_name, t.client_id as tenant_client_id
                 FROM $ct c
                 LEFT JOIN $tt t ON c.tenant_id = t.id
                 WHERE $where_sql
                 ORDER BY c.created_at DESC
                 LIMIT %d OFFSET %d",
                $args['per_page'],
                $offset
            );
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public static function count_contracts($args = []) {
        global $wpdb;
        $table = self::contracts_table();

        $where = ['1=1'];
        $values = [];

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $where_sql = implode(' AND ', $where);

        if (!empty($values)) {
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE $where_sql", $values));
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where_sql");
    }

    public static function get_contract($id) {
        global $wpdb;
        $ct = self::contracts_table();
        $tt = self::tenants_table();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, t.full_name as tenant_name, t.client_id as tenant_client_id
             FROM $ct c
             LEFT JOIN $tt t ON c.tenant_id = t.id
             WHERE c.id = %d",
            $id
        ), ARRAY_A);
    }

    public static function save_contract($data) {
        global $wpdb;
        $table = self::contracts_table();

        $unit_ids = $data['unit_ids'] ?? [];
        if (!is_array($unit_ids)) {
            $unit_ids = [$unit_ids];
        }
        $unit_ids = array_map('absint', array_filter($unit_ids));

        // Check availability for new contracts
        if (empty($data['id'])) {
            $rented = self::get_all_rented_unit_ids();
            foreach ($unit_ids as $uid) {
                $u = self::get_unit($uid);
                $is_manual = !empty($u['manual_status']) && $u['manual_status'] === 'rented';
                if ($is_manual || in_array($uid, $rented)) {
                    return new \WP_Error('no_availability', __('One or more selected units are already rented.', 'purplebox-storage'));
                }
            }
        }

        $fields = [
            'tenant_id'          => absint($data['tenant_id']),
            'unit_ids'           => wp_json_encode($unit_ids),
            'move_in_date'       => sanitize_text_field($data['move_in_date']),
            'move_out_date'      => !empty($data['move_out_date']) ? sanitize_text_field($data['move_out_date']) : null,
            'duration_weeks'     => !empty($data['duration_weeks']) ? absint($data['duration_weeks']) : null,
            'first_payment_date' => !empty($data['first_payment_date']) ? sanitize_text_field($data['first_payment_date']) : null,
            'payment_method'     => sanitize_text_field($data['payment_method'] ?? 'Cash'),
            'next_payment_date'  => !empty($data['next_payment_date']) ? sanitize_text_field($data['next_payment_date']) : null,
            'auto_renew'         => !empty($data['auto_renew']) ? 1 : 0,
            'signed_pdf_path'    => !empty($data['signed_pdf_path']) ? sanitize_text_field($data['signed_pdf_path']) : null,
            'notes'              => !empty($data['notes']) ? sanitize_textarea_field($data['notes']) : null,
            'status'             => sanitize_text_field($data['status'] ?? 'active'),
        ];

        if (!empty($data['id'])) {
            $wpdb->update($table, $fields, ['id' => absint($data['id'])]);
            return absint($data['id']);
        }

        $wpdb->insert($table, $fields);
        return $wpdb->insert_id;
    }

    public static function delete_contract($id) {
        global $wpdb;
        return $wpdb->delete(self::contracts_table(), ['id' => absint($id)]);
    }

    public static function end_contract($id) {
        global $wpdb;
        $wpdb->update(self::contracts_table(), ['status' => 'ended'], ['id' => absint($id)]);
        return true;
    }

    /**
     * Remove a single unit from a multi-unit contract.
     * The unit becomes available again. If only one unit remains, the contract stays active.
     * Returns WP_Error if contract not found or unit not part of the contract.
     */
    public static function remove_unit_from_contract($contract_id, $unit_id) {
        global $wpdb;
        $contract_id = absint($contract_id);
        $unit_id     = absint($unit_id);

        $contract = self::get_contract($contract_id);
        if (!$contract) {
            return new \WP_Error('not_found', __('Contract not found.', 'purplebox-storage'));
        }

        $unit_ids = json_decode($contract['unit_ids'], true);
        if (!is_array($unit_ids)) {
            $unit_ids = [];
        }
        $unit_ids = array_map('intval', $unit_ids);

        if (!in_array($unit_id, $unit_ids, true)) {
            return new \WP_Error('not_in_contract', __('Unit is not part of this contract.', 'purplebox-storage'));
        }

        $unit_ids = array_values(array_filter($unit_ids, fn($id) => $id !== $unit_id));

        $wpdb->update(
            self::contracts_table(),
            ['unit_ids' => wp_json_encode($unit_ids)],
            ['id' => $contract_id]
        );

        return true;
    }

    // ─── Dashboard Stats ───

    public static function get_dashboard_stats() {
        global $wpdb;
        $units_table = self::units_table();

        // Total stock = sum of all unit quantities
        $total_units = (int) $wpdb->get_var("SELECT COALESCE(SUM(quantity), 0) FROM $units_table");

        // Rented slots = count of how many times each unit appears in active contracts
        $rented_counts = self::get_rented_count_per_unit();

        // Total rented slots (capped at each unit's quantity)
        $all_units_qty = $wpdb->get_results("SELECT id, quantity, manual_status FROM $units_table", ARRAY_A) ?? [];
        $total_rented = 0;
        foreach ($all_units_qty as $u) {
            $qty = max(1, (int) $u['quantity']);
            if (!empty($u['manual_status']) && $u['manual_status'] === 'rented') {
                $total_rented += $qty;
            } else {
                $rented = min($qty, $rented_counts[(int) $u['id']] ?? 0);
                $total_rented += $rented;
            }
        }

        $total_available = max(0, $total_units - $total_rented);
        $occupancy = $total_units > 0 ? round(($total_rented / $total_units) * 100, 1) : 0;

        $contracts_table = self::contracts_table();
        $avg_weeks = (float) $wpdb->get_var(
            "SELECT AVG(duration_weeks) FROM $contracts_table WHERE status = 'active' AND duration_weeks IS NOT NULL"
        );
        $avg_months = $avg_weeks > 0 ? round($avg_weeks / 4.33, 1) : 0;

        $active_tenants = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT tenant_id) FROM $contracts_table WHERE status = 'active'"
        );

        return [
            'total'               => $total_units,
            'available'           => $total_available,
            'rented'              => $total_rented,
            'occupancy'           => $occupancy,
            'avg_contract_months' => $avg_months,
            'active_tenants'      => $active_tenants,
        ];
    }

    /**
     * Get units becoming available: now, this week, this month.
     * Returns units whose contracts end within the specified period.
     */
    public static function get_upcoming_availability() {
        global $wpdb;
        $ct = self::contracts_table();
        $ut = self::units_table();

        $today = current_time('Y-m-d');
        $end_of_week = date('Y-m-d', strtotime('+7 days', strtotime($today)));
        $end_of_month = date('Y-m-t', strtotime($today));

        // Get currently available units
        $available_now = self::get_available_units();

        // Get contracts ending this week
        $ending_this_week = $wpdb->get_results($wpdb->prepare(
            "SELECT unit_ids, move_out_date FROM $ct WHERE status = 'active' AND move_out_date BETWEEN %s AND %s",
            $today, $end_of_week
        ), ARRAY_A);

        // Get contracts ending this month
        $ending_this_month = $wpdb->get_results($wpdb->prepare(
            "SELECT unit_ids, move_out_date FROM $ct WHERE status = 'active' AND move_out_date BETWEEN %s AND %s",
            $today, $end_of_month
        ), ARRAY_A);

        // Resolve unit details
        $week_units = [];
        foreach ($ending_this_week as $c) {
            $ids = json_decode($c['unit_ids'], true);
            if (is_array($ids)) {
                foreach ($ids as $uid) {
                    $unit = self::get_unit($uid);
                    if ($unit) {
                        $unit['available_date'] = $c['move_out_date'];
                        $week_units[] = $unit;
                    }
                }
            }
        }

        $month_units = [];
        foreach ($ending_this_month as $c) {
            $ids = json_decode($c['unit_ids'], true);
            if (is_array($ids)) {
                foreach ($ids as $uid) {
                    $unit = self::get_unit($uid);
                    if ($unit) {
                        $unit['available_date'] = $c['move_out_date'];
                        $month_units[] = $unit;
                    }
                }
            }
        }

        return [
            'now'   => $available_now,
            'week'  => $week_units,
            'month' => $month_units,
        ];
    }

    public static function get_availability_by_size() {
        global $wpdb;
        $table = self::units_table();

        $all_units     = $wpdb->get_results(
            "SELECT id, unit_number, display_name, size_category, floor, COALESCE(quantity,1) AS quantity, manual_status FROM $table ORDER BY unit_number ASC",
            ARRAY_A
        ) ?? [];
        $rented_counts = self::get_rented_count_per_unit();

        $groups = [];
        foreach ($all_units as $unit) {
            $size   = $unit['size_category'];
            $qty    = max(1, (int) $unit['quantity']);
            if (!empty($unit['manual_status']) && $unit['manual_status'] === 'rented') {
                $rented = $qty;
            } else {
                $rented = min($qty, $rented_counts[(int) $unit['id']] ?? 0);
            }
            $avail  = $qty - $rented;

            if (!isset($groups[$size])) {
                $groups[$size] = ['size_category' => $size, 'total' => 0, 'available' => 0, 'units' => []];
            }

            $groups[$size]['total']     += $qty;
            $groups[$size]['available'] += $avail;

            $groups[$size]['units'][] = [
                'unit_number'  => $unit['unit_number'],
                'display_name' => $unit['display_name'],
                'floor'        => $unit['floor'],
                'quantity'     => $qty,
                'available'    => $avail,
                'is_rented'    => ($avail === 0),
            ];
        }

        ksort($groups);
        return array_values($groups);
    }

    public static function get_recent_activity($limit = 10) {
        global $wpdb;
        $ct = self::contracts_table();
        $tt = self::tenants_table();

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.unit_ids, c.status, c.created_at, t.full_name as tenant_name
             FROM $ct c
             LEFT JOIN $tt t ON c.tenant_id = t.id
             ORDER BY c.created_at DESC
             LIMIT %d",
            $limit
        ), ARRAY_A);

        foreach ($results as &$row) {
            $details = self::get_unit_details_from_ids($row['unit_ids']);
            $labels  = [];
            foreach ($details as $u) {
                $label = $u['unit_number'];
                if (!empty($u['display_name'])) {
                    $label .= ' — ' . $u['display_name'];
                }
                $labels[] = $label;
            }
            $row['unit_labels']  = $labels;
            $row['unit_numbers'] = implode(', ', array_column($details, 'unit_number'));
        }

        return $results;
    }

    public static function get_active_units_for_tenant($tenant_id) {
        global $wpdb;
        $ct = self::contracts_table();
        $ut = self::units_table();

        $contracts = $wpdb->get_results($wpdb->prepare(
            "SELECT unit_ids FROM $ct WHERE tenant_id = %d AND status = 'active'",
            $tenant_id
        ), ARRAY_A);

        $units = [];
        foreach ($contracts as $c) {
            $ids = json_decode($c['unit_ids'], true);
            if (is_array($ids)) {
                foreach ($ids as $uid) {
                    $unit = self::get_unit($uid);
                    if ($unit) {
                        $units[] = $unit;
                    }
                }
            }
        }
        return $units;
    }

    /**
     * Get full unit rows (id, unit_number, display_name, size_category, floor) from JSON unit_ids.
     */
    public static function get_unit_details_from_ids($unit_ids_json) {
        $ids = json_decode($unit_ids_json, true);
        if (!is_array($ids) || empty($ids)) {
            return [];
        }

        global $wpdb;
        $table = self::units_table();
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, unit_number, display_name, size_category, floor, price, discounted_price FROM $table WHERE id IN ($placeholders)",
                $ids
            ),
            ARRAY_A
        );
    }

    /**
     * Get available/total counts for every unit_group.
     * Returns [ 'groupName' => ['total' => N, 'available' => M], ... ]
     */
    public static function get_group_stock() {
        global $wpdb;
        $table = self::units_table();

        $units = $wpdb->get_results(
            "SELECT id, unit_group, manual_status FROM $table WHERE unit_group IS NOT NULL AND unit_group != ''",
            ARRAY_A
        );
        if (empty($units)) return [];

        $rented_ids = self::get_all_rented_unit_ids();
        $stocks = [];

        foreach ($units as $u) {
            $g = $u['unit_group'];
            if (!isset($stocks[$g])) {
                $stocks[$g] = ['total' => 0, 'available' => 0];
            }
            $stocks[$g]['total']++;
            $is_manual = !empty($u['manual_status']) && $u['manual_status'] === 'rented';
            if (!$is_manual && !in_array((int) $u['id'], $rented_ids)) {
                $stocks[$g]['available']++;
            }
        }
        return $stocks;
    }

    /**
     * Get active contracts expiring within $days days.
     */
    public static function get_expiring_contracts($days = 15) {
        global $wpdb;
        $ct = self::contracts_table();
        $tt = self::tenants_table();

        $today    = current_time('Y-m-d');
        $deadline = date('Y-m-d', strtotime("+{$days} days", strtotime($today)));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.unit_ids, c.move_out_date,
                    t.full_name AS tenant_name, t.id AS tenant_id,
                    DATEDIFF(c.move_out_date, %s) AS days_left
             FROM {$ct} c
             JOIN {$tt} t ON t.id = c.tenant_id
             WHERE c.status = 'active'
               AND c.move_out_date IS NOT NULL
               AND c.move_out_date BETWEEN %s AND %s
             ORDER BY c.move_out_date ASC",
            $today, $today, $deadline
        ), ARRAY_A);
    }

    /**
     * Get tenants whose Emirates ID or Passport expires within $days days.
     * Returns array with keys: id, client_id, full_name, phones,
     *   eid_expiry, eid_days_left, passport_expiry, passport_days_left.
     */
    public static function get_expiring_documents($days = 60) {
        global $wpdb;
        $tt       = self::tenants_table();
        $today    = current_time('Y-m-d');
        $deadline = date('Y-m-d', strtotime("+{$days} days", strtotime($today)));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, client_id, full_name, phones,
                    eid_expiry, passport_expiry,
                    DATEDIFF(eid_expiry, %s)        AS eid_days_left,
                    DATEDIFF(passport_expiry, %s)   AS passport_days_left
             FROM {$tt}
             WHERE status = 'active'
               AND (
                     (eid_expiry      IS NOT NULL AND eid_expiry      BETWEEN %s AND %s)
                  OR (passport_expiry IS NOT NULL AND passport_expiry BETWEEN %s AND %s)
               )
             ORDER BY LEAST(
                 COALESCE(eid_expiry, '9999-12-31'),
                 COALESCE(passport_expiry, '9999-12-31')
             ) ASC",
            $today, $today,
            $today, $deadline,
            $today, $deadline
        ), ARRAY_A) ?? [];
    }

    /**
     * Get unit numbers string from JSON unit_ids.
     */
    public static function get_unit_numbers_from_ids($unit_ids_json) {
        $ids = json_decode($unit_ids_json, true);
        if (!is_array($ids) || empty($ids)) {
            return '—';
        }

        global $wpdb;
        $table = self::units_table();
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT unit_number FROM $table WHERE id IN ($placeholders)",
            $ids
        ));

        return implode(', ', $results);
    }

    // ─── Reports ────────────────────────────────────────────────────────────────

    /** Report 1 — Unit Inventory */
    public static function get_report_inventory($args = []) {
        global $wpdb;
        $table  = self::units_table();
        $floor  = sanitize_text_field($args['floor']  ?? '');
        $size   = sanitize_text_field($args['size']   ?? '');
        $status = sanitize_text_field($args['status'] ?? ''); // available|rented|''

        $where = ['1=1'];
        $vals  = [];
        if ($floor) { $where[] = 'floor = %s';         $vals[] = $floor; }
        if ($size)  { $where[] = 'size_category = %s'; $vals[] = $size; }

        $wsql  = implode(' AND ', $where);
        $units = !empty($vals)
            ? ($wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE $wsql ORDER BY unit_number ASC", $vals), ARRAY_A) ?? [])
            : ($wpdb->get_results("SELECT * FROM $table WHERE $wsql ORDER BY unit_number ASC", ARRAY_A) ?? []);

        $rented_counts = self::get_rented_count_per_unit();
        $result = [];
        foreach ($units as $unit) {
            $qty = max(1, (int)($unit['quantity'] ?? 1));
            if (!empty($unit['manual_status']) && $unit['manual_status'] === 'rented') {
                $rented = $qty;
            } else {
                $rented = min($qty, $rented_counts[(int)$unit['id']] ?? 0);
            }
            $avail  = $qty - $rented;
            $ustatus = ($avail === 0) ? 'rented' : 'available';
            if ($status && $status !== $ustatus) continue;
            $unit['rented_count'] = $rented;
            $unit['avail_count']  = $avail;
            $unit['unit_status']  = $ustatus;
            $unit['is_manual']    = !empty($unit['manual_status']) && $unit['manual_status'] === 'rented';
            $unit['features_arr'] = !empty($unit['features']) ? (json_decode($unit['features'], true) ?? []) : [];
            $result[] = $unit;
        }
        return $result;
    }

    /** Report 2 — Contracts (sorted by expiry ASC) */
    public static function get_report_contracts($args = []) {
        global $wpdb;
        $ct     = self::contracts_table();
        $tt     = self::tenants_table();
        $status = sanitize_text_field($args['status']   ?? 'active');
        $expiring = (int)($args['expiring'] ?? 0);
        $today  = current_time('Y-m-d');

        $where = ['1=1'];
        $vals  = [];
        if ($status && $status !== 'all') { $where[] = 'c.status = %s'; $vals[] = $status; }
        if ($expiring > 0) {
            $deadline = date('Y-m-d', strtotime("+{$expiring} days", strtotime($today)));
            $where[] = 'c.move_out_date IS NOT NULL';
            $where[] = 'c.move_out_date BETWEEN %s AND %s';
            $vals[] = $today; $vals[] = $deadline;
        }

        $wsql = implode(' AND ', $where);
        $sql  = "SELECT c.*, t.full_name AS tenant_name, t.client_id AS tenant_client_id,
                        t.phones, t.email, t.emirates_id, t.nationality,
                        DATEDIFF(c.move_out_date, %s) AS days_left
                 FROM $ct c LEFT JOIN $tt t ON c.tenant_id = t.id
                 WHERE $wsql
                 ORDER BY CASE WHEN c.move_out_date IS NULL THEN 1 ELSE 0 END, c.move_out_date ASC";

        $rows = $wpdb->get_results(
            $wpdb->prepare($sql, array_merge([$today], $vals)), ARRAY_A
        ) ?? [];

        foreach ($rows as &$c) {
            $details = self::get_unit_details_from_ids($c['unit_ids'] ?? '[]');
            $labels  = [];
            foreach ($details as $u) {
                $lbl = $u['unit_number'];
                if (!empty($u['display_name'])) $lbl .= ' (' . $u['display_name'] . ')';
                $labels[] = $lbl;
            }
            $c['unit_labels'] = implode(', ', $labels);
        }
        return $rows;
    }

    /** Report 3 — Tenant Directory with contract history */
    public static function get_report_tenants($args = []) {
        global $wpdb;
        $tt     = self::tenants_table();
        $ct     = self::contracts_table();
        $status = sanitize_text_field($args['status'] ?? 'all');
        $today  = current_time('Y-m-d');

        $where = ['1=1'];
        $vals  = [];
        if ($status && $status !== 'all') { $where[] = 'status = %s'; $vals[] = $status; }
        $wsql    = implode(' AND ', $where);
        $tenants = !empty($vals)
            ? ($wpdb->get_results($wpdb->prepare("SELECT * FROM $tt WHERE $wsql ORDER BY full_name ASC", $vals), ARRAY_A) ?? [])
            : ($wpdb->get_results("SELECT * FROM $tt WHERE $wsql ORDER BY full_name ASC", ARRAY_A) ?? []);

        foreach ($tenants as &$tenant) {
            $contracts = $wpdb->get_results($wpdb->prepare(
                "SELECT id, unit_ids, move_in_date, move_out_date, status,
                        DATEDIFF(move_out_date, %s) AS days_left
                 FROM $ct WHERE tenant_id = %d
                 ORDER BY FIELD(status,'active','ended') ASC, move_out_date ASC",
                $today, $tenant['id']
            ), ARRAY_A) ?? [];

            foreach ($contracts as &$con) {
                $details = self::get_unit_details_from_ids($con['unit_ids'] ?? '[]');
                $labels  = [];
                foreach ($details as $u) {
                    $lbl = $u['unit_number'];
                    if (!empty($u['display_name'])) $lbl .= ' (' . $u['display_name'] . ')';
                    $labels[] = $lbl;
                }
                $con['unit_labels'] = implode(', ', $labels);
            }
            $tenant['contracts']        = $contracts;
            $tenant['active_contracts'] = array_values(array_filter($contracts, fn($c) => $c['status'] === 'active'));
        }
        return $tenants;
    }

    /** Report 4 — Availability Forecast */
    public static function get_report_forecast() {
        global $wpdb;
        $ct    = self::contracts_table();
        $tt    = self::tenants_table();
        $today = current_time('Y-m-d');

        $week_end  = date('Y-m-d', strtotime('+7 days',  strtotime($today)));
        $month_end = date('Y-m-d', strtotime('+30 days', strtotime($today)));

        $week_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.unit_ids, c.move_out_date,
                    DATEDIFF(c.move_out_date, %s) AS days_left,
                    t.full_name AS tenant_name, t.phones
             FROM $ct c LEFT JOIN $tt t ON c.tenant_id = t.id
             WHERE c.status = 'active' AND c.move_out_date BETWEEN %s AND %s
             ORDER BY c.move_out_date ASC",
            $today, $today, $week_end
        ), ARRAY_A) ?? [];

        $month_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.unit_ids, c.move_out_date,
                    DATEDIFF(c.move_out_date, %s) AS days_left,
                    t.full_name AS tenant_name, t.phones
             FROM $ct c LEFT JOIN $tt t ON c.tenant_id = t.id
             WHERE c.status = 'active' AND c.move_out_date > %s AND c.move_out_date <= %s
             ORDER BY c.move_out_date ASC",
            $today, $week_end, $month_end
        ), ARRAY_A) ?? [];

        $resolve = function (&$rows) {
            foreach ($rows as &$r) {
                $details = self::get_unit_details_from_ids($r['unit_ids'] ?? '[]');
                $labels  = [];
                foreach ($details as $u) {
                    $lbl = $u['unit_number'];
                    if (!empty($u['display_name'])) $lbl .= ' (' . $u['display_name'] . ')';
                    $labels[] = $lbl;
                }
                $r['unit_labels'] = implode(', ', $labels);
            }
        };
        $resolve($week_rows);
        $resolve($month_rows);

        return [
            'available' => self::get_available_units(),
            'week'      => $week_rows,
            'month'     => $month_rows,
            'today'     => $today,
        ];
    }

    /** Report 5 — Occupancy Summary */
    public static function get_report_occupancy() {
        global $wpdb;
        $ut    = self::units_table();
        $ct    = self::contracts_table();
        $today = current_time('Y-m-d');

        $all_units     = $wpdb->get_results("SELECT id, unit_number, size_category, floor, quantity, manual_status FROM $ut ORDER BY unit_number ASC", ARRAY_A) ?? [];
        $rented_counts = self::get_rented_count_per_unit();

        $by_size  = [];
        $by_floor = [];
        $totals   = ['total' => 0, 'rented' => 0, 'available' => 0];

        foreach ($all_units as $unit) {
            $qty = max(1, (int)($unit['quantity'] ?? 1));
            if (!empty($unit['manual_status']) && $unit['manual_status'] === 'rented') {
                $rented = $qty;
            } else {
                $rented = min($qty, $rented_counts[(int)$unit['id']] ?? 0);
            }
            $avail  = $qty - $rented;
            $size   = $unit['size_category'];
            $floor  = $unit['floor'];

            if (!isset($by_size[$size]))  $by_size[$size]  = ['label' => $size,  'total' => 0, 'rented' => 0, 'available' => 0];
            if (!isset($by_floor[$floor])) $by_floor[$floor] = ['label' => $floor, 'total' => 0, 'rented' => 0, 'available' => 0];

            $by_size[$size]['total']      += $qty;  $by_size[$size]['rented']      += $rented;  $by_size[$size]['available']  += $avail;
            $by_floor[$floor]['total']    += $qty;  $by_floor[$floor]['rented']    += $rented;  $by_floor[$floor]['available'] += $avail;
            $totals['total'] += $qty; $totals['rented'] += $rented; $totals['available'] += $avail;
        }

        $pct = fn($r, $t) => $t > 0 ? round(($r / $t) * 100, 1) : 0;
        $totals['occupancy'] = $pct($totals['rented'], $totals['total']);
        foreach ($by_size  as &$r) $r['occupancy'] = $pct($r['rented'], $r['total']);
        foreach ($by_floor as &$r) $r['occupancy'] = $pct($r['rented'], $r['total']);
        ksort($by_size); ksort($by_floor);

        $deadline      = date('Y-m-d', strtotime('+15 days', strtotime($today)));
        $expiring_soon = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $ct WHERE status = 'active' AND move_out_date BETWEEN %s AND %s",
            $today, $deadline
        ));
        $active_tenants = (int)$wpdb->get_var("SELECT COUNT(DISTINCT tenant_id) FROM $ct WHERE status = 'active'");

        return [
            'totals'         => $totals,
            'by_size'        => array_values($by_size),
            'by_floor'       => array_values($by_floor),
            'expiring_soon'  => $expiring_soon,
            'active_tenants' => $active_tenants,
            'generated_at'   => current_time('d/m/Y H:i'),
        ];
    }
}
