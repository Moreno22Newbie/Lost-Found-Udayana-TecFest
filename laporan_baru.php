<?php
require_once __DIR__ . '/fungsi.php';
$pengguna = wajib_login();
$db = ambilKoneksi();
$kategori = $db->query('SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori')->fetchAll();
$idKategoriValid = array_column($kategori, 'id_kategori');

$errors = [];
$jenisAwal = $_GET['jenis'] ?? '';
$data = [
    'jenis_laporan'   => in_array($jenisAwal, ['kehilangan', 'penemuan'], true) ? $jenisAwal : 'kehilangan',
    'id_kategori'     => '',
    'nama_barang'     => '',
    'warna'           => '',
    'deskripsi_umum'  => '',
    'ciri_khusus'     => '',
    'lokasi_kejadian' => '',
    'tanggal_kejadian'=> date('Y-m-d\TH:i'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    foreach ($data as $kunci => $nilai) {
        $data[$kunci] = trim($_POST[$kunci] ?? '');
    }

    // Validasi
    if (!in_array($data['jenis_laporan'], ['kehilangan', 'penemuan'], true)) { $errors[] = 'Pilih jenis laporan.'; }
    if (!in_array((int)$data['id_kategori'], array_map('intval', $idKategoriValid), true)) { $errors[] = 'Pilih kategori barang.'; }
    if (mb_strlen($data['nama_barang']) < 2 || mb_strlen($data['nama_barang']) > 100) { $errors[] = 'Nama barang harus 2 sampai 100 karakter.'; }
    if (mb_strlen($data['warna']) > 50) { $errors[] = 'Warna maksimal 50 karakter.'; }
    if (mb_strlen($data['deskripsi_umum']) < 10) { $errors[] = 'Deskripsi umum minimal 10 karakter.'; }
    if ($data['lokasi_kejadian'] === '' || mb_strlen($data['lokasi_kejadian']) > 150) { $errors[] = 'Lokasi wajib diisi (maksimal 150 karakter).'; }
    $waktu = strtotime($data['tanggal_kejadian']);
    if (!$waktu || $waktu > time() + 3600) { $errors[] = 'Tanggal kejadian tidak valid atau di masa depan.'; }

    // Foto baru diunggah kalau data lain sudah benar
    $namaFoto = null;
    if (!$errors) {
        [$namaFoto, $errFoto] = upload_foto($_FILES['foto'] ?? ['name' => '']);
        if ($errFoto) { $errors[] = $errFoto; }
    }

    if (!$errors) {
        $st = $db->prepare(
            'INSERT INTO laporan (id_pengguna, id_kategori, jenis_laporan, nama_barang, warna, deskripsi_umum,
                                  ciri_khusus, lokasi_kejadian, tanggal_kejadian, foto, status_laporan, kedaluwarsa_pada)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'aktif\', ?)'
        );
        $st->execute([
            $pengguna['id_pengguna'], (int)$data['id_kategori'], $data['jenis_laporan'], $data['nama_barang'],
            $data['warna'] ?: null, $data['deskripsi_umum'], $data['ciri_khusus'] ?: null, $data['lokasi_kejadian'],
            date('Y-m-d H:i:s', $waktu), $namaFoto,
            date('Y-m-d H:i:s', strtotime('+' . MASA_AKTIF_HARI . ' days')),
        ]);
        flash('Laporan berhasil dibuat.');
        redirect('detail.php?id=' . $db->lastInsertId());
    }
}

$judul = 'Buat laporan';
require __DIR__ . '/header.php';
?>
<main class="wadah sempit isi">
  <h1>Buat laporan</h1>
  <?php if ($errors): ?>
    <div class="galat" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data" style="margin-top:20px">
    <?= csrf_field() ?>
    <div class="baris-form">
      <label>Jenis laporan</label>
      <div class="pilihan-jenis">
        <label><input type="radio" name="jenis_laporan" value="kehilangan" <?= $data['jenis_laporan'] === 'kehilangan' ? 'checked' : '' ?>> Barang hilang</label>
        <label><input type="radio" name="jenis_laporan" value="penemuan" <?= $data['jenis_laporan'] === 'penemuan' ? 'checked' : '' ?>> Barang ditemukan</label>
      </div>
    </div>
    <div class="baris-form">
      <label for="nama_barang">Nama barang</label>
      <input type="text" id="nama_barang" name="nama_barang" value="<?= e($data['nama_barang']) ?>" required maxlength="100" placeholder="Contoh: iPhone 13">
    </div>
    <div class="baris-form">
      <label for="id_kategori">Kategori</label>
      <select id="id_kategori" name="id_kategori" required>
        <option value="">Pilih kategori</option>
        <?php foreach ($kategori as $k): ?>
          <option value="<?= (int)$k['id_kategori'] ?>" <?= (int)$data['id_kategori'] === (int)$k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="baris-form">
      <label for="warna">Warna</label>
      <input type="text" id="warna" name="warna" value="<?= e($data['warna']) ?>" maxlength="50" placeholder="Contoh: hitam">
    </div>
    <div class="baris-form">
      <label for="deskripsi_umum">Deskripsi umum</label>
      <textarea id="deskripsi_umum" name="deskripsi_umum" required><?= e($data['deskripsi_umum']) ?></textarea>
      <p class="bantuan">Tampil ke publik. Cukup tulis ciri umum, misalnya merek dan ukuran.</p>
    </div>
    <div class="baris-form">
      <label for="ciri_khusus">Ciri khusus (rahasia)</label>
      <textarea id="ciri_khusus" name="ciri_khusus"><?= e($data['ciri_khusus']) ?></textarea>
      <p class="bantuan">Tidak ditampilkan ke publik. Dipakai admin untuk memastikan pemilik asli, misalnya goresan, stiker, atau isi tas.</p>
    </div>
    <div class="baris-form">
      <label for="lokasi_kejadian">Lokasi hilang atau ditemukan</label>
      <input type="text" id="lokasi_kejadian" name="lokasi_kejadian" value="<?= e($data['lokasi_kejadian']) ?>" required maxlength="150" placeholder="Contoh: Parkiran Fakultas Teknik">
    </div>
    <div class="baris-form">
      <label for="tanggal_kejadian">Waktu kejadian</label>
      <input type="datetime-local" id="tanggal_kejadian" name="tanggal_kejadian" value="<?= e($data['tanggal_kejadian']) ?>" required>
    </div>
    <div class="baris-form">
      <label for="foto">Foto (opsional)</label>
      <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
      <p class="bantuan">JPG, PNG, atau WebP. Maksimal 2 MB.</p>
    </div>
    <button type="submit" class="tombol">Kirim laporan</button>
  </form>
</main>
<?php require __DIR__ . '/footer.php'; ?>
