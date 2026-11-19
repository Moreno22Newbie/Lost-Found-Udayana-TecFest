<?php
// Pengaturan umum website. Ubah bagian DB sesuai komputer/hosting Anda.

// --- Pengaturan database ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'lost_found_udayana');
define('DB_USER', 'root');   // Laragon: root
define('DB_PASS', '');       // Laragon: kosong

// --- Pengaturan umum ---
define('NAMA_SITUS', 'Lost&Found Udayana');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAKS_FOTO', 2 * 1024 * 1024); // 2 MB
define('MASA_AKTIF_HARI', 30);        // laporan tampil publik selama 30 hari
date_default_timezone_set('Asia/Makassar'); // WITA

// Mulai session (dipakai untuk login dan token CSRF)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
