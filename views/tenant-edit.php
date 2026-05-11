<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap purplebox-wrap">
    <h1 class="wp-heading-inline">
        <?php echo $tenant ? esc_html__('Edit Tenant', 'purplebox-storage') : esc_html__('Add New Tenant', 'purplebox-storage'); ?>
    </h1>
    <?php if ($tenant) : ?>
        <span style="margin-left:10px; font-size:14px; color:#50575e;"><?php echo esc_html($tenant['client_id']); ?></span>
    <?php endif; ?>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-tenants')); ?>" class="page-title-action">
        <?php esc_html_e('← Back to Tenants', 'purplebox-storage'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['saved'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Tenant saved.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="purplebox_action" value="save_tenant">
        <input type="hidden" name="tenant_id" value="<?php echo esc_attr($tenant['id'] ?? 0); ?>">
        <?php wp_nonce_field('purplebox_save_tenant', 'purplebox_nonce'); ?>

        <!-- Basic Info -->
        <div class="postbox">
            <div class="postbox-header"><h2><?php esc_html_e('Tenant Information', 'purplebox-storage'); ?></h2></div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <?php if ($tenant) : ?>
                    <tr>
                        <th><?php esc_html_e('Client ID', 'purplebox-storage'); ?></th>
                        <td>
                            <code style="font-size:14px; padding:4px 8px; background:#f6f7f7; border:1px solid #dcdcde; border-radius:3px;">
                                <?php echo esc_html($tenant['client_id']); ?>
                            </code>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><label for="full_name"><?php esc_html_e('Full Name', 'purplebox-storage'); ?> <span class="required">*</span></label></th>
                        <td>
                            <input type="text" id="full_name" name="full_name" value="<?php echo esc_attr($tenant['full_name'] ?? ''); ?>" required class="regular-text" placeholder="<?php esc_attr_e('As per ID document', 'purplebox-storage'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="tenant_type"><?php esc_html_e('Tenant Type', 'purplebox-storage'); ?></label></th>
                        <td>
                            <select id="tenant_type" name="tenant_type">
                                <option value="individual" <?php selected($tenant['tenant_type'] ?? 'individual', 'individual'); ?>><?php esc_html_e('Individual', 'purplebox-storage'); ?></option>
                                <option value="company" <?php selected($tenant['tenant_type'] ?? '', 'company'); ?>><?php esc_html_e('Company (B2B)', 'purplebox-storage'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Phone Number(s)', 'purplebox-storage'); ?> <span class="required">*</span></label></th>
                        <td>
                            <div id="purplebox-phones-list">
                                <?php
                                $phones = $tenant['phones_array'] ?? [''];
                                foreach ($phones as $index => $phone) :
                                    $wa_num = preg_replace('/[^0-9]/', '', $phone);
                                ?>
                                <div class="purplebox-phone-row" style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                                    <input type="tel" name="phones[]" value="<?php echo esc_attr($phone); ?>" class="regular-text" placeholder="+971 5X XXX XXXX"<?php echo $index === 0 ? ' required' : ''; ?>>
                                    <?php if ($wa_num) : ?>
                                        <a href="https://wa.me/<?php echo esc_attr($wa_num); ?>" target="_blank" class="pb-wa-btn" title="<?php esc_attr_e('Open WhatsApp', 'purplebox-storage'); ?>">
                                            <?php esc_html_e('WhatsApp', 'purplebox-storage'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($index > 0) : ?>
                                        <button type="button" class="button purplebox-remove-phone">✕</button>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="purplebox-add-phone" class="button button-small">
                                + <?php esc_html_e('Add Phone', 'purplebox-storage'); ?>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="email"><?php esc_html_e('Email', 'purplebox-storage'); ?></label></th>
                        <td>
                            <input type="email" id="email" name="email" value="<?php echo esc_attr($tenant['email'] ?? ''); ?>" class="regular-text" placeholder="name@example.com">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nationality"><?php esc_html_e('Nationality', 'purplebox-storage'); ?></label></th>
                        <td>
                            <input type="text" id="nationality" name="nationality" value="<?php echo esc_attr($tenant['nationality'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="address"><?php esc_html_e('Address', 'purplebox-storage'); ?></label></th>
                        <td>
                            <textarea id="address" name="address" rows="3" class="large-text" placeholder="<?php esc_attr_e('Building, area, emirate', 'purplebox-storage'); ?>"><?php echo esc_textarea($tenant['address'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="status"><?php esc_html_e('Status', 'purplebox-storage'); ?></label></th>
                        <td>
                            <select id="status" name="status">
                                <option value="active" <?php selected($tenant['status'] ?? 'active', 'active'); ?>><?php esc_html_e('Active', 'purplebox-storage'); ?></option>
                                <option value="ended" <?php selected($tenant['status'] ?? '', 'ended'); ?>><?php esc_html_e('Ended', 'purplebox-storage'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Identity Documents -->
        <div class="postbox">
            <div class="postbox-header"><h2><?php esc_html_e('Identity Documents', 'purplebox-storage'); ?></h2></div>
            <div class="inside">
                <p class="description" style="margin-bottom:16px;"><?php esc_html_e('Fill in whichever documents the tenant has provided. Both can be saved.', 'purplebox-storage'); ?></p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e('Emirates ID', 'purplebox-storage'); ?></th>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                <div>
                                    <label style="display:block; margin-bottom:4px; font-size:12px; color:#50575e;"><?php esc_html_e('ID Number', 'purplebox-storage'); ?></label>
                                    <input type="text" name="emirates_id" value="<?php echo esc_attr($tenant['emirates_id'] ?? ''); ?>" placeholder="784-XXXX-XXXXXXX-X" style="width:220px;">
                                </div>
                                <div>
                                    <label style="display:block; margin-bottom:4px; font-size:12px; color:#50575e;"><?php esc_html_e('Expiry Date', 'purplebox-storage'); ?></label>
                                    <input type="date" name="eid_expiry" value="<?php echo esc_attr($tenant['eid_expiry'] ?? ''); ?>">
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Passport', 'purplebox-storage'); ?></th>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                <div>
                                    <label style="display:block; margin-bottom:4px; font-size:12px; color:#50575e;"><?php esc_html_e('Passport Number', 'purplebox-storage'); ?></label>
                                    <input type="text" name="passport_number" value="<?php echo esc_attr($tenant['passport_number'] ?? ''); ?>" placeholder="e.g. A12345678" style="width:220px;">
                                </div>
                                <div>
                                    <label style="display:block; margin-bottom:4px; font-size:12px; color:#50575e;"><?php esc_html_e('Expiry Date', 'purplebox-storage'); ?></label>
                                    <input type="date" name="passport_expiry" value="<?php echo esc_attr($tenant['passport_expiry'] ?? ''); ?>">
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Authorized Access Persons -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e('Authorized Access Persons', 'purplebox-storage'); ?></h2>
            </div>
            <div class="inside">
                <p class="description" style="margin-bottom:16px;">
                    <?php esc_html_e('People authorized to access this tenant\'s storage unit(s). Add anyone who may visit or collect on behalf of the tenant.', 'purplebox-storage'); ?>
                </p>

                <table class="widefat" id="purplebox-access-table">
                    <thead>
                        <tr>
                            <th style="width:22%;"><?php esc_html_e('Full Name', 'purplebox-storage'); ?> <span class="required">*</span></th>
                            <th style="width:18%;"><?php esc_html_e('Phone', 'purplebox-storage'); ?></th>
                            <th style="width:14%;"><?php esc_html_e('Relation', 'purplebox-storage'); ?></th>
                            <th style="width:14%;"><?php esc_html_e('ID Type', 'purplebox-storage'); ?></th>
                            <th style="width:22%;"><?php esc_html_e('ID Number', 'purplebox-storage'); ?></th>
                            <th style="width:44px;"></th>
                        </tr>
                    </thead>
                    <tbody id="purplebox-access-list">
                        <?php
                        $persons = $tenant['access_persons_array'] ?? [];
                        if (!empty($persons)) :
                            foreach ($persons as $p) :
                        ?>
                        <tr class="purplebox-access-row">
                            <td><input type="text" name="access_name[]" value="<?php echo esc_attr($p['name'] ?? ''); ?>" class="widefat" placeholder="<?php esc_attr_e('Full name', 'purplebox-storage'); ?>"></td>
                            <td><input type="tel" name="access_phone[]" value="<?php echo esc_attr($p['phone'] ?? ''); ?>" class="widefat" placeholder="+971 5X XXX XXXX"></td>
                            <td><input type="text" name="access_relation[]" value="<?php echo esc_attr($p['relation'] ?? ''); ?>" class="widefat" placeholder="<?php esc_attr_e('e.g. Family, Staff', 'purplebox-storage'); ?>"></td>
                            <td>
                                <select name="access_id_type[]" class="widefat">
                                    <option value=""><?php esc_html_e('— None —', 'purplebox-storage'); ?></option>
                                    <option value="Emirates ID" <?php selected($p['id_type'] ?? '', 'Emirates ID'); ?>><?php esc_html_e('Emirates ID', 'purplebox-storage'); ?></option>
                                    <option value="Passport" <?php selected($p['id_type'] ?? '', 'Passport'); ?>><?php esc_html_e('Passport', 'purplebox-storage'); ?></option>
                                    <option value="Other" <?php selected($p['id_type'] ?? '', 'Other'); ?>><?php esc_html_e('Other', 'purplebox-storage'); ?></option>
                                </select>
                            </td>
                            <td><input type="text" name="access_id_number[]" value="<?php echo esc_attr($p['id_number'] ?? ''); ?>" class="widefat" placeholder="<?php esc_attr_e('ID number', 'purplebox-storage'); ?>"></td>
                            <td><button type="button" class="button purplebox-remove-access" title="<?php esc_attr_e('Remove', 'purplebox-storage'); ?>">✕</button></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <button type="button" id="purplebox-add-access" class="button button-small" style="margin-top:10px;">
                    + <?php esc_html_e('Add Person', 'purplebox-storage'); ?>
                </button>

                <!-- Template row (hidden) -->
                <table style="display:none;">
                    <tbody id="purplebox-access-row-template">
                        <tr class="purplebox-access-row">
                            <td><input type="text" name="access_name[]" value="" class="widefat" placeholder="<?php esc_attr_e('Full name', 'purplebox-storage'); ?>"></td>
                            <td><input type="tel" name="access_phone[]" value="" class="widefat" placeholder="+971 5X XXX XXXX"></td>
                            <td><input type="text" name="access_relation[]" value="" class="widefat" placeholder="<?php esc_attr_e('e.g. Family, Staff', 'purplebox-storage'); ?>"></td>
                            <td>
                                <select name="access_id_type[]" class="widefat">
                                    <option value=""><?php esc_html_e('— None —', 'purplebox-storage'); ?></option>
                                    <option value="Emirates ID"><?php esc_html_e('Emirates ID', 'purplebox-storage'); ?></option>
                                    <option value="Passport"><?php esc_html_e('Passport', 'purplebox-storage'); ?></option>
                                    <option value="Other"><?php esc_html_e('Other', 'purplebox-storage'); ?></option>
                                </select>
                            </td>
                            <td><input type="text" name="access_id_number[]" value="" class="widefat" placeholder="<?php esc_attr_e('ID number', 'purplebox-storage'); ?>"></td>
                            <td><button type="button" class="button purplebox-remove-access" title="<?php esc_attr_e('Remove', 'purplebox-storage'); ?>">✕</button></td>
                        </tr>
                    </tbody>
                </table>

                <div class="submit-row" style="margin-top:20px;">
                    <?php submit_button(__('Save Tenant', 'purplebox-storage'), 'primary', 'submit', false); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-tenants')); ?>" class="button"><?php esc_html_e('Cancel', 'purplebox-storage'); ?></a>
                    <?php if ($tenant) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contract-new&tenant_id=' . $tenant['id'])); ?>" class="button" style="margin-left:auto;">
                            + <?php esc_html_e('New Contract for this Tenant', 'purplebox-storage'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </form>
</div>
