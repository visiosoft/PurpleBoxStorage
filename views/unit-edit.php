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
        <?php if (!empty($unit['manual_status']) && $unit['manual_status'] === 'rented') : ?>
            <div class="notice notice-warning"><p><?php esc_html_e('This unit is manually marked as booked (not via a contract).', 'purplebox-storage'); ?></p></div>
        <?php else : ?>
            <div class="notice notice-warning"><p><?php esc_html_e('This unit is currently rented under an active contract.', 'purplebox-storage'); ?></p></div>
        <?php endif; ?>
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
                    <?php if (!empty($unit['manual_status']) && $unit['manual_status'] === 'rented') : ?>
                        <span class="pill" style="margin-right:12px; background:#fef3cd; color:#856404;">
                            <?php esc_html_e('Manually Booked', 'purplebox-storage'); ?>
                        </span>
                    <?php elseif ($is_rented) : ?>
                        <span class="pill" style="margin-right:12px; background:#e8f0fe; color:#1e4ea1;">
                            <?php esc_html_e('Rented', 'purplebox-storage'); ?>
                        </span>
                    <?php else : ?>
                        <span class="pill available" style="margin-right:12px;">
                            <?php esc_html_e('Available', 'purplebox-storage'); ?>
                        </span>
                    <?php endif; ?>
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
                        <th><label for="manual_status"><?php esc_html_e('Status Override', 'purplebox-storage'); ?></label></th>
                        <td>
                            <?php $current_manual = $unit['manual_status'] ?? ''; ?>
                            <select id="manual_status" name="manual_status">
                                <option value="" <?php selected($current_manual, ''); ?>><?php esc_html_e('Automatic (from contracts)', 'purplebox-storage'); ?></option>
                                <option value="rented" <?php selected($current_manual, 'rented'); ?>><?php esc_html_e('Manually Rented / Booked', 'purplebox-storage'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Set to "Manually Rented" to mark this unit as booked without creating a contract. It will appear as rented in inventory and reports.', 'purplebox-storage'); ?></p>
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
                                $sizes = ['Locker', '10 sq.ft.', '25 sq.ft.', '35 sq.ft.', '50 sq.ft.', '75 sq.ft.', '100 sq.ft.', '150 sq.ft.', '200 sq.ft.', 'Custom'];
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
                        <th><label for="display_name"><?php esc_html_e('Size Label', 'purplebox-storage'); ?></label></th>
                        <td>
                            <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($unit['display_name'] ?? ''); ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g. Small, Budget Unit, Premium Corner', 'purplebox-storage'); ?>">
                            <p class="description"><?php esc_html_e('Friendly name for this size — shown on contracts and the dashboard instead of the sq.ft. category.', 'purplebox-storage'); ?></p>
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
                                $floors = ['F1', 'F2', 'F3'];
                                $current_floor = $unit['floor'] ?? 'F1';
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
                                <input type="number" id="price" name="price"
                                       value="<?php echo esc_attr($unit['price'] ?? ''); ?>"
                                       step="0.01" min="0" class="regular-text" placeholder="0.00"
                                       oninput="pbUpdateDiscount()">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Discount', 'purplebox-storage'); ?></label></th>
                        <td>
                            <?php
                            $saved_pct = isset($unit['discount_pct']) && $unit['discount_pct'] !== null
                                         ? (float) $unit['discount_pct'] : '';
                            $presets = [5, 10, 15, 20, 25, 30];
                            ?>
                            <!-- Preset buttons -->
                            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px;" id="pb-pct-presets">
                                <button type="button" class="button pb-pct-btn" data-pct="0"
                                    style="<?php echo $saved_pct === '' || $saved_pct == 0 ? 'background:#1d2327;color:#fff;border-color:#1d2327;' : ''; ?>">
                                    <?php esc_html_e('No discount', 'purplebox-storage'); ?>
                                </button>
                                <?php foreach ($presets as $p) : ?>
                                <button type="button" class="button pb-pct-btn" data-pct="<?php echo $p; ?>"
                                    style="<?php echo (string)$saved_pct === (string)$p ? 'background:#1d2327;color:#fff;border-color:#1d2327;' : ''; ?>">
                                    <?php echo $p; ?>%
                                </button>
                                <?php endforeach; ?>
                                <button type="button" class="button pb-pct-btn" data-pct="custom"
                                    style="<?php echo ($saved_pct !== '' && $saved_pct > 0 && !in_array((int)$saved_pct, $presets)) ? 'background:#1d2327;color:#fff;border-color:#1d2327;' : ''; ?>">
                                    <?php esc_html_e('Custom %', 'purplebox-storage'); ?>
                                </button>
                            </div>

                            <!-- Hidden real input -->
                            <input type="hidden" id="discount_pct" name="discount_pct"
                                   value="<?php echo esc_attr($saved_pct); ?>">

                            <!-- Custom % text input (shown only for custom) -->
                            <div id="pb-custom-pct-wrap" style="display:<?php echo ($saved_pct !== '' && $saved_pct > 0 && !in_array((int)$saved_pct, $presets)) ? 'flex' : 'none'; ?>; align-items:center; gap:6px; margin-bottom:10px;">
                                <input type="number" id="pb_custom_pct_input" min="0" max="100" step="0.01"
                                       value="<?php echo esc_attr($saved_pct); ?>"
                                       class="small-text" placeholder="e.g. 12"
                                       oninput="pbSetCustomPct(this.value)">
                                <span style="color:#50575e;">%</span>
                            </div>

                            <!-- Live preview -->
                            <div id="pb-discount-preview" style="margin-top:4px; font-size:13px; color:#00691f; min-height:20px;"></div>

                            <script>
                            (function(){
                                function pbGetPrice() {
                                    return parseFloat(document.getElementById('price').value) || 0;
                                }
                                function pbSetPct(pct) {
                                    document.getElementById('discount_pct').value = (pct === '' || pct === 0) ? '' : pct;
                                    pbUpdatePreview(pct);
                                }
                                window.pbUpdateDiscount = function() { pbUpdatePreview(parseFloat(document.getElementById('discount_pct').value) || 0); };
                                window.pbSetCustomPct  = function(val) { pbSetPct(parseFloat(val) || ''); };

                                function pbUpdatePreview(pct) {
                                    var price = pbGetPrice();
                                    var el    = document.getElementById('pb-discount-preview');
                                    if (!pct || !price) { el.textContent = ''; return; }
                                    var disc  = price * (1 - pct / 100);
                                    var save  = price - disc;
                                    el.textContent = pct + '% off → AED ' + disc.toFixed(2) + ' (saving AED ' + save.toFixed(2) + ')';
                                }

                                document.querySelectorAll('.pb-pct-btn').forEach(function(btn){
                                    btn.addEventListener('click', function(){
                                        document.querySelectorAll('.pb-pct-btn').forEach(function(b){
                                            b.style.background = ''; b.style.color = ''; b.style.borderColor = '';
                                        });
                                        this.style.background   = '#1d2327';
                                        this.style.color        = '#fff';
                                        this.style.borderColor  = '#1d2327';

                                        var pct = this.dataset.pct;
                                        var wrap = document.getElementById('pb-custom-pct-wrap');
                                        if (pct === 'custom') {
                                            wrap.style.display = 'flex';
                                            pbSetPct(parseFloat(document.getElementById('pb_custom_pct_input').value) || '');
                                        } else {
                                            wrap.style.display = 'none';
                                            pbSetPct(pct === '0' ? '' : parseFloat(pct));
                                        }
                                    });
                                });

                                // Init preview on load
                                pbUpdatePreview(parseFloat(document.getElementById('discount_pct').value) || 0);
                            })();
                            </script>
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
