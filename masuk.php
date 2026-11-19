<?php
require_once __DIR__ . '/fungsi.php';
if (pengguna_sekarang()) { redirect('index.php'); }

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $email = trim($_POST['email'] ?? '');
    $sandi = $_POST['password'] ?? '';

    $st = ambilKoneksi()->prepare('SELECT id_pengguna, password_hash FROM pengguna WHERE email = ?');
    $st->execute([$email]);
    $user = $st->fetch();

    if ($user && password_verify($sandi, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['id_pengguna'] = (int)$user['id_pengguna'];
        $tujuan = $_SESSION['kembali'] ?? 'index.php';
        unset($_SESSION['kembali']);
        redirect($tujuan);
    }
    $error = 'Email atau kata sandi salah.';
}

$judul = 'Masuk';
require __DIR__ . '/header.php';
?>
<main class="wadah sempit isi">
  <h1>Masuk</h1>
  <?php if ($error): ?><div class="galat" role="alert"><p><?= e($error) ?></p></div><?php endif; ?>
  <form method="post" style="margin-top:20px">
    <?= csrf_field() ?>
    <div class="baris-form">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
    </div>
    <div class="baris-form">
      <label for="password">Kata sandi</label>
      <input type="password" id="password" name="password" required autocomplete="current-password">
    </div>
    <button type="submit" class="tombol">Masuk</button>
  </form>
  <p class="bantuan" style="margin-top:20px">Belum punya akun? <a href="daftar.php">Daftar di sini</a>.</p>
</main>
<?php require __DIR__ . '/footer.php'; ?>
