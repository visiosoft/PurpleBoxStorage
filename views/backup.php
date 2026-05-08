<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap purplebox-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Backup & Restore', 'purplebox-storage'); ?></h1>
    <hr class="wp-header-end">

    <?php if (isset($_GET['imported']) && $_GET['imported'] === '1') :
        $ui = absint($_GET['units_imported']   ?? 0); $us = absint($_GET['units_skipped']      ?? 0);
        $ti = absint($_GET['tenants_imported'] ?? 0); $ts = absint($_GET['tenants_skipped']    ?? 0);
        $ci = absint($_GET['contracts_imported']??0); $cs = absint($_GET['contracts_skipped']  ?? 0);
    ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong><?php esc_html_e('Import complete!', 'purplebox-storage'); ?></strong>
                &nbsp;
                <?php printf(
                    esc_html__('Units: %d imported, %d skipped. Tenants: %d imported, %d skipped. Contracts: %d imported, %d skipped.', 'purplebox-storage'),
                    $ui, $us, $ti, $ts, $ci, $cs
                ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['import_error'])) :
        $errors = [
            'no_file'      => __('No file was uploaded. Please choose a backup JSON file.', 'purplebox-storage'),
            'read_failed'  => __('Could not read the uploaded file.', 'purplebox-storage'),
            'invalid_file' => __('Invalid backup file. Please upload a valid PurpleBox JSON backup.', 'purplebox-storage'),
            'wrong_plugin' => __('This file is not a PurpleBox backup.', 'purplebox-storage'),
        ];
        $msg = $errors[sanitize_key($_GET['import_error'])] ?? __('An unknown error occurred.', 'purplebox-storage');
    ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html($msg); ?></p></div>
    <?php endif; ?>

    <!-- Current Data Stats -->
    <div class="at-a-glance" style="margin-bottom:24px;">
        <div class="glance-tile">
            <div class="label"><?php esc_html_e('Units', 'purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($stats['units']); ?></div>
        </div>
        <div class="glance-tile">
            <div class="label"><?php esc_html_e('Tenants', 'purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($stats['tenants']); ?></div>
        </div>
        <div class="glance-tile">
            <div class="label"><?php esc_html_e('Contracts', 'purplebox-storage'); ?></div>
            <div class="value"><?php echo esc_html($stats['contracts']); ?></div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:start;">

        <!-- Export / Backup -->
        <div class="postbox">
            <div class="postbox-header">
                <h2>⬇️ <?php esc_html_e('Export Backup', 'purplebox-storage'); ?></h2>
            </div>
            <div class="inside">
                <p style="color:#50575e; margin-top:0;">
                    <?php esc_html_e('Download a complete JSON backup of all PurpleBox data — units, tenants, and contracts.', 'purplebox-storage'); ?>
                </p>
                <ul style="color:#50575e; margin-left:16px; list-style:disc;">
                    <li><?php printf(esc_html__('%d units', 'purplebox-storage'), $stats['units']); ?></li>
                    <li><?php printf(esc_html__('%d tenants', 'purplebox-storage'), $stats['tenants']); ?></li>
                    <li><?php printf(esc_html__('%d contracts', 'purplebox-storage'), $stats['contracts']); ?></li>
                </ul>
                <form method="post" style="margin-top:16px;">
                    <input type="hidden" name="purplebox_action" value="export_backup">
                    <?php wp_nonce_field('purplebox_export_backup', 'purplebox_nonce'); ?>
                    <button type="submit" class="button button-primary" style="font-size:14px; padding:6px 18px; height:auto;">
                        ⬇️ <?php esc_html_e('Download Backup (.json)', 'purplebox-storage'); ?>
                    </button>
                </form>
                <p style="color:#50575e; font-size:12px; margin-top:12px;">
                    <?php printf(
                        esc_html__('File name: purplebox-backup-%s.json', 'purplebox-storage'),
                        date('Y-m-d')
                    ); ?>
                </p>
            </div>
        </div>

        <!-- Import / Restore -->
        <div class="postbox">
            <div class="postbox-header">
                <h2>⬆️ <?php esc_html_e('Import / Restore', 'purplebox-storage'); ?></h2>
            </div>
            <div class="inside">
                <p style="color:#50575e; margin-top:0;">
                    <?php esc_html_e('Upload a previously exported PurpleBox backup JSON file to restore data.', 'purplebox-storage'); ?>
                </p>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="purplebox_action" value="import_backup">
                    <?php wp_nonce_field('purplebox_import_backup', 'purplebox_nonce'); ?>

                    <table class="form-table" role="presentation" style="margin-top:0;">
                        <tr>
                            <th style="padding-top:6px;"><label for="backup_file"><?php esc_html_e('Backup File', 'purplebox-storage'); ?> <span class="required">*</span></label></th>
                            <td>
                                <input type="file" name="backup_file" id="backup_file" accept=".json" required>
                                <p class="description"><?php esc_html_e('Select a .json file exported from PurpleBox.', 'purplebox-storage'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Import Mode', 'purplebox-storage'); ?></th>
                            <td>
                                <label style="display:block; margin-bottom:8px;">
                                    <input type="radio" name="import_mode" value="skip" checked>
                                    <strong><?php esc_html_e('Skip existing records', 'purplebox-storage'); ?></strong>
                                    <span style="color:#50575e; display:block; margin-left:20px; font-size:12px;">
                                        <?php esc_html_e('Only adds new records. Existing data is untouched. Safe for merging.', 'purplebox-storage'); ?>
                                    </span>
                                </label>
                                <label style="display:block;">
                                    <input type="radio" name="import_mode" value="overwrite">
                                    <strong><?php esc_html_e('Overwrite existing records', 'purplebox-storage'); ?></strong>
                                    <span style="color:#50575e; display:block; margin-left:20px; font-size:12px;">
                                        <?php esc_html_e('Replaces matching records with backup data. Use for full restore.', 'purplebox-storage'); ?>
                                    </span>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <div style="background:#fff8e1; border-left:4px solid #f0b429; padding:10px 14px; margin:12px 0; border-radius:2px;">
                        <strong>⚠️ <?php esc_html_e('Warning:', 'purplebox-storage'); ?></strong>
                        <?php esc_html_e('Overwrite mode will replace existing records permanently. Make an export backup first if unsure.', 'purplebox-storage'); ?>
                    </div>

                    <button type="submit" class="button button-primary" style="font-size:14px; padding:6px 18px; height:auto;"
                        onclick="return confirm('<?php esc_attr_e('Are you sure you want to import this backup?', 'purplebox-storage'); ?>')">
                        ⬆️ <?php esc_html_e('Start Import', 'purplebox-storage'); ?>
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- What's included note -->
    <div class="postbox" style="margin-top:8px;">
        <div class="postbox-header"><h2><?php esc_html_e('What\'s included in the backup?', 'purplebox-storage'); ?></h2></div>
        <div class="inside">
            <table class="widefat striped" style="max-width:600px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Data', 'purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Included', 'purplebox-storage'); ?></th>
                        <th><?php esc_html_e('Match key (import)', 'purplebox-storage'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php esc_html_e('Storage Units', 'purplebox-storage'); ?></td>
                        <td>✅</td>
                        <td><code>unit_number</code></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Tenants', 'purplebox-storage'); ?></td>
                        <td>✅</td>
                        <td><code>client_id</code></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Contracts', 'purplebox-storage'); ?></td>
                        <td>✅</td>
                        <td><code>id</code></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Uploaded PDFs', 'purplebox-storage'); ?></td>
                        <td><span style="color:#50575e;">Path only (not file)</span></td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
