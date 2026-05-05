<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap purplebox-wrap">
    <h1 class="wp-heading-inline">
        <?php echo $unit ? esc_html__('Edit Unit', 'purplebox-storage') : esc_html__('Add New Unit', 'purplebox-storage'); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-units')); ?>" class="page-title-action">
        <?php esc_html_e('← Back to Inventory', 'purplebox-storage'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['saved'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Unit saved.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>

    <?php if ($unit && $is_rented) : ?>
        <div class="notice notice-warning"><p><?php esc_html_e('This unit is currently rented under an active contract.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="purplebox_action" value="save_unit">
        <input type="hidden" name="unit_id" value="<?php echo esc_attr($unit['id'] ?? 0); ?>">
        <?php wp_nonce_field('purplebox_save_unit', 'purplebox_nonce'); ?>

        <!-- Identity -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e('Unit Identity', 'purplebox-storage'); ?></h2>
                <?php if ($unit) : ?>
                    <span class="pill <?php echo $is_rented ? '' : 'available'; ?>" style="margin-right:12px; <?php echo $is_rented ? 'background:#e8f0fe; color:#1e4ea1;' : ''; ?>">
                        <?php echo $is_rented ? esc_html__('Rented', 'purplebox-storage') : esc_html__('Available', 'purplebox-storage'); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="unit_number"><?php esc_html_e('Unit Number', 'purplebox-storage'); ?> <span class="required">*</span></label></th>
                        <td>
                            <input type="text" id="unit_number" name="unit_number" value="<?php echo esc_attr($unit['unit_number'] ?? ''); ?>" required class="regular-text" placeholder="<?php esc_attr_e('e.g. A01, G-25, F1-10', 'purplebox-storage'); ?>">
                            <p class="description"><?php esc_html_e('Internal reference code. Must be unique.', 'purplebox-storage'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="display_name"><?php esc_html_e('Display Name', 'purplebox-storage'); ?></label></th>
                        <td>
                            <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($unit['display_name'] ?? ''); ?>" class="large-text" placeholder="<?php esc_attr_e('e.g. Ground Floor 20sqf Near Door, Small Corner Unit', 'purplebox-storage'); ?>">
                            <p class="description"><?php esc_html_e('Friendly label shown on contracts and listings. Leave blank to use the unit number.', 'purplebox-storage'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Size & Location -->
        <div class="postbox">
            <div class="postbox-header"><h2><?php esc_html_e('Size & Location', 'purplebox-storage'); ?></h2></div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="size_category"><?php esc_html_e('Size Category', 'purplebox-storage'); ?> <span class="required">*</span></label></th>
                        <td>
                            <select id="size_category" name="size_category" required>
                                <?php
                                $sizes = ['Locker', '25 sq.ft.', '35 sq.ft.', '50 sq.ft.', '75 sq.ft.', '100 sq.ft.', '150 sq.ft.', '200 sq.ft.', 'Custom'];
                                $current_size = $unit['size_category'] ?? '';
                                foreach ($sizes as $size) {
                                    printf('<option value="%s" %s>%s</option>', esc_attr($size), selected($current_size, $size, false), esc_html($size));
                                }
                                ?>
                            </select>
                            <p class="description"><?php esc_html_e('Used for grouping and filtering in the dashboard.', 'purplebox-storage'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="custom_size"><?php esc_html_e('Actual Size (sq.ft.)', 'purplebox-storage'); ?></label></th>
                        <td>
                            <input type="number" id="custom_size" name="custom_size" value="<?php echo esc_attr($unit['custom_size'] ?? ''); ?>" step="0.01" min="0" class="small-text" placeholder="0.00">
                            <p class="description"><?php esc_html_e('Exact size for this unit. Required if category is Custom.', 'purplebox-storage'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="floor"><?php esc_html_e('Floor', 'purplebox-storage'); ?> <span class="required">*</span></label></th>
                        <td>
                            <select id="floor" name="floor" required>
                                <?php
                                $floors = ['Ground', 'F1', 'F2'];
                                $current_floor = $unit['floor'] ?? 'Ground';
                                foreach ($floors as $floor) {
                                    printf('<option value="%s" %s>%s</option>', esc_attr($floor), selected($current_floor, $floor, false), esc_html($floor));
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="facility"><?php esc_html_e('Facility', 'purplebox-storage'); ?></label></th>
                        <td>
                            <input type="text" id="facility" name="facility" value="<?php echo esc_attr($unit['facility'] ?? 'PurpleBox Al Quoz'); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Pricing -->
        <div class="postbox">
            <div class="postbox-header"><h2><?php esc_html_e('Pricing', 'purplebox-storage'); ?></h2></div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="price"><?php esc_html_e('Regular Price (AED)', 'purplebox-storage'); ?></label></th>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="color:#50575e;">AED</span>
                                <input type="number" id="price" name="price" value="<?php echo esc_attr($unit['price'] ?? ''); ?>" step="0.01" min="0" class="regular-text" placeholder="0.00">
                            </div>
                            <p class="description"><?php esc_html_e('Standard rental price for this unit. Increase for premium locations (near door, corner, etc).', 'purplebox-storage'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="discounted_price"><?php esc_html_e('Discounted Price (AED)', 'purplebox-storage'); ?></label></th>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="color:#50575e;">AED</span>
                                <input type="number" id="discounted_price" name="discounted_price" value="<?php echo esc_attr($unit['discounted_price'] ?? ''); ?>" step="0.01" min="0" class="regular-text" placeholder="0.00">
                            </div>
                            <p class="description"><?php esc_html_e('Optional. Leave blank if no discount applies.', 'purplebox-storage'); ?></p>
                            <?php
                            if (!empty($unit['price']) && !empty($unit['discounted_price'])) :
                                $saving = (float) $unit['price'] - (float) $unit['discounted_price'];
                                $pct    = round(($saving / (float) $unit['price']) * 100);
                                if ($saving > 0) :
                            ?>
                                <p style="margin-top:6px; color:#00691f;">
                                    <?php printf(esc_html__('Saving: AED %s (%d%% off)', 'purplebox-storage'), number_format($saving, 2), $pct); ?>
                                </p>
                            <?php endif; endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Features & Notes -->
        <div class="postbox">
            <div class="postbox-header"><h2><?php esc_html_e('Features & Notes', 'purplebox-storage'); ?></h2></div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label><?php esc_html_e('Features', 'purplebox-storage'); ?></label></th>
                        <td>
                            <div class="checkbox-list">
                                <?php
                                $all_features = ['Climate Controlled', 'Power Outlet', '24/7 Access', 'Roll-up Door', 'Near Entrance', 'Corner Unit', 'Easy Access', 'Ground Level'];
                                $current_features = !empty($unit['features']) ? json_decode($unit['features'], true) : [];
                                if (!is_array($current_features)) $current_features = [];
                                foreach ($all_features as $feature) {
                                    $checked = in_array($feature, $current_features) ? 'checked' : '';
                                    printf(
                                        '<label><input type="checkbox" name="features[]" value="%s" %s> %s</label>',
                                        esc_attr($feature), $checked, esc_html($feature)
                                    );
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="notes"><?php esc_html_e('Internal Notes', 'purplebox-storage'); ?></label></th>
                        <td>
                            <textarea id="notes" name="notes" rows="3" class="large-text" placeholder="<?php esc_attr_e('e.g. Near main entrance, slight slope in floor, etc.', 'purplebox-storage'); ?>"><?php echo esc_textarea($unit['notes'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                </table>

                <div class="submit-row">
                    <?php submit_button(__('Save Unit', 'purplebox-storage'), 'primary', 'submit', false); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-units')); ?>" class="button"><?php esc_html_e('Cancel', 'purplebox-storage'); ?></a>
                </div>
            </div>
        </div>
    </form>
</div>
