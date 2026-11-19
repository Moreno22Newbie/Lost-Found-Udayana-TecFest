<?php
require_once __DIR__ . '/fungsi.php';
$db = ambilKoneksi();
$pengguna = pengguna_sekarang();

$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare(
    'SELECT l.*, k.nama_kategori FROM laporan l JOIN kategori k ON k.id_kategori = l.id_kategori WHERE l.id_laporan = ?'
);
$st->execute([$id]);
$l = $st->fetch();
if (!$l) { tidak_ditemukan(); }

$pemilik = $pengguna && (int)$pengguna['id_pengguna'] === (int)$l['id_pengguna'];
$admin   = $pengguna && $pengguna['peran'] === 'admin';
$tampilPublik = in_array($l['status_laporan'], ['aktif', 'dalam_proses'], true) && strtotime($l['kedaluwarsa_pada']) > time();

if (!$tampilPublik && !$pemilik && !$admin) { tidak_ditemukan(); }

$bisaKlaim = $l['jenis_laporan'] === 'penemuan' && $l['status_laporan'] === 'aktif' && !$pemilik && $tampilPublik;
$cocok = $tampilPublik ? cari_kecocokan($l) : [];

$judul = $l['nama_barang'];
require __DIR__ . '/header.php';
?>
<main class="wadah isi">
  <p><a href="index.php">Kembali ke daftar</a></p>
  <div class="dua-kolom">
    <div class="thumb-besar"><?= foto_html($l['foto'], $l['nama_barang']) ?></div>
    <div>
      <span class="lencana <?= $l['jenis_laporan'] === 'kehilangan' ? 'hilang' : 'ditemukan' ?>"><?= $l['jenis_laporan'] === 'kehilangan' ? 'Barang hilang' : 'Barang ditemukan' ?></span>
      <span class="status <?= e($l['status_laporan']) ?>"><?= e(label_status($l['status_laporan'])) ?></span>
      <h1 style="margin-top:10px"><?= e($l['nama_barang']) ?></h1>

      <dl class="rincian">
        <div><dt>Kategori</dt><dd><?= e($l['nama_kategori']) ?></dd></div>
        <div><dt>Warna</dt><dd><?= e($l['warna'] ?: '-') ?></dd></div>
        <div><dt>Lokasi</dt><dd><?= e($l['lokasi_kejadian']) ?></dd></div>
        <div><dt>Waktu kejadian</dt><dd><?= e(tanggal_id($l['tanggal_kejadian'], true)) ?></dd></div>
      </dl>

      <h2 style="margin-top:24px">Deskripsi</h2>
      <p style="white-space:pre-line"><?= e($l['deskripsi_umum']) ?></p>

      <?php if ($pemilik || $admin): ?>
        <div class="panel panel-rahasia">
          <h2>Ciri khusus (hanya terlihat oleh <?= $pemilik ? 'Anda' : 'admin' ?>)</h2>
          <p style="white-space:pre-line;margin:0"><?= e($l['ciri_khusus'] ?: 'Tidak diisi.') ?></p>
        </div>
      <?php else: ?>
        <p class="bantuan">Ciri khusus barang disimpan pelapor dan dipakai admin untuk verifikasi.</p>
      <?php endif; ?>

      <div class="tombol-baris">
        <?php if ($bisaKlaim): ?>
          <?php if ($pengguna): ?>
            <a class="tombol" href="klaim.php?id=<?= (int)$l['id_laporan'] ?>">Ini barang saya, ajukan klaim</a>
          <?php else: ?>
            <a class="tombol" href="masuk.php">Masuk untuk mengajukan klaim</a>
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($pemilik || $admin): ?>
          <a class="tombol tombol-garis" href="edit_laporan.php?id=<?= (int)$l['id_laporan'] ?>">Ubah laporan</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($cocok): ?>
    <section style="margin-top:48px">
      <h2><?= $l['jenis_laporan'] === 'kehilangan' ? 'Barang ditemukan yang mungkin cocok' : 'Laporan kehilangan yang mirip' ?></h2>
      <div class="daftar">
        <?php foreach ($cocok as $c): ?>
          <a class="item" href="detail.php?id=<?= (int)$c['id_laporan'] ?>">
            <div class="thumb"><?= foto_html($c['foto'], $c['nama_barang']) ?></div>
            <div>
              <div class="item-judul"><?= e($c['nama_barang']) ?><?= $c['warna'] ? ', ' . e($c['warna']) : '' ?></div>
              <div class="meta">
                <span><?= e($c['lokasi_kejadian']) ?></span>
                <span><?= e(tanggal_id($c['tanggal_kejadian'])) ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/footer.php'; ?>
