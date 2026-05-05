<?php
if (!defined('ABSPATH')) {
    exit;
}

class Purplebox_Contracts_Controller {

    public static function render_list() {
        if (!current_user_can('manage_options')) {
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

        include PURPLEBOX_PLUGIN_DIR . 'views/contract-detail.php';
    }

    public static function render_wizard() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'purplebox-storage'));
        }

        $preselected_tenant_id = absint($_GET['tenant_id'] ?? 0);
        $preselected_tenant = $preselected_tenant_id ? Purplebox_DB::get_tenant($preselected_tenant_id) : null;

        $tenants         = Purplebox_DB::get_tenants(['status' => 'active', 'per_page' => 200]);
        $available_units = Purplebox_DB::get_available_units();

        include PURPLEBOX_PLUGIN_DIR . 'views/contract-new.php';
    }

    public static function handle_save() {
        if (!wp_verify_nonce($_POST['purplebox_nonce'] ?? '', 'purplebox_save_contract')) {
            wp_die(__('Security check failed', 'purplebox-storage'));
        }

        $unit_ids      = array_map('absint', (array) ($_POST['unit_ids'] ?? []));
        $tenant_id     = absint($_POST['tenant_id'] ?? 0);
        $move_in_date  = sanitize_text_field($_POST['move_in_date'] ?? '');
        $move_out_date = sanitize_text_field($_POST['move_out_date'] ?? '');
        $duration_weeks = !empty($_POST['duration_weeks']) ? absint($_POST['duration_weeks']) : null;

        if (!$tenant_id || empty($unit_ids) || !$move_in_date) {
            wp_redirect(admin_url('admin.php?page=purplebox-contract-new&error=missing_fields'));
            exit;
        }

        $data = [
            'tenant_id'        => $tenant_id,
            'unit_ids'         => $unit_ids,
            'move_in_date'     => $move_in_date,
            'move_out_date'    => !empty($move_out_date) ? $move_out_date : null,
            'duration_weeks'   => $duration_weeks,
            'payment_method'   => sanitize_text_field($_POST['payment_method'] ?? 'Cash'),
            'next_payment_date'=> sanitize_text_field($_POST['next_payment_date'] ?? ''),
            'auto_renew'       => !empty($_POST['auto_renew']) ? 1 : 0,
            'status'           => 'active',
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

        wp_redirect(admin_url('admin.php?page=purplebox-contracts&action=view&contract_id=' . $result . '&created=1'));
        exit;
    }

    public static function custom_upload_dir($dirs) {
        $dirs['subdir'] = '/purplebox/contracts';
        $dirs['path']   = $dirs['basedir'] . '/purplebox/contracts';
        $dirs['url']    = $dirs['baseurl'] . '/purplebox/contracts';
        return $dirs;
    }

    public static function render_agreement() {
        if (!current_user_can('manage_options')) {
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

        $data = [
            'full_name' => $tenant['full_name']       ?? '',
            'address'   => $tenant['address']         ?? '',
            'contact'   => $contact_number,
            'email'     => $tenant['email']           ?? '',
            'emergency' => $emergency_number,
            'move_in'   => $fmt_date($contract['move_in_date']  ?? ''),
            'move_out'  => $fmt_date($contract['move_out_date'] ?? ''),
            'unit_size' => $unit_label,
            'access1'   => $access_persons[0]['name'] ?? '',
            'access2'   => $access_persons[1]['name'] ?? '',
            'access3'   => $access_persons[2]['name'] ?? '',
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
                const fontSize = 20;
                // off: calibrated so text sits inside each box.
                // -32 = bottom edge, +22 = above box, so ~-5 centres nicely.
                const off = -5;
                const fields = [
                    { text: data.full_name, x: 350,  y: height - 1096 + off },
                    { text: data.address,   x: 350,  y: height - 1211 + off },
                    { text: data.contact,   x: 430,  y: height - 1326 + off },
                    { text: data.email,     x: 1320, y: height - 1326 + off },
                    { text: data.emergency, x: 460,  y: height - 1441 + off },
                    { text: data.move_in,   x: 380,  y: height - 1556 + off },
                    { text: data.move_out,  x: 1315, y: height - 1556 + off },
                    { text: data.unit_size, x: 390,  y: height - 1671 + off },
                    // Three separate access boxes across the row
                    { text: data.access1,   x: 320,  y: height - 1789 + off },
                    { text: data.access2,   x: 840,  y: height - 1789 + off },
                    { text: data.access3,   x: 1360, y: height - 1789 + off },
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
