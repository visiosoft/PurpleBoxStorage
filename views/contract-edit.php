<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap purplebox-wrap">
    <h1 class="wp-heading-inline">
        <?php printf(esc_html__('Edit Contract #%d', 'purplebox-storage'), $contract['id']); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contracts&action=view&contract_id=' . $contract['id'])); ?>" class="page-title-action">
        <?php esc_html_e('← Back to Contract', 'purplebox-storage'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['error']) && $_GET['error'] === 'no_availability') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('One or more newly selected units are already rented. Please choose different units.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="purplebox_action" value="update_contract">
        <input type="hidden" name="contract_id"     value="<?php echo esc_attr($contract['id']); ?>">
        <?php wp_nonce_field('purplebox_update_contract', 'purplebox_nonce'); ?>

        <!-- Tenant & Units -->
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
                        <th><label for="tenant_id"><?php esc_html_e('Tenant', 'purplebox-storage'); ?> <span class="required">*</span></label></th>
                        <td>
                            <select id="tenant_id" name="tenant_id" required style="min-width:300px;">
                                <?php foreach ($tenants as $t) :
                                    $phones = json_decode($t['phones'] ?? '[]', true);
                                    $phone  = is_array($phones) && !empty($phones) ? $phones[0] : '';
                                    $label  = $t['client_id'] . ' — ' . $t['full_name'];
                                    if ($phone) $label .= ' (' . $phone . ')';
                                ?>
                                    <option value="<?php echo esc_attr($t['id']); ?>" <?php selected((int) $contract['tenant_id'], (int) $t['id']); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-tenant-edit')); ?>" target="_blank" style="margin-left:8px;">
                                <?php esc_html_e('+ Add new tenant', 'purplebox-storage'); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Storage Units', 'purplebox-storage'); ?> <span class="required">*</span></th>
                        <td>
                            <?php if (empty($selectable_units)) : ?>
                                <div class="notice notice-warning inline"><p><?php esc_html_e('No units available.', 'purplebox-storage'); ?></p></div>
                            <?php else : ?>
                                <div style="max-height:250px; overflow-y:auto; border:1px solid #c3c4c7; border-radius:4px; padding:0;">
                                    <table class="widefat striped" style="border:0;">
                                        <thead>
                                            <tr>
                                                <th style="width:30px;"></th>
                                                <th><?php esc_html_e('Unit #', 'purplebox-storage'); ?></th>
                                                <th><?php esc_html_e('Name / Label', 'purplebox-storage'); ?></th>
                                                <th><?php esc_html_e('Size', 'purplebox-storage'); ?></th>
                                                <th><?php esc_html_e('Floor', 'purplebox-storage'); ?></th>
                                                <th><?php esc_html_e('Price (AED)', 'purplebox-storage'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($selectable_units as $su) :
                                                $is_checked = in_array((int) $su['id'], $current_unit_ids);
                                                $price_display = !empty($su['discounted_price'])
                                                    ? '<del style="color:#50575e;">AED ' . number_format((float)$su['price'], 2) . '</del> <strong style="color:#00691f;">AED ' . number_format((float)$su['discounted_price'], 2) . '</strong>'
                                                    : (!empty($su['price']) ? 'AED ' . number_format((float)$su['price'], 2) : '—');
                                            ?>
                                                <tr>
                                                    <td><input type="checkbox" name="unit_ids[]" value="<?php echo esc_attr($su['id']); ?>" <?php checked($is_checked); ?>></td>
                                                    <td><strong><?php echo esc_html($su['unit_number']); ?></strong></td>
                                                    <td><?php echo esc_html($su['display_name'] ?? '—'); ?></td>
                                                    <td><?php echo esc_html($su['size_category']); ?></td>
                                                    <td><?php echo esc_html($su['floor']); ?></td>
                                                    <td><?php echo $price_display; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="description"><?php esc_html_e('Select one or more units for this contract. Units already on this contract are pre-checked.', 'purplebox-storage'); ?></p>
                            <?php endif; ?>
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
            <div class="postbox-header"><h2><?php esc_html_e('Internal Notes', 'purplebox-storage'); ?></h2></div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="notes"><?php esc_html_e('Notes', 'purplebox-storage'); ?></label></th>
                        <td>
                            <textarea id="notes" name="notes" rows="4" class="large-text" placeholder="<?php esc_attr_e('e.g. Special access arrangements, deposit notes, follow-up required…', 'purplebox-storage'); ?>"><?php echo esc_textarea($contract['notes'] ?? ''); ?></textarea>
                            <p class="description"><?php esc_html_e('Visible to admin only. Not printed on agreements.', 'purplebox-storage'); ?></p>
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
