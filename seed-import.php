<?php
/**
 * One-time seed script — import units from Excel (Purplebox Inventory).
 *
 * Usage (CLI):   php seed-import.php
 * Usage (browser): place in plugin root and visit
 *     /wp-admin/admin.php?page=purplebox-seed-import
 *
 * Or simply load WordPress and run via WP-CLI:
 *     wp eval-file wp-content/plugins/purplebox-storage/seed-import.php
 */

// ─── Bootstrap WordPress if running from CLI ────────────────────────────
if (!defined('ABSPATH')) {
    // Walk up to find wp-load.php
    $dir = __DIR__;
    for ($i = 0; $i < 10; $i++) {
        if (file_exists($dir . '/wp-load.php')) {
            require_once $dir . '/wp-load.php';
            break;
        }
        $dir = dirname($dir);
    }
    if (!defined('ABSPATH')) {
        die("Could not find wp-load.php. Run this from inside your WordPress installation.\n");
    }
}

// Ensure the plugin classes are available
if (!class_exists('Purplebox_DB')) {
    require_once __DIR__ . '/includes/class-purplebox-db.php';
}

// ─── DATA from Excel: Purplebox Inventory (1).xlsx ──────────────────────
// Columns: Floor | Unit Number | Approx Size (SQF) | Price | Notes
//
// Floor is normalised to F1 or F2.
// Unit numbers are prefixed with floor: F1-01, F2-01, etc.
// "used" in notes → manual_status = 'rented'
// Size mapped to the nearest plugin category.

$units = [
    // ── F1 units ────────────────────────────────────────────
    ['floor' => 'F1', 'num' => 1,  'sqf' => 154, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 2,  'sqf' => 154, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 3,  'sqf' => 154, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 4,  'sqf' => 154, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 5,  'sqf' => 154, 'price' => 2880, 'notes' => 'done (cancel the electrical outlet and paint)', 'used' => false],
    ['floor' => 'F1', 'num' => 6,  'sqf' => 154, 'price' => 2880, 'notes' => 'used', 'used' => true],
    ['floor' => 'F1', 'num' => 7,  'sqf' => 25,  'price' => 750,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 8,  'sqf' => 25,  'price' => 750,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 9,  'sqf' => 42,  'price' => null,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 10, 'sqf' => 78,  'price' => 1200, 'notes' => 'used', 'used' => true],
    ['floor' => 'F1', 'num' => 11, 'sqf' => null, 'price' => null,  'notes' => 'still not built yet', 'used' => false],
    ['floor' => 'F1', 'num' => 12, 'sqf' => 110, 'price' => 1600, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 13, 'sqf' => 110, 'price' => 1600, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 14, 'sqf' => 190, 'price' => 2800, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 15, 'sqf' => 190, 'price' => 2800, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 16, 'sqf' => 190, 'price' => 2800, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 17, 'sqf' => 156, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 18, 'sqf' => 150, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 19, 'sqf' => 70,  'price' => 1200, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 20, 'sqf' => 55,  'price' => 990,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 21, 'sqf' => 40,  'price' => 990,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 22, 'sqf' => 78,  'price' => 1440, 'notes' => 'done (used store)', 'used' => true],
    ['floor' => 'F1', 'num' => 23, 'sqf' => 84,  'price' => 1200, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 24, 'sqf' => 77,  'price' => 1200, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 25, 'sqf' => 24,  'price' => 750,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 26, 'sqf' => 24,  'price' => 750,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 27, 'sqf' => 24,  'price' => 750,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 28, 'sqf' => 24,  'price' => 750,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 29, 'sqf' => 24,  'price' => 750,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 30, 'sqf' => 24,  'price' => 750,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 31, 'sqf' => 140, 'price' => 2640, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 32, 'sqf' => 25,  'price' => 750,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 33, 'sqf' => 25,  'price' => 750,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 34, 'sqf' => 25,  'price' => 750,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 35, 'sqf' => 25,  'price' => 750,  'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 36, 'sqf' => 190, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 37, 'sqf' => 190, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 38, 'sqf' => 190, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 39, 'sqf' => 190, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 40, 'sqf' => 190, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 41, 'sqf' => 190, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 42, 'sqf' => 190, 'price' => 2880, 'notes' => 'done (pending cancel the fire alarm) building', 'used' => false],
    ['floor' => 'F1', 'num' => 43, 'sqf' => 190, 'price' => 2880, 'notes' => 'done', 'used' => false],
    ['floor' => 'F1', 'num' => 44, 'sqf' => 190, 'price' => 2880, 'notes' => 'pending epoxy paint (fire alarm store)', 'used' => false],

    // ── F2 units (starting from row 45 in Excel) ───────────
    ['floor' => 'F2', 'num' => 1,   'sqf' => 49,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 2,   'sqf' => 49,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 3,   'sqf' => 49,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 4,   'sqf' => 49,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 5,   'sqf' => 45,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 6,   'sqf' => 45,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 7,   'sqf' => 35,  'price' => 770,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 8,   'sqf' => 35,  'price' => 770,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 9,   'sqf' => 50,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 10,  'sqf' => 50,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 11,  'sqf' => 50,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 12,  'sqf' => 50,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 13,  'sqf' => 50,  'price' => 990,  'notes' => 'store (pending epoxy paint)', 'used' => false],
    ['floor' => 'F2', 'num' => 14,  'sqf' => 75,  'price' => 1320, 'notes' => 'done (fire hose reel)', 'used' => false],
    ['floor' => 'F2', 'num' => 15,  'sqf' => 154, 'price' => 2640, 'notes' => 'done', 'used' => false],
    ['floor' => 'F2', 'num' => 16,  'sqf' => 154, 'price' => 2640, 'notes' => 'done', 'used' => false],
    ['floor' => 'F2', 'num' => 17,  'sqf' => 47,  'price' => 990,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 18,  'sqf' => 35,  'price' => 770,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 19,  'sqf' => 47,  'price' => 990,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 20,  'sqf' => null, 'price' => null,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 21,  'sqf' => null, 'price' => null,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 22,  'sqf' => 35,  'price' => 770,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 23,  'sqf' => 34,  'price' => 770,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 24,  'sqf' => 85,  'price' => 1320, 'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 25,  'sqf' => 85,  'price' => 1320, 'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 26,  'sqf' => 85,  'price' => 1320, 'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 27,  'sqf' => 32,  'price' => 770,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 28,  'sqf' => 32,  'price' => 770,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 29,  'sqf' => 32,  'price' => 770,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 30,  'sqf' => 32,  'price' => 770,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 31,  'sqf' => 32,  'price' => 770,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 32,  'sqf' => 45,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 33,  'sqf' => 45,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 34,  'sqf' => 45,  'price' => 990,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 35,  'sqf' => 35,  'price' => 770,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 36,  'sqf' => 27,  'price' => 687,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 37,  'sqf' => 27,  'price' => 687,  'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 38,  'sqf' => 98,  'price' => 1760, 'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 39,  'sqf' => 69,  'price' => 1320, 'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 40,  'sqf' => 98,  'price' => 1760, 'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 41,  'sqf' => 98,  'price' => 1760, 'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 42,  'sqf' => 98,  'price' => 1760, 'notes' => 'missing ceiling and sprinkler', 'used' => false],
    ['floor' => 'F2', 'num' => 43,  'sqf' => 145, 'price' => 2640, 'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 44,  'sqf' => 145, 'price' => 2640, 'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 45,  'sqf' => 145, 'price' => 2640, 'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 46,  'sqf' => 145, 'price' => 2640, 'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 47,  'sqf' => 11,  'price' => 440,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 48,  'sqf' => 11,  'price' => 440,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 49,  'sqf' => 11,  'price' => 440,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 50,  'sqf' => 11,  'price' => 440,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 51,  'sqf' => 11,  'price' => 440,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 52,  'sqf' => 11,  'price' => 440,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 53,  'sqf' => 11,  'price' => 440,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 54,  'sqf' => 11,  'price' => 440,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 55,  'sqf' => 11,  'price' => 440,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 56,  'sqf' => 11,  'price' => 440,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 57,  'sqf' => 45,  'price' => 990,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 58,  'sqf' => 31,  'price' => 770,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 59,  'sqf' => 45,  'price' => 990,  'notes' => 'not yet installed', 'used' => false],
    ['floor' => 'F2', 'num' => 60,  'sqf' => 45,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 61,  'sqf' => 49,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 62,  'sqf' => 49,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 63,  'sqf' => 49,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 64,  'sqf' => 49,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 65,  'sqf' => 49,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 66,  'sqf' => 50,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 67,  'sqf' => 42,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 68,  'sqf' => 46,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 69,  'sqf' => 96,  'price' => 1760, 'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 70,  'sqf' => 47,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 71,  'sqf' => 47,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 72,  'sqf' => 34,  'price' => 770,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 73,  'sqf' => 34,  'price' => 770,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 74,  'sqf' => 98,  'price' => 1760, 'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 75,  'sqf' => 35,  'price' => 770,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 76,  'sqf' => 35,  'price' => 770,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 77,  'sqf' => 50,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 78,  'sqf' => 50,  'price' => 990,  'notes' => 'missing ceiling and sprinkler and epoxy paint', 'used' => false],
    ['floor' => 'F2', 'num' => 79,  'sqf' => 36,  'price' => 770,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 80,  'sqf' => 36,  'price' => 770,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 81,  'sqf' => 36,  'price' => 770,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 82,  'sqf' => 36,  'price' => 770,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 83,  'sqf' => 36,  'price' => 770,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 84,  'sqf' => 36,  'price' => 770,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 85,  'sqf' => 36,  'price' => 770,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 86,  'sqf' => 36,  'price' => 770,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 87,  'sqf' => 25,  'price' => 687,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 88,  'sqf' => 46,  'price' => 990,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 89,  'sqf' => 67,  'price' => 1320, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 90,  'sqf' => 44,  'price' => 990,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 91,  'sqf' => 33,  'price' => 770,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 92,  'sqf' => 49,  'price' => 990,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 93,  'sqf' => 94,  'price' => 1760, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 94,  'sqf' => 94,  'price' => 1760, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 95,  'sqf' => 94,  'price' => 1760, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 96,  'sqf' => 94,  'price' => 1760, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 97,  'sqf' => 94,  'price' => 1760, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 98,  'sqf' => 76,  'price' => 1320, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 99,  'sqf' => 76,  'price' => 1320, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 100, 'sqf' => 76,  'price' => 1320, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 101, 'sqf' => 76,  'price' => 1320, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 102, 'sqf' => 76,  'price' => 1320, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 103, 'sqf' => 76,  'price' => 1320, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 104, 'sqf' => 38,  'price' => 770,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 105, 'sqf' => 35,  'price' => 770,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 106, 'sqf' => 75,  'price' => 1320, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 107, 'sqf' => 100, 'price' => 1760, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 108, 'sqf' => 100, 'price' => 1760, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 109, 'sqf' => 100, 'price' => 1760, 'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 110, 'sqf' => 60,  'price' => 990,  'notes' => '', 'used' => false],
    ['floor' => 'F2', 'num' => 111, 'sqf' => 37,  'price' => 770,  'notes' => '', 'used' => false],
];

// ─── Size category mapping ──────────────────────────────────────────────
// Maps actual sq.ft. to the nearest plugin size category
function map_size_category($sqf) {
    if ($sqf === null) return 'Custom';

    $categories = [
        'Locker'      => [0, 15],
        '25 sq.ft.'   => [16, 30],
        '35 sq.ft.'   => [31, 40],
        '50 sq.ft.'   => [41, 60],
        '75 sq.ft.'   => [61, 85],
        '100 sq.ft.'  => [86, 120],
        '150 sq.ft.'  => [121, 160],
        '200 sq.ft.'  => [161, 999],
    ];

    foreach ($categories as $label => [$min, $max]) {
        if ($sqf >= $min && $sqf <= $max) {
            return $label;
        }
    }
    return 'Custom';
}

// ─── Run import ─────────────────────────────────────────────────────────
global $wpdb;
$table = $wpdb->prefix . 'purplebox_units';

$inserted = 0;
$skipped  = 0;
$rented   = 0;

foreach ($units as $u) {
    $unit_number = $u['floor'] . '-' . str_pad($u['num'], 2, '0', STR_PAD_LEFT);

    // Skip if unit_number already exists
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE unit_number = %s",
        $unit_number
    ));
    if ($exists) {
        $skipped++;
        continue;
    }

    $size_category = map_size_category($u['sqf']);

    $fields = [
        'unit_number'   => $unit_number,
        'size_category' => $size_category,
        'custom_size'   => $u['sqf'],
        'floor'         => $u['floor'],
        'price'         => $u['price'] ?? 0,
        'quantity'       => 1,
        'facility'      => 'PurpleBox Al Quoz',
        'notes'         => $u['notes'],
        'manual_status' => $u['used'] ? 'rented' : null,
    ];

    $wpdb->insert($table, $fields);
    $inserted++;
    if ($u['used']) $rented++;
}

$msg = sprintf(
    "Import complete: %d units inserted, %d skipped (already exist), %d marked as manually rented.\n",
    $inserted, $skipped, $rented
);

if (php_sapi_name() === 'cli') {
    echo $msg;
} else {
    echo '<div class="notice notice-success"><p>' . esc_html($msg) . '</p></div>';
}
