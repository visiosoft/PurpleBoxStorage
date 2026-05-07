<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap purplebox-wrap">
    <h1 class="wp-heading-inline">
        <?php printf(esc_html__('Edit Contract #%d', 'purplebox-storage'), $contract['id']); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contracts&action=view&contract_id=' . $contract['id'])); ?>" class="page-title-action">
        <?php esc_html_e('← Back to Contract', 'purplebox-storage'); ?>
    </a>
    <hr class="wp-header-end">

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="purplebox_action" value="update_contract">
        <input type="hidden" name="contract_id"     value="<?php echo esc_attr($contract['id']); ?>">
        <?php wp_nonce_field('purplebox_update_contract', 'purplebox_nonce'); ?>

        <!-- Read-only info -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e('Contract Info', 'purplebox-storage'); ?></h2>
                <span class="pill <?php echo $contract['status'] === 'active' ? 'active' : 'ended'; ?>" style="margin-right:12px;">
                    <?php echo esc_html(ucfirst($contract['status'])); ?>
                </span>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e('Tenant', 'purplebox-storage'); ?></th>
                        <td>
                            <strong><?php echo esc_html($contract['tenant_name'] ?? '—'); ?></strong>
                            <?php if (!empty($contract['tenant_client_id'])) : ?>
                                <span style="color:#50575e; margin-left:6px;"><?php echo esc_html($contract['tenant_client_id']); ?></span>
                            <?php endif; ?>
                            <p class="description"><?php esc_html_e('Tenant cannot be changed. End this contract and create a new one if needed.', 'purplebox-storage'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Storage Units', 'purplebox-storage'); ?></th>
                        <td>
                            <?php foreach ($contract['unit_details'] as $ud) : ?>
                                <div style="margin-bottom:4px;">
                                    <strong><?php echo esc_html($ud['unit_number']); ?></strong>
                                    <?php if (!empty($ud['display_name'])) : ?>
                                        — <?php echo esc_html($ud['display_name']); ?>
                                    <?php endif; ?>
                                    <span style="color:#50575e; margin-left:6px;"><?php echo esc_html($ud['size_category'] . ' · ' . $ud['floor']); ?></span>
                                </div>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('Units cannot be changed here. Use "Cancel this unit" on the contract detail page.', 'purplebox-storage'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Editable fields -->
        <div class="postbox">
            <div class="postbox-header"><h2><?php esc_html_e('Dates & Duration', 'purplebox-storage'); ?></h2></div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="move_in_date"><?php esc_html_e('Move In Date', 'purplebox-storage'); ?> <span class="required">*</span></label></th>
                        <td>
                            <input type="date" id="move_in_date" name="move_in_date"
                                   value="<?php echo esc_attr($contract['move_in_date'] ?? ''); ?>"
                                   required class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Open-ended', 'purplebox-storage'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="open_ended" name="open_ended" value="1"
                                    <?php checked(empty($contract['move_out_date'])); ?>
                                    onchange="document.getElementById('move_out_wrap').style.display = this.checked ? 'none' : 'block';">
                                <?php esc_html_e('No fixed end date', 'purplebox-storage'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr id="move_out_wrap" style="<?php echo empty($contract['move_out_date']) ? 'display:none;' : ''; ?>">
                        <th><label for="move_out_date"><?php esc_html_e('Move Out / Expiry Date', 'purplebox-storage'); ?></label></th>
                        <td>
                            <input type="date" id="move_out_date" name="move_out_date"
                                   value="<?php echo esc_attr($contract['move_out_date'] ?? ''); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="duration_weeks"><?php esc_html_e('Duration (weeks)', 'purplebox-storage'); ?></label></th>
                        <td>
                            <input type="number" id="duration_weeks" name="duration_weeks"
                                   value="<?php echo esc_attr($contract['duration_weeks'] ?? ''); ?>"
                                   min="1" class="small-text" placeholder="e.g. 4">
                            <p class="description"><?php esc_html_e('Optional. Auto-calculated from dates if left blank.', 'purplebox-storage'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="postbox">
            <div class="postbox-header"><h2><?php esc_html_e('Payment & Status', 'purplebox-storage'); ?></h2></div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="payment_method"><?php esc_html_e('Payment Method', 'purplebox-storage'); ?></label></th>
                        <td>
                            <select id="payment_method" name="payment_method">
                                <?php
                                $methods = ['Cash', 'Bank Transfer', 'Cheque', 'Card', 'Other'];
                                $current_method = $contract['payment_method'] ?? 'Cash';
                                foreach ($methods as $m) {
                                    printf('<option value="%s" %s>%s</option>', esc_attr($m), selected($current_method, $m, false), esc_html($m));
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="next_payment_date"><?php esc_html_e('Next Payment Date', 'purplebox-storage'); ?></label></th>
                        <td>
                            <input type="date" id="next_payment_date" name="next_payment_date"
                                   value="<?php echo esc_attr($contract['next_payment_date'] ?? ''); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="auto_renew"><?php esc_html_e('Auto-renew', 'purplebox-storage'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_renew" name="auto_renew" value="1"
                                    <?php checked(!empty($contract['auto_renew'])); ?>>
                                <?php esc_html_e('Automatically renew when contract expires', 'purplebox-storage'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="status"><?php esc_html_e('Status', 'purplebox-storage'); ?></label></th>
                        <td>
                            <select id="status" name="status">
                                <option value="active" <?php selected($contract['status'], 'active'); ?>><?php esc_html_e('Active',     'purplebox-storage'); ?></option>
                                <option value="ended"  <?php selected($contract['status'], 'ended');  ?>><?php esc_html_e('Ended',      'purplebox-storage'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Setting to "Ended" frees up the unit(s) in inventory.', 'purplebox-storage'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="postbox">
            <div class="postbox-header"><h2><?php esc_html_e('Signed Contract PDF', 'purplebox-storage'); ?></h2></div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e('Current PDF', 'purplebox-storage'); ?></th>
                        <td>
                            <?php if (!empty($contract['signed_pdf_path'])) : ?>
                                <a href="<?php echo esc_url($contract['signed_pdf_path']); ?>" target="_blank" class="button button-small">
                                    <?php esc_html_e('View Current PDF', 'purplebox-storage'); ?>
                                </a>
                            <?php else : ?>
                                <span style="color:#50575e;"><?php esc_html_e('No PDF uploaded yet.', 'purplebox-storage'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="signed_pdf"><?php esc_html_e('Replace PDF', 'purplebox-storage'); ?></label></th>
                        <td>
                            <input type="file" id="signed_pdf" name="signed_pdf" accept=".pdf">
                            <p class="description"><?php esc_html_e('Upload a new signed PDF to replace the existing one. Leave blank to keep the current file.', 'purplebox-storage'); ?></p>
                        </td>
                    </tr>
                </table>

                <div class="submit-row">
                    <?php submit_button(__('Save Changes', 'purplebox-storage'), 'primary', 'submit', false); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contracts&action=view&contract_id=' . $contract['id'])); ?>" class="button">
                        <?php esc_html_e('Cancel', 'purplebox-storage'); ?>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
