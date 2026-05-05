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
    </div>

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
                                    <strong><?php echo esc_html($u['unit_number']); ?></strong>
                                    <?php if (!empty($u['display_name'])) : ?>
                                        <span style="margin-left:6px;"><?php echo esc_html($u['display_name']); ?></span>
                                    <?php else : ?>
                                        <span style="color:#50575e; margin-left:6px;"><?php echo esc_html($u['size_category']); ?></span>
                                    <?php endif; ?>
                                    <span style="color:#50575e; font-size:11px; display:block;"><?php echo esc_html($u['floor']); ?></span>
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
                                    <?php if (!empty($u['display_name'])) : ?>
                                        <span style="margin-left:6px;"><?php echo esc_html($u['display_name']); ?></span>
                                    <?php else : ?>
                                        <span style="color:#50575e; margin-left:6px;"><?php echo esc_html($u['size_category']); ?></span>
                                    <?php endif; ?>
                                    <span style="color:#50575e; font-size:11px; display:block;"><?php echo esc_html($u['floor']); ?></span>
                                    <span style="font-size:11px; color:#8a6500;"><?php printf(esc_html__('Free: %s', 'purplebox-storage'), esc_html($u['available_date'])); ?></span>
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
                                    <?php if (!empty($u['display_name'])) : ?>
                                        <span style="margin-left:6px;"><?php echo esc_html($u['display_name']); ?></span>
                                    <?php else : ?>
                                        <span style="color:#50575e; margin-left:6px;"><?php echo esc_html($u['size_category']); ?></span>
                                    <?php endif; ?>
                                    <span style="color:#50575e; font-size:11px; display:block;"><?php echo esc_html($u['floor']); ?></span>
                                    <span style="font-size:11px; color:#1e4ea1;"><?php printf(esc_html__('Free: %s', 'purplebox-storage'), esc_html($u['available_date'])); ?></span>
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
                                    $avail = (int) ($u['available'] ?? 0);
                                    $qty   = (int) ($u['quantity'] ?? 1);
                                    $full  = ($avail === 0);
                                    $low   = (!$full && $avail <= max(1, (int)($qty * 0.25)));
                                    if ($full) {
                                        $bg = '#fce8e6'; $fg = '#b32d2e'; $dot = '#b32d2e';
                                    } elseif ($low) {
                                        $bg = '#fef3cd'; $fg = '#7a5200'; $dot = '#8a6500';
                                    } else {
                                        $bg = '#e8f5e9'; $fg = '#1b5e20'; $dot = '#00691f';
                                    }
                                ?>
                                    <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:12px; font-size:12px; background:<?php echo $bg; ?>; color:<?php echo $fg; ?>;">
                                        <span style="font-size:9px; color:<?php echo $dot; ?>;">●</span>
                                        <strong><?php echo esc_html($u['unit_number']); ?></strong>
                                        <?php if (!empty($u['display_name'])) : ?>
                                            <span style="opacity:.8;"><?php echo esc_html($u['display_name']); ?></span>
                                        <?php endif; ?>
                                        <span style="opacity:.6; font-size:11px;"><?php echo esc_html($u['floor']); ?></span>
                                        <?php if ($qty > 1) : ?>
                                            <span style="font-size:11px; font-weight:600;"><?php echo $avail . '/' . $qty; ?></span>
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
                                    <?php echo esc_html($item['tenant_name']); ?>
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
