<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap purplebox-wrap">
    <h1 class="wp-heading-inline"><?php printf(esc_html__('Contract #%d', 'purplebox-storage'), $contract['id']); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contracts')); ?>" class="page-title-action">
        <?php esc_html_e('← All Contracts', 'purplebox-storage'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['created'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Contract created successfully!', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>

    <?php if (isset($_GET['unit_cancelled'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Unit removed from contract. The unit is now available.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>

    <?php if (isset($_GET['cancel_error'])) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Could not remove unit from contract. Please try again.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>

    <div class="postbox">
        <div class="postbox-header">
            <h2><?php esc_html_e('Contract Details', 'purplebox-storage'); ?></h2>
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
                    <th><?php esc_html_e('Tenant', 'purplebox-storage'); ?></th>
                    <td>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-tenants&action=edit&tenant_id=' . $contract['tenant_id'])); ?>">
                            <?php echo esc_html($contract['tenant_name']); ?>
                        </a>
                        <?php if (!empty($contract['tenant_client_id'])) : ?>
                            <span style="color:#50575e; margin-left:6px;"><?php echo esc_html($contract['tenant_client_id']); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Storage Units', 'purplebox-storage'); ?></th>
                    <td>
                        <?php
                        $unit_count = count($contract['unit_details']);
                    ?>
                        <?php foreach ($contract['unit_details'] as $ud) : ?>
                            <div style="margin-bottom:6px; display:flex; align-items:center; gap:10px;">
                                <div>
                                    <strong><?php echo esc_html($ud['unit_number']); ?></strong>
                                    <?php if (!empty($ud['display_name'])) : ?>
                                        — <?php echo esc_html($ud['display_name']); ?>
                                    <?php endif; ?>
                                    <span style="color:#50575e; margin-left:6px;"><?php echo esc_html($ud['size_category'] . ' · ' . $ud['floor']); ?></span>
                                </div>
                                <?php if ($contract['status'] === 'active' && $unit_count > 1) :
                                    $cancel_unit_url = wp_nonce_url(
                                        admin_url('admin.php?page=purplebox-contracts&action=cancel_unit&contract_id=' . $contract['id'] . '&unit_id=' . $ud['id']),
                                        'purplebox_cancel_unit_' . $contract['id'] . '_' . $ud['id']
                                    );
                                ?>
                                    <a href="<?php echo esc_url($cancel_unit_url); ?>"
                                       class="button button-small"
                                       style="color:#b32d2e; border-color:#b32d2e;"
                                       onclick="return confirm('<?php echo esc_js(sprintf(__('Remove unit %s from this contract? The unit will become available again, and the rest of the contract will remain active.', 'purplebox-storage'), $ud['unit_number'])); ?>')">
                                        <?php esc_html_e('Cancel this unit', 'purplebox-storage'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Move In Date', 'purplebox-storage'); ?></th>
                    <td><?php echo esc_html($contract['move_in_date'] ?? '—'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Move Out Date', 'purplebox-storage'); ?></th>
                    <td>
                        <?php if (empty($contract['move_out_date'])) : ?>
                            <em><?php esc_html_e('Open-ended', 'purplebox-storage'); ?></em>
                        <?php else : ?>
                            <?php echo esc_html($contract['move_out_date']); ?>
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
                    <th><?php esc_html_e('Payment Method', 'purplebox-storage'); ?></th>
                    <td><?php echo esc_html($contract['payment_method'] ?? '—'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Next Payment Date', 'purplebox-storage'); ?></th>
                    <td><?php echo !empty($contract['next_payment_date']) ? esc_html($contract['next_payment_date']) : '<span style="color:#50575e;">—</span>'; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Auto-renew', 'purplebox-storage'); ?></th>
                    <td><?php echo $contract['auto_renew'] ? esc_html__('Yes', 'purplebox-storage') : esc_html__('No', 'purplebox-storage'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Signed Contract', 'purplebox-storage'); ?></th>
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
                    <th><?php esc_html_e('Agreement PDF', 'purplebox-storage'); ?></th>
                    <td>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contracts&action=agreement&contract_id=' . $contract['id'])); ?>"
                           class="button button-primary" target="_blank">
                            <?php esc_html_e('Download Filled Agreement', 'purplebox-storage'); ?>
                        </a>
                        <span style="color:#50575e; margin-left:8px;">
                            <?php esc_html_e('Generates the customer agreement PDF from this contract.', 'purplebox-storage'); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Created', 'purplebox-storage'); ?></th>
                    <td><?php echo esc_html($contract['created_at']); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="submit-row">
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

    <p style="margin-top:16px; color:#50575e; text-align:right; font-size:12px;">
        <?php echo esc_html(sprintf(__('PurpleBox Storage v%s', 'purplebox-storage'), PURPLEBOX_VERSION)); ?>
    </p>
</div>
