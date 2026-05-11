<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap purplebox-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Dashboard', 'purplebox-storage'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contract-new')); ?>" class="page-title-action">
        <?php esc_html_e('+ New Contract', 'purplebox-storage'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-unit-edit')); ?>" class="page-title-action">
        <?php esc_html_e('+ Add Unit', 'purplebox-storage'); ?>
    </a>
    <hr class="wp-header-end">

    <!-- Stats tiles -->
    <div class="at-a-glance">
        <div class="glance-tile">
            <div class="label"><?php esc_html_e('Total Units', 'purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($stats['total']); ?></div>
        </div>
        <div class="glance-tile success">
            <div class="label"><?php esc_html_e('Available', 'purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($stats['available']); ?></div>
        </div>
        <div class="glance-tile">
            <div class="label"><?php esc_html_e('Rented', 'purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($stats['rented']); ?></div>
        </div>
        <div class="glance-tile">
            <div class="label"><?php esc_html_e('Active Tenants', 'purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($stats['active_tenants']); ?></div>
        </div>
        <div class="glance-tile" style="background:#f6f7f7;">
            <div class="label"><?php esc_html_e('Occupancy', 'purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($stats['occupancy']); ?>%</div>
        </div>
    </div>

    <?php
    // Document expiry alerts (next 60 days)
    $expiring_docs = Purplebox_DB::get_expiring_documents(60);
    if (!empty($expiring_docs)) :
    ?>
    <div class="postbox pb-expiry-box" style="margin-bottom:20px; border-left:4px solid #b32d2e;">
        <div class="postbox-header" style="background:#fff8f8;">
            <h2 style="color:#b32d2e;">
                ⚠️ <?php
                    printf(
                        esc_html(_n('%d ID Expiring Soon', '%d IDs Expiring Soon', count($expiring_docs), 'purplebox-storage')),
                        count($expiring_docs)
                    );
                ?>
            </h2>
            <span style="margin-right:12px; font-size:12px; color:#50575e;"><?php esc_html_e('Next 60 days', 'purplebox-storage'); ?></span>
        </div>
        <div class="inside" style="padding:0;">
            <table class="widefat striped" style="border:0;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Tenant', 'purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Emirates ID Expiry', 'purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Passport Expiry', 'purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Contact', 'purplebox-storage'); ?></th>
                        <th style="width:100px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiring_docs as $td) :
                        $phones     = json_decode($td['phones'] ?? '[]', true);
                        $first_phone = is_array($phones) && !empty($phones) ? $phones[0] : '';
                        $wa_num      = preg_replace('/[^0-9]/', '', $first_phone);

                        $eid_days  = $td['eid_days_left'] !== null ? (int) $td['eid_days_left'] : null;
                        $pp_days   = $td['passport_days_left'] !== null ? (int) $td['passport_days_left'] : null;

                        $eid_class = '';
                        if ($eid_days !== null) {
                            $eid_class = $eid_days <= 0 ? 'pb-expired' : ($eid_days <= 14 ? 'pb-critical' : 'pb-warning');
                        }
                        $pp_class = '';
                        if ($pp_days !== null) {
                            $pp_class = $pp_days <= 0 ? 'pb-expired' : ($pp_days <= 14 ? 'pb-critical' : 'pb-warning');
                        }
                    ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-tenants&action=edit&tenant_id=' . $td['id'])); ?>">
                                    <strong><?php echo esc_html($td['full_name']); ?></strong>
                                </a>
                                <span style="color:#50575e; font-size:11px; display:block;"><?php echo esc_html($td['client_id']); ?></span>
                            </td>
                            <td>
                                <?php if (!empty($td['eid_expiry'])) : ?>
                                    <span class="pb-doc-expiry <?php echo esc_attr($eid_class); ?>">
                                        <?php echo esc_html(date('d/m/Y', strtotime($td['eid_expiry']))); ?>
                                        <?php if ($eid_days !== null) : ?>
                                            <em>(<?php echo $eid_days <= 0 ? esc_html__('Expired', 'purplebox-storage') : sprintf(esc_html__('%d days', 'purplebox-storage'), $eid_days); ?>)</em>
                                        <?php endif; ?>
                                    </span>
                                <?php else : ?>
                                    <span style="color:#50575e;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($td['passport_expiry'])) : ?>
                                    <span class="pb-doc-expiry <?php echo esc_attr($pp_class); ?>">
                                        <?php echo esc_html(date('d/m/Y', strtotime($td['passport_expiry']))); ?>
                                        <?php if ($pp_days !== null) : ?>
                                            <em>(<?php echo $pp_days <= 0 ? esc_html__('Expired', 'purplebox-storage') : sprintf(esc_html__('%d days', 'purplebox-storage'), $pp_days); ?>)</em>
                                        <?php endif; ?>
                                    </span>
                                <?php else : ?>
                                    <span style="color:#50575e;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($first_phone) : ?>
                                    <span style="font-size:12px;"><?php echo esc_html($first_phone); ?></span>
                                    <?php if ($wa_num) : ?>
                                        <a href="https://wa.me/<?php echo esc_attr($wa_num); ?>" target="_blank" class="pb-wa-btn pb-wa-btn-sm" title="<?php esc_attr_e('WhatsApp', 'purplebox-storage'); ?>">
                                            <?php esc_html_e('WhatsApp', 'purplebox-storage'); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span style="color:#50575e;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-tenants&action=edit&tenant_id=' . $td['id'])); ?>" class="button button-small">
                                    <?php esc_html_e('Update ID', 'purplebox-storage'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Upcoming Availability -->
    <div class="postbox" style="margin-bottom:20px;">
        <div class="postbox-header">
            <h2><?php esc_html_e('Upcoming Availability', 'purplebox-storage'); ?></h2>
        </div>
        <div class="inside" style="padding:0;">
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:0; border-top:1px solid #dcdcde;">

                <!-- Available Now -->
                <div style="padding:16px 20px; border-right:1px solid #dcdcde;">
                    <h3 style="margin:0 0 12px; font-size:13px; text-transform:uppercase; letter-spacing:.05em; color:#50575e;">
                        <?php esc_html_e('Available Now', 'purplebox-storage'); ?>
                        <span style="background:#00691f; color:#fff; border-radius:10px; padding:1px 7px; font-size:11px; margin-left:6px;">
                            <?php echo count($upcoming['now']); ?>
                        </span>
                    </h3>
                    <?php if (empty($upcoming['now'])) : ?>
                        <p style="color:#50575e; font-style:italic; margin:0;"><?php esc_html_e('No units available', 'purplebox-storage'); ?></p>
                    <?php else : ?>
                        <ul style="margin:0; padding:0; list-style:none;">
                            <?php foreach ($upcoming['now'] as $u) : ?>
                                <li style="padding:5px 0; border-bottom:1px solid #f0f0f1;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-unit-edit&unit_id=' . $u['id'])); ?>">
                                        <strong><?php echo esc_html($u['unit_number']); ?></strong>
                                    </a>
                                    <?php if (!empty($u['display_name'])) : ?>
                                        <em style="color:#50575e; margin-left:4px; font-style:italic;"><?php echo esc_html($u['display_name']); ?></em>
                                    <?php endif; ?>
                                    <span style="color:#50575e; font-size:11px; display:block;"><?php echo esc_html($u['size_category']); ?> &middot; <?php echo esc_html($u['floor']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Ending This Week -->
                <div style="padding:16px 20px; border-right:1px solid #dcdcde;">
                    <h3 style="margin:0 0 12px; font-size:13px; text-transform:uppercase; letter-spacing:.05em; color:#50575e;">
                        <?php esc_html_e('Free This Week', 'purplebox-storage'); ?>
                        <span style="background:#8a6500; color:#fff; border-radius:10px; padding:1px 7px; font-size:11px; margin-left:6px;">
                            <?php echo count($upcoming['week']); ?>
                        </span>
                    </h3>
                    <?php if (empty($upcoming['week'])) : ?>
                        <p style="color:#50575e; font-style:italic; margin:0;"><?php esc_html_e('None this week', 'purplebox-storage'); ?></p>
                    <?php else : ?>
                        <ul style="margin:0; padding:0; list-style:none;">
                            <?php foreach ($upcoming['week'] as $u) : ?>
                                <li style="padding:5px 0; border-bottom:1px solid #f0f0f1;">
                                    <strong><?php echo esc_html($u['unit_number']); ?></strong>
                                    <span style="color:#50575e; font-size:11px; display:block;"><?php echo esc_html($u['size_category']); ?> &middot; <?php echo esc_html($u['floor']); ?></span>
                                    <span style="font-size:11px; color:#8a6500;"><?php printf(esc_html__('Free: %s', 'purplebox-storage'), esc_html(date('d/m/Y', strtotime($u['available_date'])))); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Ending This Month -->
                <div style="padding:16px 20px;">
                    <h3 style="margin:0 0 12px; font-size:13px; text-transform:uppercase; letter-spacing:.05em; color:#50575e;">
                        <?php esc_html_e('Free This Month', 'purplebox-storage'); ?>
                        <span style="background:#1e4ea1; color:#fff; border-radius:10px; padding:1px 7px; font-size:11px; margin-left:6px;">
                            <?php echo count($upcoming['month']); ?>
                        </span>
                    </h3>
                    <?php if (empty($upcoming['month'])) : ?>
                        <p style="color:#50575e; font-style:italic; margin:0;"><?php esc_html_e('None this month', 'purplebox-storage'); ?></p>
                    <?php else : ?>
                        <ul style="margin:0; padding:0; list-style:none;">
                            <?php foreach ($upcoming['month'] as $u) : ?>
                                <li style="padding:5px 0; border-bottom:1px solid #f0f0f1;">
                                    <strong><?php echo esc_html($u['unit_number']); ?></strong>
                                    <span style="color:#50575e; font-size:11px; display:block;"><?php echo esc_html($u['size_category']); ?> &middot; <?php echo esc_html($u['floor']); ?></span>
                                    <span style="font-size:11px; color:#1e4ea1;"><?php printf(esc_html__('Free: %s', 'purplebox-storage'), esc_html(date('d/m/Y', strtotime($u['available_date'])))); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <div class="dashboard-widgets">
        <!-- Availability by Size -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e('Availability by Size', 'purplebox-storage'); ?></h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-units')); ?>" class="button button-small"><?php esc_html_e('View all', 'purplebox-storage'); ?></a>
            </div>
            <div class="inside">
                <?php if (!empty($availability)) : ?>
                    <?php foreach ($availability as $row) :
                        $total        = (int) $row['total'];
                        $avail        = (int) $row['available'];
                        $rented_count = $total - $avail;
                        $pct_rented   = $total > 0 ? round(($rented_count / $total) * 100) : 0;

                        if ($avail === 0) { $tag_class = 'full'; $tag_label = __('Full', 'purplebox-storage'); }
                        elseif ($avail <= 2) { $tag_class = 'low'; $tag_label = __('Low', 'purplebox-storage'); }
                        else { $tag_class = 'ok'; $tag_label = __('OK', 'purplebox-storage'); }
                    ?>
                        <div class="avail-row" style="flex-wrap:wrap; align-items:center;">
                            <span class="size"><?php echo esc_html($row['size_category']); ?></span>
                            <span class="ratio"><?php echo esc_html($avail . ' / ' . $total); ?></span>
                            <div class="bar">
                                <div class="bar-fill <?php echo $pct_rented >= 100 ? 'full' : ($pct_rented >= 80 ? 'low' : ''); ?>" style="width:<?php echo esc_attr($pct_rented); ?>%"></div>
                            </div>
                            <span class="tag <?php echo esc_attr($tag_class); ?>"><?php echo esc_html($tag_label); ?></span>

                            <?php if (!empty($row['units'])) : ?>
                            <div style="width:100%; margin-top:8px; padding-left:4px; display:flex; flex-wrap:wrap; gap:6px;">
                                <?php foreach ($row['units'] as $u) :
                                    $u_avail = (int) ($u['available'] ?? 0);
                                    $qty     = (int) ($u['quantity'] ?? 1);
                                    $full    = ($u_avail === 0);
                                    $low     = (!$full && $u_avail <= max(1, (int)($qty * 0.25)));
                                    if ($full) {
                                        $bg = '#fce8e6'; $fg = '#b32d2e'; $dot = '#b32d2e';
                                    } elseif ($low) {
                                        $bg = '#fef3cd'; $fg = '#7a5200'; $dot = '#8a6500';
                                    } else {
                                        $bg = '#e8f5e9'; $fg = '#1b5e20'; $dot = '#00691f';
                                    }
                                ?>
                                    <span style="display:inline-flex; flex-direction:column; padding:4px 10px; border-radius:10px; font-size:12px; background:<?php echo $bg; ?>; color:<?php echo $fg; ?>; line-height:1.4;">
                                        <span style="display:flex; align-items:center; gap:5px;">
                                            <span style="font-size:9px; color:<?php echo $dot; ?>;">●</span>
                                            <strong><?php echo esc_html($u['unit_number']); ?></strong>
                                            <span style="opacity:.55; font-size:11px;"><?php echo esc_html($u['floor']); ?></span>
                                            <?php if ($qty > 1) : ?>
                                                <span style="font-size:11px; font-weight:600; margin-left:2px;"><?php echo $u_avail . '/' . $qty; ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if (!empty($u['display_name'])) : ?>
                                            <em style="font-style:italic; font-size:11px; opacity:.75; padding-left:14px;"><?php echo esc_html($u['display_name']); ?></em>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p style="color:#50575e;"><?php esc_html_e('No units added yet.', 'purplebox-storage'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <!-- Quick Stats -->
            <div class="postbox">
                <div class="postbox-header"><h2><?php esc_html_e('Quick Stats', 'purplebox-storage'); ?></h2></div>
                <div class="inside">
                    <p>
                        <strong><?php esc_html_e('Occupancy', 'purplebox-storage'); ?></strong>
                        &middot; <?php echo esc_html($stats['occupancy']); ?>%
                    </p>
                    <p>
                        <strong><?php esc_html_e('Avg contract length', 'purplebox-storage'); ?></strong>
                        &middot; <?php echo esc_html($stats['avg_contract_months']); ?> <?php esc_html_e('months', 'purplebox-storage'); ?>
                    </p>
                    <hr style="border:0; border-top:1px solid #f0f0f1; margin:12px 0;">
                    <p style="margin:0;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-reports')); ?>">
                            <?php esc_html_e('📊 View Reports →', 'purplebox-storage'); ?>
                        </a>
                    </p>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="postbox">
                <div class="postbox-header"><h2><?php esc_html_e('Recent Activity', 'purplebox-storage'); ?></h2></div>
                <div class="inside">
                    <?php if (!empty($activity)) : ?>
                        <ul class="activity-list">
                            <?php foreach ($activity as $item) : ?>
                                <li>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-tenants&action=edit&tenant_id=' . $item['tenant_id'])); ?>">
                                        <?php echo esc_html($item['tenant_name']); ?>
                                    </a>
                                    <?php if ($item['status'] === 'active') : ?>
                                        <?php esc_html_e('rented', 'purplebox-storage'); ?>
                                    <?php else : ?>
                                        <?php esc_html_e('ended contract for', 'purplebox-storage'); ?>
                                    <?php endif; ?>
                                    <strong><?php echo esc_html(implode(', ', $item['unit_labels'] ?? [$item['unit_numbers']])); ?></strong>
                                    <span class="time">
                                        <?php echo esc_html(human_time_diff(strtotime($item['created_at']), current_time('timestamp')) . ' ' . __('ago', 'purplebox-storage')); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p style="color:#50575e;"><?php esc_html_e('No activity yet.', 'purplebox-storage'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
