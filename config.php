<?php
/**
 * Project: Weighbridge Hybrid Sync
 * File: config.php
 * Logic: Dual-Mode Credentials (Local XAMPP + Hostinger Cloud)
 */

return [
    // --- 1. LOCAL DATABASE (XAMPP) ---
    'db_host'   => 'localhost',
    'db_user'   => 'root',
    'db_pass'   => '',
    'db_name'   => 'weighbridge_db',


    'slip_prefix' => 'WB-',
    'date_format' => 'd/m/Y',
    'time_format' => 'H:i',
];