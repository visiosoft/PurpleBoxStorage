<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap purplebox-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('New Rental Contract', 'purplebox-storage'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contracts')); ?>" class="page-title-action">
        <?php esc_html_e('← All Contracts', 'purplebox-storage'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['error']) && $_GET['error'] === 'missing_fields') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Please fill in all required fields and select at least one unit.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'no_availability') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('One or more selected units are no longer available.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>

    <div class="wizard-steps">
        <div class="wizard-step active" data-step="1"><span class="num">1</span> <?php esc_html_e('Tenant', 'purplebox-storage'); ?></div>
        <div class="wizard-step" data-step="2"><span class="num">2</span> <?php esc_html_e('Units', 'purplebox-storage'); ?></div>
        <div class="wizard-step" data-step="3"><span class="num">3</span> <?php esc_html_e('Terms', 'purplebox-storage'); ?></div>
        <div class="wizard-step" data-step="4"><span class="num">4</span> <?php esc_html_e('Review', 'purplebox-storage'); ?></div>
    </div>

    <form method="post" enctype="multipart/form-data" id="purplebox-contract-form">
        <input type="hidden" name="purplebox_action" value="save_contract">
        <?php wp_nonce_field('purplebox_save_contract', 'purplebox_nonce'); ?>

        <!-- Step 1: Tenant -->
        <div class="wizard-panel active" data-step="1">
            <div class="postbox">
                <div class="postbox-header"><h2><?php esc_html_e('Step 1 — Select Tenant', 'purplebox-storage'); ?></h2></div>
                <div class="inside">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th><label><?php esc_html_e('Tenant', 'purplebox-storage'); ?> <span class="required">*</span></label></th>
                            <td>
                                <select name="tenant_id" id="purplebox-tenant-select" required style="max-width:400px;">
                                    <option value=""><?php esc_html_e('— Select a tenant —', 'purplebox-storage'); ?></option>
                                    <?php foreach ($tenants as $t) :
                                        $phones = json_decode($t['phones'] ?? '[]', true);
                                        $first_phone = !empty($phones) ? $phones[0] : '';
                                    ?>
                                        <option value="<?php echo esc_attr($t['id']); ?>" <?php selected($preselected_tenant_id ?? 0, $t['id']); ?>>
                                            <?php echo esc_html($t['client_id'] . ' — ' . $t['full_name'] . ($first_phone ? ' (' . $first_phone . ')' : '')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Or', 'purplebox-storage'); ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-tenants&action=edit&tenant_id=0')); ?>" target="_blank">
                                        <?php esc_html_e('add a new tenant ↗', 'purplebox-storage'); ?>
                                    </a>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <div class="submit-row">
                        <button type="button" class="button button-primary purplebox-next-step" style="margin-left:auto;">
                            <?php esc_html_e('Continue: Select Units →', 'purplebox-storage'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Units -->
        <div class="wizard-panel" data-step="2">
            <div class="postbox">
                <div class="postbox-header"><h2><?php esc_html_e('Step 2 — Select Storage Units', 'purplebox-storage'); ?></h2></div>
                <div class="inside">
                    <?php if (empty($available_units)) : ?>
                        <div class="notice notice-warning inline">
                            <p><?php esc_html_e('No units are currently available. All units are rented.', 'purplebox-storage'); ?></p>
                        </div>
                    <?php else : ?>
                        <p class="description" style="margin-bottom:12px;">
                            <?php esc_html_e('Select one or more available units for this contract.', 'purplebox-storage'); ?>
                        </p>
                        <table class="widefat striped" style="max-width:800px;">
                            <thead>
                                <tr>
                                    <th style="width:32px;"></th>
                                    <th><?php esc_html_e('Unit #', 'purplebox-storage'); ?></th>
                                    <th><?php esc_html_e('Name / Label', 'purplebox-storage'); ?></th>
                                    <th><?php esc_html_e('Size', 'purplebox-storage'); ?></th>
                                    <th><?php esc_html_e('Floor', 'purplebox-storage'); ?></th>
                                    <th><?php esc_html_e('Price (AED)', 'purplebox-storage'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($available_units as $u) : ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="unit_ids[]" value="<?php echo esc_attr($u['id']); ?>" class="purplebox-unit-checkbox" id="unit-<?php echo esc_attr($u['id']); ?>">
                                        </td>
                                        <td><label for="unit-<?php echo esc_attr($u['id']); ?>"><strong><?php echo esc_html($u['unit_number']); ?></strong></label></td>
                                        <td><?php echo !empty($u['display_name']) ? esc_html($u['display_name']) : '<span style="color:#50575e;">—</span>'; ?></td>
                                        <td><?php echo esc_html($u['size_category']); ?></td>
                                        <td><?php echo esc_html($u['floor']); ?></td>
                                        <td>
                                            <?php if (!empty($u['discounted_price'])) : ?>
                                                <span style="text-decoration:line-through; color:#50575e; font-size:11px;">AED <?php echo number_format((float)$u['price'], 2); ?></span>
                                                <strong style="color:#00691f; display:block;">AED <?php echo number_format((float)$u['discounted_price'], 2); ?></strong>
                                            <?php elseif (!empty($u['price'])) : ?>
                                                AED <?php echo number_format((float)$u['price'], 2); ?>
                                            <?php else : ?>
                                                <span style="color:#50575e;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p id="units-required-msg" style="color:#b32d2e; display:none; margin-top:8px;">
                            <?php esc_html_e('Please select at least one unit.', 'purplebox-storage'); ?>
                        </p>
                    <?php endif; ?>
                    <div class="submit-row">
                        <button type="button" class="button purplebox-prev-step"><?php esc_html_e('← Back', 'purplebox-storage'); ?></button>
                        <button type="button" class="button button-primary purplebox-next-step" style="margin-left:auto;">
                            <?php esc_html_e('Continue: Terms →', 'purplebox-storage'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Terms -->
        <div class="wizard-panel" data-step="3">
            <div class="postbox">
                <div class="postbox-header"><h2><?php esc_html_e('Step 3 — Contract Terms', 'purplebox-storage'); ?></h2></div>
                <div class="inside">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th><label for="move_in_date"><?php esc_html_e('Move In Date', 'purplebox-storage'); ?> <span class="required">*</span></label></th>
                            <td>
                                <input type="date" name="move_in_date" id="move_in_date" value="<?php echo esc_attr(date('Y-m-d')); ?>" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="first_payment_date"><?php esc_html_e('First Payment Date', 'purplebox-storage'); ?></label></th>
                            <td>
                                <input type="date" name="first_payment_date" id="first_payment_date" value="">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="move_out_date"><?php esc_html_e('Move Out Date', 'purplebox-storage'); ?></label></th>
                            <td>
                                <input type="date" name="move_out_date" id="move_out_date" value="">
                                <p class="description"><?php esc_html_e('Leave blank for open-ended contract.', 'purplebox-storage'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="payment_method"><?php esc_html_e('Payment Method', 'purplebox-storage'); ?> <span class="required">*</span></label></th>
                            <td>
                                <select name="payment_method" id="payment_method" required>
                                    <option value="Cash"><?php esc_html_e('Cash', 'purplebox-storage'); ?></option>
                                    <option value="Card"><?php esc_html_e('Card', 'purplebox-storage'); ?></option>
                                    <option value="Bank Transfer"><?php esc_html_e('Bank Transfer', 'purplebox-storage'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="next_payment_date"><?php esc_html_e('Next Payment Date', 'purplebox-storage'); ?></label></th>
                            <td>
                                <input type="date" name="next_payment_date" id="next_payment_date" value="">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Auto-renew', 'purplebox-storage'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_renew" value="1" checked>
                                    <?php esc_html_e('Renew automatically at move-out date', 'purplebox-storage'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Signed Contract PDF', 'purplebox-storage'); ?></label></th>
                            <td>
                                <input type="file" name="signed_pdf" accept=".pdf">
                                <p class="description"><?php esc_html_e('Upload the signed contract document (PDF only).', 'purplebox-storage'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <div class="submit-row">
                        <button type="button" class="button purplebox-prev-step"><?php esc_html_e('← Back', 'purplebox-storage'); ?></button>
                        <button type="button" class="button button-primary purplebox-next-step" style="margin-left:auto;">
                            <?php esc_html_e('Continue: Review →', 'purplebox-storage'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Review -->
        <div class="wizard-panel" data-step="4">
            <div class="postbox">
                <div class="postbox-header"><h2><?php esc_html_e('Step 4 — Review & Save', 'purplebox-storage'); ?></h2></div>
                <div class="inside">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th><?php esc_html_e('Tenant', 'purplebox-storage'); ?></th>
                            <td id="review-tenant">—</td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Units', 'purplebox-storage'); ?></th>
                            <td id="review-units">—</td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Move In', 'purplebox-storage'); ?></th>
                            <td id="review-move-in">—</td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('First Payment', 'purplebox-storage'); ?></th>
                            <td id="review-first-payment">—</td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Move Out', 'purplebox-storage'); ?></th>
                            <td id="review-move-out">—</td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Payment Method', 'purplebox-storage'); ?></th>
                            <td id="review-payment">—</td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Next Payment', 'purplebox-storage'); ?></th>
                            <td id="review-next-payment">—</td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Auto-renew', 'purplebox-storage'); ?></th>
                            <td id="review-renew">—</td>
                        </tr>
                    </table>
                    <div class="submit-row">
                        <button type="button" class="button purplebox-prev-step"><?php esc_html_e('← Back', 'purplebox-storage'); ?></button>
                        <?php submit_button(__('Create Contract', 'purplebox-storage'), 'primary', 'submit', false, ['style' => 'margin-left:auto;']); ?>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>
