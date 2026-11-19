<?php
require_once __DIR__ . '/fungsi.php';
$pengguna = pengguna_sekarang();
$judulHalaman = isset($judul) ? $judul . ' - ' . NAMA_SITUS : NAMA_SITUS;
$pesanFlash = flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judulHalaman) ?></title>
  <link rel="icon" href="logo-unud.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="atas">
  <div class="wadah baris-atas">
    <a class="logo" href="index.php"><img src="logo-unud.png" alt="Logo Universitas Udayana" class="logo-gambar">Lost&amp;Found Udayana</a>
    <nav class="menu" aria-label="Menu utama">
      <a href="index.php">Beranda</a>
      <?php if ($pengguna): ?>
        <a href="laporan_baru.php">Buat laporan</a>
        <a href="akun.php">Akun saya</a>
        <?php if ($pengguna['peran'] === 'admin'): ?><a href="admin.php">Admin</a><?php endif; ?>
        <form method="post" action="keluar.php" class="form-sebaris">
          <?= csrf_field() ?>
          <button type="submit" class="tautan">Keluar</button>
        </form>
      <?php else: ?>
        <a href="masuk.php">Masuk</a>
        <a href="daftar.php" class="tombol tombol-kecil">Daftar</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php if ($pesanFlash): ?>
  <div class="wadah"><p class="info" role="status"><?= e($pesanFlash) ?></p></div>
<?php endif; ?>