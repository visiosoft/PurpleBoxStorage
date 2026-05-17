<?php if (!defined('ABSPATH')) exit; ?>
<?php
$tab     = sanitize_key($_GET['tab'] ?? 'inventory');
$base    = admin_url('admin.php?page=purplebox-reports');
$tabs    = [
    'inventory' => __('Unit Inventory',       'purplebox-storage'),
    'contracts' => __('Active Contracts',     'purplebox-storage'),
    'tenants'   => __('Tenant Directory',     'purplebox-storage'),
    'forecast'  => __('Availability Forecast','purplebox-storage'),
    'occupancy' => __('Occupancy Summary',    'purplebox-storage'),
];
$today_label = date('d/m/Y H:i');
?>
<!-- Print header (only visible when printing) -->
<div class="pb-print-header">
    <div class="pb-print-logo">PurpleBox Storage</div>
    <div class="pb-print-meta">
        <strong><?php echo esc_html($tabs[$tab] ?? ''); ?></strong> &nbsp;·&nbsp;
        <?php echo esc_html($today_label); ?>
    </div>
</div>

<div class="wrap purplebox-wrap pb-reports-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Reports', 'purplebox-storage'); ?></h1>
    <hr class="wp-header-end">

    <!-- Tab nav -->
    <nav class="pb-report-tabs no-print">
        <?php foreach ($tabs as $key => $label) : ?>
            <a href="<?php echo esc_url(add_query_arg('tab', $key, $base)); ?>"
               class="pb-report-tab <?php echo $tab === $key ? 'active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php // ── 1. UNIT INVENTORY ─────────────────────────────────────────────
    if ($tab === 'inventory') :
        $floor_filter  = sanitize_text_field($_GET['floor']  ?? '');
        $size_filter   = sanitize_text_field($_GET['size']   ?? '');
        $status_filter = sanitize_text_field($_GET['status'] ?? '');
        $all_floors    = ['', 'F1', 'F2', 'F3'];
        $all_sizes     = ['', 'Locker', '10 sq.ft.', '25 sq.ft.', '35 sq.ft.', '50 sq.ft.', '75 sq.ft.', '100 sq.ft.', '150 sq.ft.', '200 sq.ft.', 'Custom'];
    ?>
    <div class="pb-report-toolbar no-print">
        <form method="get" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <input type="hidden" name="page" value="purplebox-reports">
            <input type="hidden" name="tab"  value="inventory">
            <select name="floor" class="pb-filter-select">
                <option value=""><?php esc_html_e('All Floors', 'purplebox-storage'); ?></option>
                <?php foreach (['F1','F2','F3'] as $f) : ?>
                    <option value="<?php echo esc_attr($f); ?>" <?php selected($floor_filter,$f); ?>><?php echo esc_html($f); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="size" class="pb-filter-select">
                <option value=""><?php esc_html_e('All Sizes', 'purplebox-storage'); ?></option>
                <?php foreach (array_slice($all_sizes,1) as $s) : ?>
                    <option value="<?php echo esc_attr($s); ?>" <?php selected($size_filter,$s); ?>><?php echo esc_html($s); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="pb-filter-select">
                <option value=""><?php esc_html_e('All Status', 'purplebox-storage'); ?></option>
                <option value="available" <?php selected($status_filter,'available'); ?>><?php esc_html_e('Available', 'purplebox-storage'); ?></option>
                <option value="rented"    <?php selected($status_filter,'rented');    ?>><?php esc_html_e('Rented',    'purplebox-storage'); ?></option>
            </select>
            <?php submit_button(__('Filter','purplebox-storage'),'secondary','',false); ?>
            <?php if ($floor_filter || $size_filter || $status_filter) : ?>
                <a href="<?php echo esc_url(add_query_arg('tab','inventory',$base)); ?>" class="button"><?php esc_html_e('Clear','purplebox-storage'); ?></a>
            <?php endif; ?>
        </form>
        <div class="pb-report-actions">
            <button class="button" onclick="pbPrint()"><?php esc_html_e('🖨 Print','purplebox-storage'); ?></button>
            <button class="button" onclick="pbExportCSV('pb-table-inventory','inventory-report')"><?php esc_html_e('⬇ CSV','purplebox-storage'); ?></button>
        </div>
    </div>

    <p class="pb-record-count no-print">
        <?php printf(esc_html__('%d units found','purplebox-storage'), count($data)); ?>
    </p>

    <?php if (empty($data)) : ?>
        <p class="pb-empty"><?php esc_html_e('No units found.','purplebox-storage'); ?></p>
    <?php else : ?>
    <table class="pb-report-table widefat" id="pb-table-inventory">
        <thead>
            <tr>
                <th><?php esc_html_e('Unit No.',      'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Size Label',    'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Size Category', 'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Floor',         'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Status',        'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Available',     'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Rented',        'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Price (AED)',   'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Discount',      'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Features',      'purplebox-storage'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $u) : ?>
            <tr>
                <td><strong><?php echo esc_html($u['unit_number']); ?></strong></td>
                <td><?php echo esc_html($u['display_name'] ?? '—'); ?></td>
                <td><?php echo esc_html($u['size_category']); ?></td>
                <td><?php echo esc_html($u['floor']); ?></td>
                <td>
                    <span class="pill <?php echo $u['unit_status'] === 'available' ? 'available' : 'rented'; ?>">
                        <?php echo $u['unit_status'] === 'available' ? esc_html__('Available','purplebox-storage') : esc_html__('Rented','purplebox-storage'); ?>
                    </span>
                </td>
                <td><?php echo esc_html($u['avail_count']); ?></td>
                <td><?php echo esc_html($u['rented_count']); ?></td>
                <td><?php echo !empty($u['price']) ? 'AED ' . number_format((float)$u['price'], 2) : '—'; ?></td>
                <td><?php echo !empty($u['discount_pct']) ? esc_html($u['discount_pct']) . '% → AED ' . number_format((float)$u['discounted_price'],2) : '—'; ?></td>
                <td style="font-size:11px;"><?php echo !empty($u['features_arr']) ? esc_html(implode(', ', $u['features_arr'])) : '—'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php // ── 2. CONTRACTS ──────────────────────────────────────────────────
    elseif ($tab === 'contracts') :
        $status_filter   = sanitize_text_field($_GET['status']   ?? 'active');
        $expiring_filter = absint($_GET['expiring'] ?? 0);
    ?>
    <div class="pb-report-toolbar no-print">
        <form method="get" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <input type="hidden" name="page" value="purplebox-reports">
            <input type="hidden" name="tab"  value="contracts">
            <select name="status" class="pb-filter-select">
                <option value="active" <?php selected($status_filter,'active'); ?>><?php esc_html_e('Active',     'purplebox-storage'); ?></option>
                <option value="ended"  <?php selected($status_filter,'ended');  ?>><?php esc_html_e('Ended',      'purplebox-storage'); ?></option>
                <option value="all"    <?php selected($status_filter,'all');    ?>><?php esc_html_e('All',        'purplebox-storage'); ?></option>
            </select>
            <select name="expiring" class="pb-filter-select">
                <option value="0"  <?php selected($expiring_filter,0);  ?>><?php esc_html_e('Any expiry',      'purplebox-storage'); ?></option>
                <option value="7"  <?php selected($expiring_filter,7);  ?>><?php esc_html_e('Expiring in 7d',  'purplebox-storage'); ?></option>
                <option value="15" <?php selected($expiring_filter,15); ?>><?php esc_html_e('Expiring in 15d', 'purplebox-storage'); ?></option>
                <option value="30" <?php selected($expiring_filter,30); ?>><?php esc_html_e('Expiring in 30d', 'purplebox-storage'); ?></option>
            </select>
            <?php submit_button(__('Filter','purplebox-storage'),'secondary','',false); ?>
        </form>
        <div class="pb-report-actions">
            <button class="button" onclick="pbPrint()"><?php esc_html_e('🖨 Print','purplebox-storage'); ?></button>
            <button class="button" onclick="pbExportCSV('pb-table-contracts','contracts-report')"><?php esc_html_e('⬇ CSV','purplebox-storage'); ?></button>
        </div>
    </div>

    <p class="pb-record-count no-print">
        <?php printf(esc_html__('%d contracts found','purplebox-storage'), count($data)); ?>
    </p>

    <?php if (empty($data)) : ?>
        <p class="pb-empty"><?php esc_html_e('No contracts found.','purplebox-storage'); ?></p>
    <?php else : ?>
    <table class="pb-report-table widefat" id="pb-table-contracts">
        <thead>
            <tr>
                <th>#</th>
                <th><?php esc_html_e('Tenant',          'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Client ID',        'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Phone',            'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Units',            'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Move In',          'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Expiry Date',      'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Days Left',        'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Payment',          'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Status',           'purplebox-storage'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $c) :
                $days_left   = isset($c['days_left']) ? (int)$c['days_left'] : null;
                $row_class   = '';
                if ($days_left !== null && $c['status'] === 'active') {
                    if ($days_left <= 7)  $row_class = 'pb-row-urgent';
                    elseif ($days_left <= 15) $row_class = 'pb-row-warning';
                }
            ?>
            <tr class="<?php echo $row_class; ?>">
                <td><?php echo esc_html($c['id']); ?></td>
                <td><strong><?php echo esc_html($c['tenant_name'] ?? '—'); ?></strong></td>
                <td><?php echo esc_html($c['tenant_client_id'] ?? '—'); ?></td>
                <td><?php $phones = json_decode($c['phones'] ?? '[]', true); echo esc_html(is_array($phones) ? implode(', ', $phones) : ($c['phones'] ?? '—')); ?></td>
                <td><?php echo esc_html($c['unit_labels'] ?? '—'); ?></td>
                <td><?php echo $c['move_in_date']  ? esc_html(date('d/m/Y', strtotime($c['move_in_date'])))  : '—'; ?></td>
                <td>
                    <?php if ($c['move_out_date']) : ?>
                        <strong><?php echo esc_html(date('d/m/Y', strtotime($c['move_out_date']))); ?></strong>
                    <?php else : ?>
                        <span style="color:#50575e;"><?php esc_html_e('Open-ended','purplebox-storage'); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($days_left !== null && $c['status'] === 'active') :
                        $dc = $days_left <= 0 ? '#b32d2e' : ($days_left <= 7 ? '#b32d2e' : ($days_left <= 15 ? '#8a6500' : '#1d2327'));
                    ?>
                        <strong style="color:<?php echo $dc; ?>">
                            <?php echo $days_left <= 0 ? esc_html__('Expired','purplebox-storage') : sprintf('%d %s', $days_left, esc_html__('days','purplebox-storage')); ?>
                        </strong>
                    <?php elseif ($c['status'] === 'ended') : ?>
                        <span style="color:#50575e;">—</span>
                    <?php else : ?>
                        <span style="color:#50575e;">—</span>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html($c['payment_method'] ?? '—'); ?></td>
                <td><span class="pill <?php echo esc_attr($c['status']); ?>"><?php echo esc_html(ucfirst($c['status'])); ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php // ── 3. TENANT DIRECTORY ───────────────────────────────────────────
    elseif ($tab === 'tenants') :
        $status_filter = sanitize_text_field($_GET['status'] ?? 'all');
    ?>
    <div class="pb-report-toolbar no-print">
        <form method="get" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <input type="hidden" name="page" value="purplebox-reports">
            <input type="hidden" name="tab"  value="tenants">
            <select name="status" class="pb-filter-select">
                <option value="all"    <?php selected($status_filter,'all');    ?>><?php esc_html_e('All Tenants',    'purplebox-storage'); ?></option>
                <option value="active" <?php selected($status_filter,'active'); ?>><?php esc_html_e('Active Tenants', 'purplebox-storage'); ?></option>
                <option value="ended"  <?php selected($status_filter,'ended');  ?>><?php esc_html_e('Ended Tenants',  'purplebox-storage'); ?></option>
            </select>
            <?php submit_button(__('Filter','purplebox-storage'),'secondary','',false); ?>
        </form>
        <div class="pb-report-actions">
            <button class="button" onclick="pbPrint()"><?php esc_html_e('🖨 Print','purplebox-storage'); ?></button>
            <button class="button" onclick="pbExportCSV('pb-table-tenants','tenants-report')"><?php esc_html_e('⬇ CSV','purplebox-storage'); ?></button>
        </div>
    </div>

    <p class="pb-record-count no-print">
        <?php printf(esc_html__('%d tenants found','purplebox-storage'), count($data)); ?>
    </p>

    <?php if (empty($data)) : ?>
        <p class="pb-empty"><?php esc_html_e('No tenants found.','purplebox-storage'); ?></p>
    <?php else : ?>
    <table class="pb-report-table widefat" id="pb-table-tenants">
        <thead>
            <tr>
                <th><?php esc_html_e('Client ID',       'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Full Name',        'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Type',             'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Phone',            'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Email',            'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Emirates ID',      'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Nationality',      'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Active Units',     'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Expiry',           'purplebox-storage'); ?></th>
                <th><?php esc_html_e('Status',           'purplebox-storage'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $t) :
                $active  = $t['active_contracts'];
                $unit_labels = implode(', ', array_column($active, 'unit_labels'));
                $expiries    = array_filter(array_column($active, 'move_out_date'));
                sort($expiries);
                $earliest_expiry = !empty($expiries) ? date('d/m/Y', strtotime($expiries[0])) : '—';
            ?>
            <tr>
                <td><?php echo esc_html($t['client_id']); ?></td>
                <td><strong><?php echo esc_html($t['full_name']); ?></strong></td>
                <td><?php echo esc_html(ucfirst($t['tenant_type'] ?? 'individual')); ?></td>
                <td><?php $phones = json_decode($t['phones'] ?? '[]', true); echo esc_html(is_array($phones) ? implode(', ', $phones) : ($t['phones'] ?? '—')); ?></td>
                <td><?php echo esc_html($t['email'] ?? '—'); ?></td>
                <td><?php echo esc_html($t['emirates_id'] ?? '—'); ?></td>
                <td><?php echo esc_html($t['nationality'] ?? '—'); ?></td>
                <td><?php echo $unit_labels ? esc_html($unit_labels) : '<span style="color:#50575e;">—</span>'; ?></td>
                <td><?php echo esc_html($earliest_expiry); ?></td>
                <td><span class="pill <?php echo esc_attr($t['status']); ?>"><?php echo esc_html(ucfirst($t['status'])); ?></span></td>
            </tr>
            <!-- Contract history sub-rows -->
            <?php if (!empty($t['contracts'])) : ?>
            <tr class="pb-sub-row no-print">
                <td colspan="10" style="padding:0 12px 10px 40px; background:#fafafa;">
                    <table class="pb-sub-table">
                        <thead><tr>
                            <th><?php esc_html_e('Contract #','purplebox-storage'); ?></th>
                            <th><?php esc_html_e('Units','purplebox-storage'); ?></th>
                            <th><?php esc_html_e('Move In','purplebox-storage'); ?></th>
                            <th><?php esc_html_e('Expiry','purplebox-storage'); ?></th>
                            <th><?php esc_html_e('Days Left','purplebox-storage'); ?></th>
                            <th><?php esc_html_e('Status','purplebox-storage'); ?></th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($t['contracts'] as $con) : ?>
                            <tr>
                                <td>#<?php echo esc_html($con['id']); ?></td>
                                <td><?php echo esc_html($con['unit_labels'] ?? '—'); ?></td>
                                <td><?php echo $con['move_in_date']  ? esc_html(date('d/m/Y', strtotime($con['move_in_date'])))  : '—'; ?></td>
                                <td><?php echo $con['move_out_date'] ? esc_html(date('d/m/Y', strtotime($con['move_out_date']))) : esc_html__('Open-ended','purplebox-storage'); ?></td>
                                <td><?php if ($con['status']==='active' && $con['move_out_date']) { $dl=(int)$con['days_left']; echo '<strong style="color:'.($dl<=7?'#b32d2e':($dl<=15?'#8a6500':'#1d2327')).'">'.($dl<=0?esc_html__('Expired','purplebox-storage'):$dl.' '.esc_html__('days','purplebox-storage')).'</strong>'; } else { echo '—'; } ?></td>
                                <td><span class="pill <?php echo esc_attr($con['status']); ?>"><?php echo esc_html(ucfirst($con['status'])); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php // ── 4. AVAILABILITY FORECAST ──────────────────────────────────────
    elseif ($tab === 'forecast') : ?>
    <div class="pb-report-toolbar no-print">
        <div class="pb-report-actions">
            <button class="button" onclick="pbPrint()"><?php esc_html_e('🖨 Print','purplebox-storage'); ?></button>
        </div>
    </div>

    <div class="pb-forecast-grid">
        <!-- Available Now -->
        <div class="postbox pb-forecast-box">
            <div class="postbox-header">
                <h2 style="color:#00691f;"><?php esc_html_e('Available Now','purplebox-storage'); ?></h2>
                <span class="pb-count-badge" style="background:#00691f;"><?php echo count($data['available'] ?? []); ?></span>
            </div>
            <div class="inside" style="padding:0;">
                <?php if (empty($data['available'])) : ?>
                    <p class="pb-empty-inner"><?php esc_html_e('No available units.','purplebox-storage'); ?></p>
                <?php else : ?>
                <table class="pb-forecast-table">
                    <thead><tr>
                        <th><?php esc_html_e('Unit','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Label','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Size','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Floor','purplebox-storage'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($data['available'] as $u) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($u['unit_number']); ?></strong></td>
                            <td><?php echo esc_html($u['display_name'] ?? '—'); ?></td>
                            <td><?php echo esc_html($u['size_category']); ?></td>
                            <td><?php echo esc_html($u['floor']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expiring This Week -->
        <div class="postbox pb-forecast-box">
            <div class="postbox-header">
                <h2 style="color:#8a6500;"><?php esc_html_e('Expiring This Week (0–7 days)','purplebox-storage'); ?></h2>
                <span class="pb-count-badge" style="background:#8a6500;"><?php echo count($data['week'] ?? []); ?></span>
            </div>
            <div class="inside" style="padding:0;">
                <?php if (empty($data['week'])) : ?>
                    <p class="pb-empty-inner"><?php esc_html_e('None expiring this week.','purplebox-storage'); ?></p>
                <?php else : ?>
                <table class="pb-forecast-table">
                    <thead><tr>
                        <th><?php esc_html_e('Units','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Tenant','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Expiry','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Days','purplebox-storage'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($data['week'] as $r) : $dl = (int)$r['days_left']; ?>
                        <tr>
                            <td><strong><?php echo esc_html($r['unit_labels']); ?></strong></td>
                            <td><?php echo esc_html($r['tenant_name'] ?? '—'); ?></td>
                            <td><?php echo esc_html(date('d/m/Y', strtotime($r['move_out_date']))); ?></td>
                            <td><strong style="color:#b32d2e;"><?php echo $dl <= 0 ? esc_html__('Today!','purplebox-storage') : $dl . 'd'; ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expiring This Month -->
        <div class="postbox pb-forecast-box">
            <div class="postbox-header">
                <h2 style="color:#1e4ea1;"><?php esc_html_e('Expiring in 8–30 Days','purplebox-storage'); ?></h2>
                <span class="pb-count-badge" style="background:#1e4ea1;"><?php echo count($data['month'] ?? []); ?></span>
            </div>
            <div class="inside" style="padding:0;">
                <?php if (empty($data['month'])) : ?>
                    <p class="pb-empty-inner"><?php esc_html_e('None expiring in this period.','purplebox-storage'); ?></p>
                <?php else : ?>
                <table class="pb-forecast-table">
                    <thead><tr>
                        <th><?php esc_html_e('Units','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Tenant','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Expiry','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Days','purplebox-storage'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($data['month'] as $r) : $dl = (int)$r['days_left']; ?>
                        <tr>
                            <td><strong><?php echo esc_html($r['unit_labels']); ?></strong></td>
                            <td><?php echo esc_html($r['tenant_name'] ?? '—'); ?></td>
                            <td><?php echo esc_html(date('d/m/Y', strtotime($r['move_out_date']))); ?></td>
                            <td style="color:#1e4ea1;"><?php echo $dl . 'd'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php // ── 5. OCCUPANCY SUMMARY ──────────────────────────────────────────
    elseif ($tab === 'occupancy') :
        $t = $data['totals'] ?? [];
    ?>
    <div class="pb-report-toolbar no-print">
        <div class="pb-report-actions">
            <button class="button" onclick="pbPrint()"><?php esc_html_e('🖨 Print','purplebox-storage'); ?></button>
        </div>
    </div>

    <!-- KPI tiles -->
    <div class="at-a-glance" style="margin-bottom:24px;">
        <div class="glance-tile">
            <div class="label"><?php esc_html_e('Total Slots','purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($t['total'] ?? 0); ?></div>
        </div>
        <div class="glance-tile success">
            <div class="label"><?php esc_html_e('Available','purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($t['available'] ?? 0); ?></div>
        </div>
        <div class="glance-tile">
            <div class="label"><?php esc_html_e('Rented','purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($t['rented'] ?? 0); ?></div>
        </div>
        <div class="glance-tile">
            <div class="label"><?php esc_html_e('Occupancy %','purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($t['occupancy'] ?? 0); ?>%</div>
        </div>
        <div class="glance-tile">
            <div class="label"><?php esc_html_e('Active Tenants','purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($data['active_tenants'] ?? 0); ?></div>
        </div>
        <div class="glance-tile <?php echo ($data['expiring_soon'] ?? 0) > 0 ? 'warning' : ''; ?>">
            <div class="label"><?php esc_html_e('Expiring ≤15 Days','purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($data['expiring_soon'] ?? 0); ?></div>
        </div>
    </div>

    <div class="pb-occ-grid">
        <!-- By Size -->
        <div class="postbox">
            <div class="postbox-header"><h2><?php esc_html_e('By Size Category','purplebox-storage'); ?></h2></div>
            <div class="inside" style="padding:0;">
                <table class="pb-report-table widefat">
                    <thead><tr>
                        <th><?php esc_html_e('Size','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Total','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Rented','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Available','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Occupancy','purplebox-storage'); ?></th>
                        <th style="min-width:120px;"><?php esc_html_e('Bar','purplebox-storage'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($data['by_size'] ?? [] as $row) :
                        $occ = (float)$row['occupancy'];
                        $bar_class = $occ >= 100 ? 'full' : ($occ >= 80 ? 'low' : '');
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($row['label']); ?></strong></td>
                            <td><?php echo esc_html($row['total']); ?></td>
                            <td><?php echo esc_html($row['rented']); ?></td>
                            <td><?php echo esc_html($row['available']); ?></td>
                            <td><strong><?php echo esc_html($row['occupancy']); ?>%</strong></td>
                            <td>
                                <div class="bar" style="height:8px;">
                                    <div class="bar-fill <?php echo $bar_class; ?>" style="width:<?php echo esc_attr($occ); ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- By Floor -->
        <div class="postbox">
            <div class="postbox-header"><h2><?php esc_html_e('By Floor','purplebox-storage'); ?></h2></div>
            <div class="inside" style="padding:0;">
                <table class="pb-report-table widefat">
                    <thead><tr>
                        <th><?php esc_html_e('Floor','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Total','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Rented','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Available','purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Occupancy','purplebox-storage'); ?></th>
                        <th style="min-width:120px;"><?php esc_html_e('Bar','purplebox-storage'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($data['by_floor'] ?? [] as $row) :
                        $occ = (float)$row['occupancy'];
                        $bar_class = $occ >= 100 ? 'full' : ($occ >= 80 ? 'low' : '');
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($row['label']); ?></strong></td>
                            <td><?php echo esc_html($row['total']); ?></td>
                            <td><?php echo esc_html($row['rented']); ?></td>
                            <td><?php echo esc_html($row['available']); ?></td>
                            <td><strong><?php echo esc_html($row['occupancy']); ?>%</strong></td>
                            <td>
                                <div class="bar" style="height:8px;">
                                    <div class="bar-fill <?php echo $bar_class; ?>" style="width:<?php echo esc_attr($occ); ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p style="color:#50575e; font-size:12px; margin-top:8px;">
        <?php printf(esc_html__('Generated: %s','purplebox-storage'), esc_html($data['generated_at'] ?? '')); ?>
    </p>

    <?php endif; ?>
</div>

<!-- JS: print + CSV export -->
<script>
function pbPrint() { window.print(); }

function pbExportCSV(tableId, filename) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var rows  = table.querySelectorAll('tr');
    var csv   = [];
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('th, td');
        var line  = [];
        cells.forEach(function(cell) {
            var txt = cell.innerText.replace(/"/g, '""').replace(/\n/g, ' ').trim();
            line.push('"' + txt + '"');
        });
        csv.push(line.join(','));
    });
    var blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var url  = URL.createObjectURL(blob);
    var a    = document.createElement('a');
    a.href   = url; a.download = filename + '.csv'; a.click();
    URL.revokeObjectURL(url);
}
</script>
