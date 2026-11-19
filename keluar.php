<?php
require_once __DIR__ . '/fungsi.php';
// Keluar hanya lewat tombol (POST) dengan token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $_SESSION = [];
    session_destroy();
}
redirect('masuk.php');
