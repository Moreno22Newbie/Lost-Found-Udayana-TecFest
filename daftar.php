<?php
require_once __DIR__ . '/fungsi.php';
if (pengguna_sekarang()) { redirect('index.php'); }

$errors = [];
$nama = $email = $telepon = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telepon  = trim($_POST['nomor_telepon'] ?? '');
    $sandi    = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (mb_strlen($nama) < 2 || mb_strlen($nama) > 100) { $errors[] = 'Nama harus 2 sampai 100 karakter.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) { $errors[] = 'Format email tidak valid.'; }
    if (!preg_match('/^[0-9+\-\s]{8,20}$/', $telepon)) { $errors[] = 'Nomor telepon harus 8 sampai 20 angka.'; }
    if (strlen($sandi) < 8) { $errors[] = 'Kata sandi minimal 8 karakter.'; }
    if ($sandi !== $konfirmasi) { $errors[] = 'Konfirmasi kata sandi tidak sama.'; }

    $db = ambilKoneksi();
    if (!$errors) {
        $cek = $db->prepare('SELECT 1 FROM pengguna WHERE email = ?');
        $cek->execute([$email]);
        if ($cek->fetch()) { $errors[] = 'Email ini sudah terdaftar. Silakan masuk.'; }
    }

    if (!$errors) {
        $st = $db->prepare('INSERT INTO pengguna (nama, email, nomor_telepon, password_hash) VALUES (?, ?, ?, ?)');
        $st->execute([$nama, $email, $telepon, password_hash($sandi, PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['id_pengguna'] = (int)$db->lastInsertId();
        flash('Akun berhasil dibuat. Selamat datang!');
        redirect('index.php');
    }
}

$judul = 'Daftar';
require __DIR__ . '/header.php';
?>
<main class="wadah sempit isi">
  <h1>Buat akun</h1>
  <?php if ($errors): ?>
    <div class="galat" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
  <?php endif; ?>
  <form method="post" style="margin-top:20px">
    <?= csrf_field() ?>
    <div class="baris-form">
      <label for="nama">Nama lengkap</label>
      <input type="text" id="nama" name="nama" value="<?= e($nama) ?>" required maxlength="100" autocomplete="name">
    </div>
    <div class="baris-form">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($email) ?>" required maxlength="100" autocomplete="email">
    </div>
    <div class="baris-form">
      <label for="nomor_telepon">Nomor telepon</label>
      <input type="tel" id="nomor_telepon" name="nomor_telepon" value="<?= e($telepon) ?>" required maxlength="20" autocomplete="tel">
      <p class="bantuan">Tidak ditampilkan ke publik.</p>
    </div>
    <div class="baris-form">
      <label for="password">Kata sandi</label>
      <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
      <p class="bantuan">Minimal 8 karakter.</p>
    </div>
    <div class="baris-form">
      <label for="konfirmasi">Ulangi kata sandi</label>
      <input type="password" id="konfirmasi" name="konfirmasi" required minlength="8" autocomplete="new-password">
    </div>
    <button type="submit" class="tombol">Daftar</button>
  </form>
  <p class="bantuan" style="margin-top:20px">Sudah punya akun? <a href="masuk.php">Masuk</a>.</p>
</main>
<?php require __DIR__ . '/footer.php'; ?>
