<?php
require_once __DIR__ . '/fungsi.php';
$pengguna = wajib_login();
$db = ambilKoneksi();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$st = $db->prepare(
    "SELECT * FROM laporan WHERE id_laporan = ? AND jenis_laporan = 'penemuan'
     AND status_laporan = 'aktif' AND kedaluwarsa_pada > NOW()"
);
$st->execute([$id]);
$l = $st->fetch();
if (!$l || (int)$l['id_pengguna'] === (int)$pengguna['id_pengguna']) { tidak_ditemukan(); }

$cek = $db->prepare(
    "SELECT id_klaim FROM klaim WHERE id_laporan_penemuan = ? AND id_pengguna = ?
     AND status_klaim IN ('menunggu','dihubungi','disetujui')"
);
$cek->execute([$id, $pengguna['id_pengguna']]);
$ada = $cek->fetch();
if ($ada) {
    flash('Anda sudah mengajukan klaim untuk barang ini.');
    redirect('klaim_detail.php?id=' . $ada['id_klaim']);
}

$st = $db->prepare("SELECT id_laporan, nama_barang FROM laporan WHERE id_pengguna = ? AND jenis_laporan = 'kehilangan' AND status_laporan IN ('aktif','dalam_proses') ORDER BY dibuat_pada DESC");
$st->execute([$pengguna['id_pengguna']]);
$laporanSaya = $st->fetchAll();

$errors = [];
$data = ['nama_pengklaim' => $pengguna['nama'], 'nomor_telepon' => $pengguna['nomor_telepon'], 'email' => $pengguna['email'], 'detail_kepemilikan' => '', 'id_laporan_kehilangan' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    foreach ($data as $kunci => $nilai) { $data[$kunci] = trim($_POST[$kunci] ?? ''); }

    if (mb_strlen($data['nama_pengklaim']) < 2 || mb_strlen($data['nama_pengklaim']) > 100) { $errors[] = 'Nama harus 2 sampai 100 karakter.'; }
    if (!preg_match('/^[0-9+\-\s]{8,20}$/', $data['nomor_telepon'])) { $errors[] = 'Nomor telepon tidak valid.'; }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Format email tidak valid.'; }
    if (mb_strlen($data['detail_kepemilikan']) < 20) { $errors[] = 'Jelaskan bukti kepemilikan minimal 20 karakter.'; }

    $idKehilangan = null;
    if ($data['id_laporan_kehilangan'] !== '') {
        foreach ($laporanSaya as $ls) {
            if ((int)$ls['id_laporan'] === (int)$data['id_laporan_kehilangan']) { $idKehilangan = (int)$ls['id_laporan']; }
        }
        if ($idKehilangan === null) { $errors[] = 'Laporan kehilangan yang dipilih tidak valid.'; }
    }

    if (!$errors) {
        $ins = $db->prepare(
            'INSERT INTO klaim (id_laporan_penemuan, id_laporan_kehilangan, id_pengguna, nama_pengklaim, nomor_telepon, email, detail_kepemilikan)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$id, $idKehilangan, $pengguna['id_pengguna'], $data['nama_pengklaim'], $data['nomor_telepon'], $data['email'], $data['detail_kepemilikan']]);
        flash('Klaim terkirim. Admin akan menghubungi Anda lewat chat di halaman ini.');
        redirect('klaim_detail.php?id=' . $db->lastInsertId());
    }
}

$judul = 'Ajukan klaim';
require __DIR__ . '/header.php';
?>
<main class="wadah sempit isi">
  <p><a href="detail.php?id=<?= (int)$id ?>">Kembali ke laporan</a></p>
  <h1>Ajukan klaim</h1>
  <p>Barang: <strong><?= e($l['nama_barang']) ?></strong>, ditemukan di <?= e($l['lokasi_kejadian']) ?>.</p>
  <?php if ($errors): ?>
    <div class="galat" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
  <?php endif; ?>
  <form method="post" style="margin-top:20px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="baris-form">
      <label for="detail_kepemilikan">Bukti kepemilikan</label>
      <textarea id="detail_kepemilikan" name="detail_kepemilikan" required><?= e($data['detail_kepemilikan']) ?></textarea>
      <p class="bantuan">Tulis ciri yang hanya diketahui pemilik: goresan, stiker, isi, nomor seri, atau wallpaper. Jawaban ini dicocokkan admin dengan ciri khusus dari penemu.</p>
    </div>
    <?php if ($laporanSaya): ?>
      <div class="baris-form">
        <label for="id_laporan_kehilangan">Tautkan ke laporan kehilangan saya (opsional)</label>
        <select id="id_laporan_kehilangan" name="id_laporan_kehilangan">
          <option value="">Tidak ditautkan</option>
          <?php foreach ($laporanSaya as $ls): ?>
            <option value="<?= (int)$ls['id_laporan'] ?>" <?= (string)$data['id_laporan_kehilangan'] === (string)$ls['id_laporan'] ? 'selected' : '' ?>><?= e($ls['nama_barang']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>
    <div class="baris-form">
      <label for="nama_pengklaim">Nama</label>
      <input type="text" id="nama_pengklaim" name="nama_pengklaim" value="<?= e($data['nama_pengklaim']) ?>" required maxlength="100">
    </div>
    <div class="baris-form">
      <label for="nomor_telepon">Nomor telepon</label>
      <input type="tel" id="nomor_telepon" name="nomor_telepon" value="<?= e($data['nomor_telepon']) ?>" required maxlength="20">
    </div>
    <div class="baris-form">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($data['email']) ?>" required maxlength="100">
    </div>
    <button type="submit" class="tombol">Kirim klaim</button>
  </form>
</main>
<?php require __DIR__ . '/footer.php'; ?>
