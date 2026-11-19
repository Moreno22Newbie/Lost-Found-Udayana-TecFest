<?php
require_once __DIR__ . '/fungsi.php';
$pengguna = wajib_login();
$db = ambilKoneksi();
$admin = $pengguna['peran'] === 'admin';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $db->prepare('SELECT * FROM laporan WHERE id_laporan = ?');
$st->execute([$id]);
$l = $st->fetch();

if (!$l || (!$admin && ((int)$l['id_pengguna'] !== (int)$pengguna['id_pengguna'] || $l['status_laporan'] !== 'aktif'))) {
    tidak_ditemukan();
}

$kategori = $db->query('SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori')->fetchAll();
$errors = [];
$data = [
    'id_kategori' => $l['id_kategori'], 'nama_barang' => $l['nama_barang'], 'warna' => $l['warna'],
    'deskripsi_umum' => $l['deskripsi_umum'], 'ciri_khusus' => $l['ciri_khusus'], 'lokasi_kejadian' => $l['lokasi_kejadian'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    foreach ($data as $kunci => $nilai) { $data[$kunci] = trim($_POST[$kunci] ?? ''); }

    if (!in_array((int)$data['id_kategori'], array_map('intval', array_column($kategori, 'id_kategori')), true)) { $errors[] = 'Pilih kategori barang.'; }
    if (mb_strlen($data['nama_barang']) < 2 || mb_strlen($data['nama_barang']) > 100) { $errors[] = 'Nama barang harus 2 sampai 100 karakter.'; }
    if (mb_strlen($data['warna']) > 50) { $errors[] = 'Warna maksimal 50 karakter.'; }
    if (mb_strlen($data['deskripsi_umum']) < 10) { $errors[] = 'Deskripsi umum minimal 10 karakter.'; }
    if ($data['lokasi_kejadian'] === '' || mb_strlen($data['lokasi_kejadian']) > 150) { $errors[] = 'Lokasi wajib diisi (maksimal 150 karakter).'; }

    if (!$errors) {
        $up = $db->prepare(
            'UPDATE laporan SET id_kategori = ?, nama_barang = ?, warna = ?, deskripsi_umum = ?, ciri_khusus = ?, lokasi_kejadian = ?
             WHERE id_laporan = ?'
        );
        $up->execute([(int)$data['id_kategori'], $data['nama_barang'], $data['warna'] ?: null, $data['deskripsi_umum'],
                      $data['ciri_khusus'] ?: null, $data['lokasi_kejadian'], $id]);
        flash('Laporan diperbarui.');
        redirect('detail.php?id=' . $id);
    }
}

$judul = 'Ubah laporan';
require __DIR__ . '/header.php';
?>
<main class="wadah sempit isi">
  <h1>Ubah laporan</h1>
  <?php if ($errors): ?>
    <div class="galat" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
  <?php endif; ?>
  <form method="post" style="margin-top:20px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="baris-form">
      <label for="nama_barang">Nama barang</label>
      <input type="text" id="nama_barang" name="nama_barang" value="<?= e($data['nama_barang']) ?>" required maxlength="100">
    </div>
    <div class="baris-form">
      <label for="id_kategori">Kategori</label>
      <select id="id_kategori" name="id_kategori" required>
        <?php foreach ($kategori as $k): ?>
          <option value="<?= (int)$k['id_kategori'] ?>" <?= (int)$data['id_kategori'] === (int)$k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="baris-form">
      <label for="warna">Warna</label>
      <input type="text" id="warna" name="warna" value="<?= e($data['warna']) ?>" maxlength="50">
    </div>
    <div class="baris-form">
      <label for="deskripsi_umum">Deskripsi umum</label>
      <textarea id="deskripsi_umum" name="deskripsi_umum" required><?= e($data['deskripsi_umum']) ?></textarea>
    </div>
    <div class="baris-form">
      <label for="ciri_khusus">Ciri khusus (rahasia)</label>
      <textarea id="ciri_khusus" name="ciri_khusus"><?= e($data['ciri_khusus']) ?></textarea>
    </div>
    <div class="baris-form">
      <label for="lokasi_kejadian">Lokasi</label>
      <input type="text" id="lokasi_kejadian" name="lokasi_kejadian" value="<?= e($data['lokasi_kejadian']) ?>" required maxlength="150">
    </div>
    <button type="submit" class="tombol">Simpan perubahan</button>
    <a class="tombol tombol-garis" href="detail.php?id=<?= (int)$id ?>">Batal</a>
  </form>
</main>
<?php require __DIR__ . '/footer.php'; ?>
