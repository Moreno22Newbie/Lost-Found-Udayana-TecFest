<?php
require_once __DIR__ . '/fungsi.php';
$db = ambilKoneksi();

$q     = trim($_GET['q'] ?? '');
$jenis = $_GET['jenis'] ?? '';
$kat   = (int)($_GET['kategori'] ?? 0);

$sql = "SELECT l.id_laporan, l.jenis_laporan, l.nama_barang, l.warna, l.lokasi_kejadian,
               l.tanggal_kejadian, l.foto, k.nama_kategori
        FROM laporan l JOIN kategori k ON k.id_kategori = l.id_kategori
        WHERE l.status_laporan IN ('aktif','dalam_proses') AND l.kedaluwarsa_pada > NOW()";
$param = [];
if ($q !== '') {
    $sql .= " AND (l.nama_barang LIKE ? OR l.deskripsi_umum LIKE ? OR l.lokasi_kejadian LIKE ? OR l.warna LIKE ?)";
    $like = '%' . $q . '%';
    array_push($param, $like, $like, $like, $like);
}
if (in_array($jenis, ['kehilangan', 'penemuan'], true)) {
    $sql .= " AND l.jenis_laporan = ?";
    $param[] = $jenis;
} else {
    $jenis = '';
}
if ($kat > 0) {
    $sql .= " AND l.id_kategori = ?";
    $param[] = $kat;
}
$sql .= " ORDER BY l.dibuat_pada DESC LIMIT 30";

$st = $db->prepare($sql);
$st->execute($param);
$laporan = $st->fetchAll();
$kategori = $db->query('SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori')->fetchAll();
$sedangCari = ($q !== '' || $jenis !== '' || $kat > 0);

require __DIR__ . '/header.php';
?>
<section class="hero">
  <div class="wadah">
    <h1>Cari barang hilang atau ditemukan di Udayana.</h1>
    <p>Semua laporan ada di satu tempat, tidak lagi tertimbun di grup WhatsApp fakultas.</p>
    <form method="get" class="cari" role="search">
      <div>
        <label for="q" class="sr" style="position:absolute;left:-9999px">Kata kunci</label>
        <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Contoh: dompet hitam, kunci motor">
      </div>
      <select name="jenis" aria-label="Jenis laporan">
        <option value="">Semua jenis</option>
        <option value="kehilangan" <?= $jenis === 'kehilangan' ? 'selected' : '' ?>>Barang hilang</option>
        <option value="penemuan" <?= $jenis === 'penemuan' ? 'selected' : '' ?>>Barang ditemukan</option>
      </select>
      <select name="kategori" aria-label="Kategori">
        <option value="0">Semua kategori</option>
        <?php foreach ($kategori as $k): ?>
          <option value="<?= (int)$k['id_kategori'] ?>" <?= $kat === (int)$k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="tombol">Cari</button>
    </form>
    <div class="aksi-hero">
      <a class="tombol tombol-kuning" href="laporan_baru.php?jenis=kehilangan">Lapor barang hilang</a>
      <a class="tombol tombol-garis" href="laporan_baru.php?jenis=penemuan">Lapor barang ditemukan</a>
    </div>
  </div>
</section>

<main class="wadah isi">
  <h2><?= $sedangCari ? 'Hasil pencarian' : 'Laporan terbaru' ?></h2>
  <?php if (!$laporan): ?>
    <p class="kosong"><?= $sedangCari ? 'Tidak ada laporan yang cocok. Coba kata kunci lain, atau buat laporan agar barang Anda bisa dicocokkan.' : 'Belum ada laporan. Jadilah yang pertama membuat laporan.' ?></p>
  <?php else: ?>
    <div class="daftar">
      <?php foreach ($laporan as $l): ?>
        <a class="item" href="detail.php?id=<?= (int)$l['id_laporan'] ?>">
          <div class="thumb"><?= foto_html($l['foto'], $l['nama_barang']) ?></div>
          <div>
            <span class="lencana <?= $l['jenis_laporan'] === 'kehilangan' ? 'hilang' : 'ditemukan' ?>"><?= $l['jenis_laporan'] === 'kehilangan' ? 'Hilang' : 'Ditemukan' ?></span>
            <div class="item-judul"><?= e($l['nama_barang']) ?><?= $l['warna'] ? ', ' . e($l['warna']) : '' ?></div>
            <div class="meta">
              <span><?= e($l['nama_kategori']) ?></span>
              <span><?= e($l['lokasi_kejadian']) ?></span>
              <span><?= e(tanggal_id($l['tanggal_kejadian'])) ?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/footer.php'; ?>
