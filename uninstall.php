<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}purplebox_contracts");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}purplebox_units");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}purplebox_tenants");

delete_option('purplebox_db_version');

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
