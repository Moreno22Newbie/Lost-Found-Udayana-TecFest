<?php
require_once __DIR__ . '/fungsi.php';
$pengguna = wajib_login();
$db = ambilKoneksi();
$idSaya = (int)$pengguna['id_pengguna'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tutup') {
    csrf_cek();
    $db->prepare("UPDATE laporan SET status_laporan = 'ditutup' WHERE id_laporan = ? AND id_pengguna = ? AND status_laporan = 'aktif'")
       ->execute([(int)($_POST['id_laporan'] ?? 0), $idSaya]);
    flash('Laporan ditutup.');
    redirect('akun.php');
}

$st = $db->prepare('SELECT id_laporan, jenis_laporan, nama_barang, status_laporan, dibuat_pada FROM laporan WHERE id_pengguna = ? ORDER BY dibuat_pada DESC');
$st->execute([$idSaya]);
$laporan = $st->fetchAll();

$st = $db->prepare(
    'SELECT c.id_klaim, c.status_klaim, c.diajukan_pada, p.nama_barang,
            (SELECT COUNT(*) FROM pesan m WHERE m.id_klaim = c.id_klaim AND m.id_pengirim <> ? AND m.sudah_dibaca = 0) AS belum_dibaca
     FROM klaim c JOIN laporan p ON p.id_laporan = c.id_laporan_penemuan
     WHERE c.id_pengguna = ? ORDER BY c.diajukan_pada DESC'
);
$st->execute([$idSaya, $idSaya]);
$klaim = $st->fetchAll();

$judul = 'Akun saya';
require __DIR__ . '/header.php';
?>
<main class="wadah isi">
  <h1>Halo, <?= e($pengguna['nama']) ?></h1>
  <p class="bantuan"><?= e($pengguna['email']) ?></p>

  <h2 style="margin-top:32px">Klaim saya</h2>
  <?php if (!$klaim): ?>
    <p class="kosong" style="padding:12px 0">Belum ada klaim. Jika menemukan barang Anda di daftar, buka laporannya dan ajukan klaim.</p>
  <?php else: ?>
    <div class="gulir"><table>
      <thead><tr><th>Barang</th><th>Diajukan</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($klaim as $c): ?>
        <tr>
          <td><?= e($c['nama_barang']) ?></td>
          <td><?= e(tanggal_id($c['diajukan_pada'])) ?></td>
          <td><span class="status <?= e($c['status_klaim']) ?>"><?= e(label_status($c['status_klaim'])) ?></span></td>
          <td><a href="klaim_detail.php?id=<?= (int)$c['id_klaim'] ?>">Buka<?= $c['belum_dibaca'] ? ' (' . (int)$c['belum_dibaca'] . ' pesan baru)' : '' ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>

  <h2 style="margin-top:40px">Laporan saya</h2>
  <?php if (!$laporan): ?>
    <p class="kosong" style="padding:12px 0">Belum ada laporan. <a href="laporan_baru.php">Buat laporan pertama</a>.</p>
  <?php else: ?>
    <div class="gulir"><table>
      <thead><tr><th>Barang</th><th>Jenis</th><th>Dibuat</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($laporan as $l): ?>
        <tr>
          <td><a href="detail.php?id=<?= (int)$l['id_laporan'] ?>"><?= e($l['nama_barang']) ?></a></td>
          <td><?= $l['jenis_laporan'] === 'kehilangan' ? 'Hilang' : 'Ditemukan' ?></td>
          <td><?= e(tanggal_id($l['dibuat_pada'])) ?></td>
          <td><span class="status <?= e($l['status_laporan']) ?>"><?= e(label_status($l['status_laporan'])) ?></span></td>
          <td>
            <?php if ($l['status_laporan'] === 'aktif'): ?>
              <form method="post" class="form-sebaris">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="tutup">
                <input type="hidden" name="id_laporan" value="<?= (int)$l['id_laporan'] ?>">
                <button type="submit" class="tautan" data-konfirmasi="Tutup laporan ini? Laporan tidak akan tampil lagi di publik.">Tutup</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/footer.php'; ?>
