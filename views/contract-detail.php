<?php if (!defined('ABSPATH')) exit;
$fmt = function($d) { return $d ? date('d/m/Y', strtotime($d)) : '—'; };
$phones = json_decode($tenant['phones'] ?? '[]', true);
if (!is_array($phones)) $phones = [];
?>
<div class="wrap purplebox-wrap">
    <h1 class="wp-heading-inline"><?php printf(esc_html__('Contract #%d', 'purplebox-storage'), $contract['id']); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contracts')); ?>" class="page-title-action">
        <?php esc_html_e('← All Contracts', 'purplebox-storage'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contracts&action=edit&contract_id=' . $contract['id'])); ?>" class="page-title-action">
        <?php esc_html_e('✏ Edit', 'purplebox-storage'); ?>
    </a>
    <?php
    $renew_url = admin_url('admin.php?page=purplebox-contract-new&renew_from=' . $contract['id']);
    ?>
    <a href="<?php echo esc_url($renew_url); ?>" class="page-title-action" style="background:#00691f; color:#fff; border-color:#00691f;">
        <?php esc_html_e('↻ Renew Contract', 'purplebox-storage'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['created'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Contract created successfully!', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Contract updated successfully.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>
    <?php if (isset($_GET['unit_cancelled'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Unit removed from contract. The unit is now available.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>
    <?php if (isset($_GET['cancel_error'])) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Could not remove unit from contract. Please try again.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>

    <!-- Row 1: Contract Info + Tenant Info -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
        <!-- Contract Info -->
        <div class="postbox" style="margin:0;">
            <div class="postbox-header">
                <h2><?php esc_html_e('Contract Info', 'purplebox-storage'); ?></h2>
                <span class="pill <?php echo $contract['status'] === 'active' ? 'available' : 'ended'; ?>" style="margin-right:12px;">
                    <?php echo esc_html(ucfirst($contract['status'])); ?>
                </span>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e('Contract ID', 'purplebox-storage'); ?></th>
                        <td><strong>#<?php echo esc_html($contract['id']); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Status', 'purplebox-storage'); ?></th>
                        <td>
                            <span class="pill <?php echo $contract['status'] === 'active' ? 'available' : 'ended'; ?>">
                                <?php echo esc_html(ucfirst($contract['status'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Move In', 'purplebox-storage'); ?></th>
                        <td><?php echo esc_html($fmt($contract['move_in_date'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Move Out', 'purplebox-storage'); ?></th>
                        <td>
                            <?php if (empty($contract['move_out_date'])) : ?>
                                <em><?php esc_html_e('Open-ended', 'purplebox-storage'); ?></em>
                            <?php else : ?>
                                <strong><?php echo esc_html($fmt($contract['move_out_date'])); ?></strong>
                                <?php
                                $days_left = (int)((strtotime($contract['move_out_date']) - strtotime(current_time('Y-m-d'))) / 86400);
                                if ($contract['status'] === 'active' && $days_left <= 15) :
                                    $dc = $days_left <= 0 ? '#b32d2e' : ($days_left <= 7 ? '#b32d2e' : '#8a6500');
                                ?>
                                    <span style="color:<?php echo $dc; ?>; margin-left:8px; font-weight:600;">
                                        (<?php echo $days_left <= 0 ? esc_html__('Expired', 'purplebox-storage') : sprintf('%d %s', $days_left, esc_html__('days left', 'purplebox-storage')); ?>)
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (!empty($contract['duration_weeks'])) : ?>
                    <tr>
                        <th><?php esc_html_e('Duration', 'purplebox-storage'); ?></th>
                        <td><?php printf(esc_html__('%d weeks', 'purplebox-storage'), (int) $contract['duration_weeks']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php esc_html_e('Auto-renew', 'purplebox-storage'); ?></th>
                        <td><?php echo $contract['auto_renew'] ? esc_html__('Yes', 'purplebox-storage') : esc_html__('No', 'purplebox-storage'); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Created', 'purplebox-storage'); ?></th>
                        <td style="color:#50575e;"><?php echo esc_html($fmt($contract['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tenant Info -->
        <div class="postbox" style="margin:0;">
            <div class="postbox-header">
                <h2><?php esc_html_e('Tenant Info', 'purplebox-storage'); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e('Name', 'purplebox-storage'); ?></th>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-tenants&action=edit&tenant_id=' . $contract['tenant_id'])); ?>">
                                <strong><?php echo esc_html($contract['tenant_name']); ?></strong>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Client ID', 'purplebox-storage'); ?></th>
                        <td><?php echo esc_html($contract['tenant_client_id'] ?? '—'); ?></td>
                    </tr>
                    <?php if (!empty($phones)) :
                        $wa_num = preg_replace('/[^0-9]/', '', $phones[0]);
                    ?>
                    <tr>
                        <th><?php esc_html_e('Phone', 'purplebox-storage'); ?></th>
                        <td>
                            <?php echo esc_html(implode(', ', $phones)); ?>
                            <?php if ($wa_num) : ?>
                                <a href="https://wa.me/<?php echo esc_attr($wa_num); ?>" target="_blank" class="pb-wa-btn" style="margin-left:10px;">
                                    <?php esc_html_e('WhatsApp', 'purplebox-storage'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($tenant['email'])) : ?>
                    <tr>
                        <th><?php esc_html_e('Email', 'purplebox-storage'); ?></th>
                        <td><?php echo esc_html($tenant['email']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($tenant['emirates_id'])) : ?>
                    <tr>
                        <th><?php esc_html_e('Emirates ID', 'purplebox-storage'); ?></th>
                        <td><?php echo esc_html($tenant['emirates_id']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($tenant['nationality'])) : ?>
                    <tr>
                        <th><?php esc_html_e('Nationality', 'purplebox-storage'); ?></th>
                        <td><?php echo esc_html($tenant['nationality']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Row 2: Storage Units (full-width) -->
    <div class="postbox">
        <div class="postbox-header">
            <h2><?php esc_html_e('Storage Units', 'purplebox-storage'); ?></h2>
            <span style="margin-right:12px; color:#50575e; font-size:13px;">
                <?php printf(esc_html__('%d unit(s)', 'purplebox-storage'), count($contract['unit_details'])); ?>
            </span>
        </div>
        <div class="inside" style="padding:0;">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Unit #', 'purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Name / Label', 'purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Size', 'purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Floor', 'purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Price (AED)', 'purplebox-storage'); ?></th>
                        <?php if ($contract['status'] === 'active' && count($contract['unit_details']) > 1) : ?>
                            <th style="width:130px;"></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contract['unit_details'] as $ud) :
                        $price_display = !empty($ud['discounted_price'])
                            ? '<del style="color:#50575e;">AED ' . number_format((float)$ud['price'], 2) . '</del> <strong style="color:#00691f;">AED ' . number_format((float)$ud['discounted_price'], 2) . '</strong>'
                            : (!empty($ud['price']) ? 'AED ' . number_format((float)$ud['price'], 2) : '—');
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($ud['unit_number']); ?></strong></td>
                            <td><?php echo esc_html($ud['display_name'] ?? '—'); ?></td>
                            <td><?php echo esc_html($ud['size_category']); ?></td>
                            <td><?php echo esc_html($ud['floor']); ?></td>
                            <td><?php echo $price_display; ?></td>
                            <?php if ($contract['status'] === 'active' && count($contract['unit_details']) > 1) :
                                $cancel_unit_url = wp_nonce_url(
                                    admin_url('admin.php?page=purplebox-contracts&action=cancel_unit&contract_id=' . $contract['id'] . '&unit_id=' . $ud['id']),
                                    'purplebox_cancel_unit_' . $contract['id'] . '_' . $ud['id']
                                );
                            ?>
                                <td>
                                    <a href="<?php echo esc_url($cancel_unit_url); ?>"
                                       class="button button-small"
                                       style="color:#b32d2e; border-color:#b32d2e;"
                                       onclick="return confirm('<?php echo esc_js(sprintf(__('Remove unit %s from this contract?', 'purplebox-storage'), $ud['unit_number'])); ?>')">
                                        <?php esc_html_e('Cancel unit', 'purplebox-storage'); ?>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Row 3: Payment + Documents -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
        <!-- Payment Details -->
        <div class="postbox" style="margin:0;">
            <div class="postbox-header"><h2><?php esc_html_e('Payment Details', 'purplebox-storage'); ?></h2></div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e('Method', 'purplebox-storage'); ?></th>
                        <td><?php echo esc_html($contract['payment_method'] ?? '—'); ?></td>
                    </tr>
                    <?php if (!empty($contract['first_payment_date'])) : ?>
                    <tr>
                        <th><?php esc_html_e('First Payment', 'purplebox-storage'); ?></th>
                        <td><?php echo esc_html($fmt($contract['first_payment_date'])); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php esc_html_e('Next Payment', 'purplebox-storage'); ?></th>
                        <td><?php echo !empty($contract['next_payment_date']) ? esc_html($fmt($contract['next_payment_date'])) : '<span style="color:#50575e;">—</span>'; ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Documents -->
        <div class="postbox" style="margin:0;">
            <div class="postbox-header"><h2><?php esc_html_e('Documents', 'purplebox-storage'); ?></h2></div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e('Signed PDF', 'purplebox-storage'); ?></th>
                        <td>
                            <?php if (!empty($contract['signed_pdf_path'])) : ?>
                                <a href="<?php echo esc_url($contract['signed_pdf_path']); ?>" target="_blank" class="button button-small">
                                    <?php esc_html_e('View Signed PDF', 'purplebox-storage'); ?>
                                </a>
                            <?php else : ?>
                                <span style="color:#50575e;"><?php esc_html_e('No PDF uploaded', 'purplebox-storage'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Agreement', 'purplebox-storage'); ?></th>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contracts&action=agreement&contract_id=' . $contract['id'])); ?>"
                               class="button button-primary" target="_blank">
                                <?php esc_html_e('Download Filled Agreement', 'purplebox-storage'); ?>
                            </a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Row 4: Notes -->
    <?php if (!empty($contract['notes'])) : ?>
    <div class="postbox" style="margin-bottom:16px;">
        <div class="postbox-header"><h2><?php esc_html_e('Internal Notes', 'purplebox-storage'); ?></h2></div>
        <div class="inside">
            <p style="white-space:pre-wrap; margin:0;"><?php echo esc_html($contract['notes']); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Row 5: Actions -->
    <div class="submit-row" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contracts&action=edit&contract_id=' . $contract['id'])); ?>" class="button button-primary">
            <?php esc_html_e('✏ Edit Contract', 'purplebox-storage'); ?>
        </a>

        <a href="<?php echo esc_url($renew_url); ?>" class="button" style="background:#00691f; color:#fff; border-color:#00691f;">
            <?php esc_html_e('↻ Renew Contract', 'purplebox-storage'); ?>
        </a>

        <?php if ($contract['status'] === 'active') :
            $end_url = wp_nonce_url(
                admin_url('admin.php?page=purplebox-contracts&action=end&contract_id=' . $contract['id']),
                'purplebox_end_contract_' . $contract['id']
            );
        ?>
            <a href="<?php echo esc_url($end_url); ?>" class="button"
               onclick="return confirm('<?php esc_attr_e('Are you sure you want to end this contract? The units will be returned to available inventory.', 'purplebox-storage'); ?>');">
                <?php esc_html_e('End Contract', 'purplebox-storage'); ?>
            </a>
        <?php endif; ?>

        <?php
        $delete_url = wp_nonce_url(
            admin_url('admin.php?page=purplebox-contracts&action=delete&contract_id=' . $contract['id']),
            'purplebox_delete_contract_' . $contract['id']
        );
        ?>
        <a href="<?php echo esc_url($delete_url); ?>" class="button"
           style="color:#b32d2e; border-color:#b32d2e; margin-left:auto;"
           onclick="return confirm('<?php esc_attr_e('Delete this contract permanently?', 'purplebox-storage'); ?>');">
            <?php esc_html_e('Delete Contract', 'purplebox-storage'); ?>
        </a>
    </div>
</div>
