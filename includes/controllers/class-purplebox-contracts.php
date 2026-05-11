<?php
if (!defined('ABSPATH')) {
    exit;
}

class Purplebox_Contracts_Controller {

    public static function render_list() {
        if (!current_user_can('manage_purplebox')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        if (isset($_GET['action']) && $_GET['action'] === 'cancel_unit' && isset($_GET['contract_id'], $_GET['unit_id'])) {
            $contract_id = absint($_GET['contract_id']);
            $unit_id     = absint($_GET['unit_id']);
            if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'purplebox_cancel_unit_' . $contract_id . '_' . $unit_id)) {
                $result = Purplebox_DB::remove_unit_from_contract($contract_id, $unit_id);
                if (is_wp_error($result)) {
                    wp_redirect(admin_url('admin.php?page=purplebox-contracts&action=view&contract_id=' . $contract_id . '&cancel_error=1'));
                } else {
                    wp_redirect(admin_url('admin.php?page=purplebox-contracts&action=view&contract_id=' . $contract_id . '&unit_cancelled=1'));
                }
                exit;
            }
        }

        if (isset($_GET['action']) && $_GET['action'] === 'end' && isset($_GET['contract_id'])) {
            $contract_id = absint($_GET['contract_id']);
            if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'purplebox_end_contract_' . $contract_id)) {
                Purplebox_DB::end_contract($contract_id);
                wp_redirect(admin_url('admin.php?page=purplebox-contracts&ended=1'));
                exit;
            }
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['contract_id'])) {
            $contract_id = absint($_GET['contract_id']);
            if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'purplebox_delete_contract_' . $contract_id)) {
                Purplebox_DB::delete_contract($contract_id);
                wp_redirect(admin_url('admin.php?page=purplebox-contracts&deleted=1'));
                exit;
            }
        }

        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['contract_id'])) {
            self::render_detail();
            return;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['contract_id'])) {
            self::render_edit();
            return;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'agreement' && isset($_GET['contract_id'])) {
            self::render_agreement();
            return;
        }

        require_once PURPLEBOX_PLUGIN_DIR . 'includes/tables/class-purplebox-contracts-table.php';
        $table = new Purplebox_Contracts_Table();
        $table->prepare_items();

        include PURPLEBOX_PLUGIN_DIR . 'views/contracts-list.php';
    }

    public static function render_detail() {
        $contract_id = absint($_GET['contract_id']);
        $contract = Purplebox_DB::get_contract($contract_id);

        if (!$contract) {
            wp_die(__('Contract not found.', 'purplebox-storage'));
        }

        // Resolve unit details for display
        $contract['unit_details']  = Purplebox_DB::get_unit_details_from_ids($contract['unit_ids'] ?? '[]');
        $contract['unit_numbers']  = implode(', ', array_column($contract['unit_details'], 'unit_number'));

        // Load full tenant details for the detail page
        $tenant = Purplebox_DB::get_tenant($contract['tenant_id']);

        include PURPLEBOX_PLUGIN_DIR . 'views/contract-detail.php';
    }

    public static function render_wizard() {
        if (!current_user_can('manage_purplebox')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        $preselected_tenant_id  = absint($_GET['tenant_id'] ?? 0);
        $preselected_tenant     = $preselected_tenant_id ? Purplebox_DB::get_tenant($preselected_tenant_id) : null;
        $preselected_unit_ids   = [];

        // Renew-from support: pre-fill tenant + units from an existing contract
        $renew_from = absint($_GET['renew_from'] ?? 0);
        if ($renew_from) {
            $renew_contract = Purplebox_DB::get_contract($renew_from);
            if ($renew_contract) {
                if (!$preselected_tenant_id) {
                    $preselected_tenant_id = absint($renew_contract['tenant_id']);
                    $preselected_tenant    = Purplebox_DB::get_tenant($preselected_tenant_id);
                }
                $renew_unit_ids = json_decode($renew_contract['unit_ids'] ?? '[]', true);
                if (is_array($renew_unit_ids)) {
                    $preselected_unit_ids = array_map('absint', $renew_unit_ids);
                }
            }
        }

        $tenants         = Purplebox_DB::get_tenants(['status' => 'active', 'per_page' => 200]);
        $available_units = Purplebox_DB::get_available_units();

        include PURPLEBOX_PLUGIN_DIR . 'views/contract-new.php';
    }

    public static function handle_save() {
        if (!wp_verify_nonce($_POST['purplebox_nonce'] ?? '', 'purplebox_save_contract')) {
            wp_die(__('Security check failed', 'purplebox-storage'));
        }

        $unit_ids           = array_map('absint', (array) ($_POST['unit_ids'] ?? []));
        $tenant_id          = absint($_POST['tenant_id'] ?? 0);
        $move_in_date       = sanitize_text_field($_POST['move_in_date'] ?? '');
        $move_out_date      = sanitize_text_field($_POST['move_out_date'] ?? '');
        $first_payment_date = sanitize_text_field($_POST['first_payment_date'] ?? '');

        if (!$tenant_id || empty($unit_ids) || !$move_in_date) {
            wp_redirect(admin_url('admin.php?page=purplebox-contract-new&error=missing_fields'));
            exit;
        }

        $data = [
            'tenant_id'          => $tenant_id,
            'unit_ids'           => $unit_ids,
            'move_in_date'       => $move_in_date,
            'move_out_date'      => !empty($move_out_date) ? $move_out_date : null,
            'first_payment_date' => !empty($first_payment_date) ? $first_payment_date : null,
            'payment_method'     => sanitize_text_field($_POST['payment_method'] ?? 'Cash'),
            'next_payment_date'  => sanitize_text_field($_POST['next_payment_date'] ?? ''),
            'auto_renew'         => !empty($_POST['auto_renew']) ? 1 : 0,
            'notes'              => sanitize_textarea_field($_POST['notes'] ?? ''),
            'status'             => 'active',
        ];

        if (!empty($_FILES['signed_pdf']) && $_FILES['signed_pdf']['size'] > 0) {
            add_filter('upload_dir', [__CLASS__, 'custom_upload_dir']);
            $uploaded = wp_handle_upload($_FILES['signed_pdf'], [
                'test_form' => false,
                'mimes'     => ['pdf' => 'application/pdf'],
            ]);
            remove_filter('upload_dir', [__CLASS__, 'custom_upload_dir']);

            if (!isset($uploaded['error'])) {
                $data['signed_pdf_path'] = $uploaded['url'];
            }
        }

        $result = Purplebox_DB::save_contract($data);

        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=purplebox-contract-new&error=no_availability'));
            exit;
        }

        wp_redirect(admin_url('admin.php?page=purplebox-contracts&saved=created'));
        exit;
    }

    public static function render_edit() {
        if (!current_user_can('manage_purplebox')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        $contract_id = absint($_GET['contract_id'] ?? 0);
        $contract    = Purplebox_DB::get_contract($contract_id);

        if (!$contract) {
            wp_die(__('Contract not found.', 'purplebox-storage'));
        }

        $contract['unit_details'] = Purplebox_DB::get_unit_details_from_ids($contract['unit_ids'] ?? '[]');
        $current_unit_ids = array_map('intval', json_decode($contract['unit_ids'] ?? '[]', true) ?: []);

        // Load tenants for the dropdown
        $tenants = Purplebox_DB::get_tenants(['status' => 'active', 'per_page' => 200]);

        // Build selectable units: available units + units already on this contract (deduped)
        $available_units  = Purplebox_DB::get_available_units();
        $current_units    = $contract['unit_details'];
        $selectable_units = $available_units;
        $available_ids    = array_map(function($u) { return (int) $u['id']; }, $available_units);
        foreach ($current_units as $cu) {
            if (!in_array((int) $cu['id'], $available_ids)) {
                $selectable_units[] = $cu;
            }
        }
        usort($selectable_units, function($a, $b) {
            return strcmp($a['unit_number'], $b['unit_number']);
        });

        include PURPLEBOX_PLUGIN_DIR . 'views/contract-edit.php';
    }

    public static function handle_update() {
        if (!wp_verify_nonce($_POST['purplebox_nonce'] ?? '', 'purplebox_update_contract')) {
            wp_die(__('Security check failed', 'purplebox-storage'));
        }

        $contract_id = absint($_POST['contract_id'] ?? 0);
        if (!$contract_id) {
            wp_redirect(admin_url('admin.php?page=purplebox-contracts'));
            exit;
        }

        // Load existing contract
        $existing = Purplebox_DB::get_contract($contract_id);
        if (!$existing) {
            wp_die(__('Contract not found.', 'purplebox-storage'));
        }

        $move_in_date   = sanitize_text_field($_POST['move_in_date'] ?? $existing['move_in_date']);
        $move_out_date  = sanitize_text_field($_POST['move_out_date'] ?? '');
        $open_ended     = !empty($_POST['open_ended']);
        $duration_weeks = !empty($_POST['duration_weeks']) ? absint($_POST['duration_weeks']) : null;

        // Accept tenant and units from POST (editable now)
        $tenant_id = absint($_POST['tenant_id'] ?? $existing['tenant_id']);
        $unit_ids  = !empty($_POST['unit_ids'])
            ? array_map('absint', (array) $_POST['unit_ids'])
            : json_decode($existing['unit_ids'] ?? '[]', true);

        // Validate newly-added units for availability
        $existing_unit_ids = array_map('intval', json_decode($existing['unit_ids'] ?? '[]', true) ?: []);
        $newly_added = array_diff($unit_ids, $existing_unit_ids);
        if (!empty($newly_added)) {
            $rented_ids = Purplebox_DB::get_all_rented_unit_ids();
            foreach ($newly_added as $uid) {
                $u = Purplebox_DB::get_unit($uid);
                $is_manual = !empty($u['manual_status']) && $u['manual_status'] === 'rented';
                if ($is_manual || in_array($uid, $rented_ids)) {
                    wp_redirect(admin_url('admin.php?page=purplebox-contracts&action=edit&contract_id=' . $contract_id . '&error=no_availability'));
                    exit;
                }
            }
        }

        // Compute duration_weeks from dates if not manually supplied
        if (!$duration_weeks && !$open_ended && $move_out_date && $move_in_date) {
            $diff = (strtotime($move_out_date) - strtotime($move_in_date)) / 86400;
            $duration_weeks = max(1, round($diff / 7));
        }

        $data = [
            'id'               => $contract_id,
            'tenant_id'        => $tenant_id,
            'unit_ids'         => $unit_ids,
            'move_in_date'     => $move_in_date,
            'move_out_date'    => (!$open_ended && $move_out_date) ? $move_out_date : null,
            'duration_weeks'   => $duration_weeks,
            'payment_method'   => sanitize_text_field($_POST['payment_method'] ?? 'Cash'),
            'next_payment_date'=> sanitize_text_field($_POST['next_payment_date'] ?? ''),
            'auto_renew'       => !empty($_POST['auto_renew']) ? 1 : 0,
            'notes'            => sanitize_textarea_field($_POST['notes'] ?? $existing['notes'] ?? ''),
            'status'           => sanitize_text_field($_POST['status'] ?? $existing['status']),
            'signed_pdf_path'  => $existing['signed_pdf_path'] ?? null,
        ];

        // Handle PDF upload
        if (!empty($_FILES['signed_pdf']) && $_FILES['signed_pdf']['size'] > 0) {
            add_filter('upload_dir', [__CLASS__, 'custom_upload_dir']);
            $uploaded = wp_handle_upload($_FILES['signed_pdf'], [
                'test_form' => false,
                'mimes'     => ['pdf' => 'application/pdf'],
            ]);
            remove_filter('upload_dir', [__CLASS__, 'custom_upload_dir']);

            if (!isset($uploaded['error'])) {
                $data['signed_pdf_path'] = $uploaded['url'];
            }
        }

        Purplebox_DB::save_contract($data);

        wp_redirect(admin_url('admin.php?page=purplebox-contracts&saved=updated'));
        exit;
    }

    public static function custom_upload_dir($dirs) {
        $dirs['subdir'] = '/purplebox/contracts';
        $dirs['path']   = $dirs['basedir'] . '/purplebox/contracts';
        $dirs['url']    = $dirs['baseurl'] . '/purplebox/contracts';
        return $dirs;
    }

    public static function render_agreement() {
        if (!current_user_can('manage_purplebox')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        $contract_id = absint($_GET['contract_id'] ?? 0);
        $contract    = Purplebox_DB::get_contract($contract_id);

        if (!$contract) {
            wp_die(__('Contract not found.', 'purplebox-storage'));
        }

        $tenant       = Purplebox_DB::get_tenant($contract['tenant_id']);
        $unit_details = Purplebox_DB::get_unit_details_from_ids($contract['unit_ids'] ?? '[]');
        $unit_label   = implode(', ', array_map(
            function ($u) { return $u['unit_number'] . ' (' . $u['size_category'] . ')'; },
            $unit_details
        ));

        $phones = json_decode($tenant['phones'] ?? '[]', true);
        if (!is_array($phones)) {
            $phones = [];
        }
        $contact_number   = $phones[0] ?? '';
        $emergency_number = isset($phones[1]) ? $phones[1] : $contact_number;

        $access_persons = json_decode($tenant['access_persons'] ?? '[]', true);
        if (!is_array($access_persons)) $access_persons = [];

        // Format dates as DD/MM/YYYY
        $fmt_date = function($d) {
            if (empty($d)) return '';
            $ts = strtotime($d);
            return $ts ? date('d/m/Y', $ts) : $d;
        };

        // Build access person fields (name + phone + ID type/number)
        $access_field = function($p) {
            if (empty($p)) return ['name' => '', 'phone' => '', 'id' => ''];
            $id = '';
            if (!empty($p['id_type']) && !empty($p['id_number'])) {
                $id = $p['id_type'] . ': ' . $p['id_number'];
            } elseif (!empty($p['id_number'])) {
                $id = $p['id_number'];
            }
            return [
                'name'  => $p['name']  ?? '',
                'phone' => $p['phone'] ?? '',
                'id'    => $id,
            ];
        };

        $data = [
            'full_name' => $tenant['full_name']       ?? '',
            'address'   => $tenant['address']         ?? '',
            'contact'   => $contact_number,
            'email'     => $tenant['email']           ?? '',
            'emergency' => $emergency_number,
            'move_in'   => $fmt_date($contract['move_in_date']  ?? ''),
            'move_out'  => $fmt_date($contract['move_out_date'] ?? ''),
            'unit_size' => $unit_label,
            'access1'   => $access_field($access_persons[0] ?? []),
            'access2'   => $access_field($access_persons[1] ?? []),
            'access3'   => $access_field($access_persons[2] ?? []),
        ];

        $pdf_path    = PURPLEBOX_PLUGIN_DIR . 'Customer Agreement.pdf';
        $tenant_name = esc_js($tenant['full_name'] ?? 'Contract');
        $json_data   = wp_json_encode($data);

        if (!file_exists($pdf_path)) {
            wp_die(__('PDF template not found. Please ensure "Customer Agreement.pdf" is inside the purplebox-plugin folder on the server.', 'purplebox-storage'));
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $pdf_base64 = base64_encode(file_get_contents($pdf_path));
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Generating Agreement</title>
            <style>
                body { font-family: sans-serif; display:flex; align-items:center; justify-content:center;
                       height:100vh; margin:0; background:#f0f0f1; }
                .box { background:#fff; padding:40px 60px; border-radius:8px; text-align:center;
                       box-shadow:0 2px 12px rgba(0,0,0,.15); }
                .box h2 { margin:0 0 10px; color:#1d2327; }
                .box p  { color:#50575e; margin:0; }
            </style>
        </head>
        <body>
        <div class="box">
            <h2>Preparing your PDF&hellip;</h2>
            <p>The filled agreement will download automatically.</p>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
        <script>
        (async () => {
            try {
                const PDFLib    = window.PDFLib;
                const data      = <?php echo $json_data; ?>;

                // PDF is embedded by PHP as base64 — no server request needed
                const b64       = <?php echo wp_json_encode($pdf_base64); ?>;
                const binStr    = atob(b64);
                const bytes     = new Uint8Array(binStr.length);
                for (let i = 0; i < binStr.length; i++) bytes[i] = binStr.charCodeAt(i);
                const pdfBytes0 = bytes.buffer;

                const pdfDoc    = await PDFLib.PDFDocument.load(pdfBytes0);
                const pages     = pdfDoc.getPages();
                const page1     = pages[0];
                const { height } = page1.getSize(); // 2000 x 2830 pt

                const font     = await pdfDoc.embedFont(PDFLib.StandardFonts.Helvetica);
                const fontSize = 26;
                // off: nudge text down so it sits centred inside each row box.
                // Larger font needs a bigger downward shift to avoid clipping the top.
                const off = -8;
                const fields = [
                    { text: data.full_name, x: 440,  y: height - 1096 + off },
                    { text: data.address,   x: 440,  y: height - 1211 + off },
                    { text: data.contact,   x: 520,  y: height - 1326 + off },
                    { text: data.email,     x: 1410, y: height - 1326 + off },
                    { text: data.emergency, x: 550,  y: height - 1441 + off },
                    { text: data.move_in,   x: 470,  y: height - 1556 + off },
                    { text: data.move_out,  x: 1405, y: height - 1556 + off },
                    { text: data.unit_size, x: 480,  y: height - 1671 + off },
                ];

                for (const f of fields) {
                    if (!f.text) continue;
                    page1.drawText(String(f.text), {
                        x: f.x, y: f.y,
                        size: fontSize,
                        font,
                        color: PDFLib.rgb(0, 0, 0),
                    });
                }

                // Access persons: name row, then phone + ID on lines below
                const accessXs   = [410, 930, 1450];
                const accessBase = height - 1789 + off;
                const subSize    = 20;
                const lineGap    = 34;
                [data.access1, data.access2, data.access3].forEach((ap, i) => {
                    const x = accessXs[i];
                    if (ap.name) {
                        page1.drawText(ap.name,  { x, y: accessBase,             size: fontSize, font, color: PDFLib.rgb(0,0,0) });
                    }
                    if (ap.phone) {
                        page1.drawText(ap.phone, { x, y: accessBase - lineGap,   size: subSize,  font, color: PDFLib.rgb(0,0,0) });
                    }
                    if (ap.id) {
                        page1.drawText(ap.id,    { x, y: accessBase - lineGap*2, size: subSize,  font, color: PDFLib.rgb(0,0,0) });
                    }
                });

                const filled  = await pdfDoc.save();
                const blob    = new Blob([filled], { type: 'application/pdf' });
                const link    = document.createElement('a');
                link.href     = URL.createObjectURL(blob);
                link.download = 'Customer_Agreement_<?php echo $tenant_name; ?>.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                document.querySelector('.box p').textContent = 'Download started. You may close this tab.';
            } catch (err) {
                document.querySelector('.box h2').textContent = 'Error';
                document.querySelector('.box p').textContent  = err.message;
            }
        })();
        </script>
        </body>
        </html>
        <?php
        exit;
    }
}
