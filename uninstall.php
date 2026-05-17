<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// NOTE: Tables are intentionally preserved on uninstall to protect client data.
// Data can only be removed manually via phpMyAdmin or a direct DB query.
// delete_option('purplebox_db_version'); — also preserved so reinstall detects existing schema.

$upload_dir = wp_upload_dir();
$purplebox_dir = $upload_dir['basedir'] . '/purplebox';
if (is_dir($purplebox_dir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($purplebox_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($purplebox_dir);
}
