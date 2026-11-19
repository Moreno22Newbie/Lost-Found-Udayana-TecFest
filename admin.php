<?php
require_once __DIR__ . '/fungsi.php';
$admin = wajib_admin();
$db = ambilKoneksi();

$statusLaporan = ['aktif', 'dalam_proses', 'diklaim', 'dikembalikan', 'kedaluwarsa', 'ditutup'];
$statusKlaim   = ['menunggu', 'dihubungi', 'disetujui', 'ditolak', 'selesai'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'status_laporan') {
    csrf_cek();
    $baru = $_POST['status'] ?? '';
    if (in_array($baru, $statusLaporan, true)) {
        $db->prepare('UPDATE laporan SET status_laporan = ? WHERE id_laporan = ?')->execute([$baru, (int)($_POST['id_laporan'] ?? 0)]);
        flash('Status laporan diperbarui.');
    }
    redirect('admin.php?tab=laporan');
}

$tab = ($_GET['tab'] ?? 'klaim') === 'laporan' ? 'laporan' : 'klaim';
$filter = $_GET['status'] ?? '';
if (!in_array($filter, $statusKlaim, true)) { $filter = ''; }

$menunggu  = (int)$db->query("SELECT COUNT(*) FROM klaim WHERE status_klaim = 'menunggu'")->fetchColumn();
$dihubungi = (int)$db->query("SELECT COUNT(*) FROM klaim WHERE status_klaim = 'dihubungi'")->fetchColumn();

if ($tab === 'klaim') {
    $sql = 'SELECT c.id_klaim, c.status_klaim, c.diajukan_pada, c.nama_pengklaim, p.nama_barang
            FROM klaim c JOIN laporan p ON p.id_laporan = c.id_laporan_penemuan';
    $param = [];
    if ($filter !== '') { $sql .= ' WHERE c.status_klaim = ?'; $param[] = $filter; }
    $sql .= " ORDER BY FIELD(c.status_klaim,'menunggu','dihubungi','disetujui','ditolak','selesai'), c.diajukan_pada DESC LIMIT 100";
    $st = $db->prepare($sql);
    $st->execute($param);
    $daftarKlaim = $st->fetchAll();
} else {
    $daftarLaporan = $db->query(
        'SELECT l.id_laporan, l.jenis_laporan, l.nama_barang, l.status_laporan, l.dibuat_pada, u.nama AS pelapor
         FROM laporan l JOIN pengguna u ON u.id_pengguna = l.id_pengguna ORDER BY l.dibuat_pada DESC LIMIT 100'
    )->fetchAll();
}

$judul = 'Admin';
require __DIR__ . '/header.php';
?>
<main class="wadah isi">
  <h1>Dashboard admin</h1>
  <p class="bantuan"><?= $menunggu ?> klaim menunggu dihubungi, <?= $dihubungi ?> klaim sedang diverifikasi.</p>

  <nav class="tab" aria-label="Bagian admin">
    <a href="admin.php?tab=klaim" class="<?= $tab === 'klaim' ? 'aktif' : '' ?>">Klaim</a>
    <a href="admin.php?tab=laporan" class="<?= $tab === 'laporan' ? 'aktif' : '' ?>">Laporan</a>
  </nav>

  <?php if ($tab === 'klaim'): ?>
    <form method="get" style="display:flex;gap:10px;max-width:360px;margin-bottom:16px">
      <input type="hidden" name="tab" value="klaim">
      <select name="status" aria-label="Filter status klaim">
        <option value="">Semua status</option>
        <?php foreach ($statusKlaim as $s): ?>
          <option value="<?= e($s) ?>" <?= $filter === $s ? 'selected' : '' ?>><?= e(label_status($s)) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="tombol tombol-garis">Saring</button>
    </form>
    <?php if (!$daftarKlaim): ?>
      <p class="kosong" style="padding:12px 0">Tidak ada klaim.</p>
    <?php else: ?>
      <div class="gulir"><table>
        <thead><tr><th>Barang</th><th>Pengklaim</th><th>Diajukan</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($daftarKlaim as $c): ?>
          <tr>
            <td><?= e($c['nama_barang']) ?></td>
            <td><?= e($c['nama_pengklaim']) ?></td>
            <td><?= e(tanggal_id($c['diajukan_pada'])) ?></td>
            <td><span class="status <?= e($c['status_klaim']) ?>"><?= e(label_status($c['status_klaim'])) ?></span></td>
            <td><a href="klaim_detail.php?id=<?= (int)$c['id_klaim'] ?>">Periksa</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>

  <?php else: ?>
    <div class="gulir"><table>
      <thead><tr><th>Barang</th><th>Jenis</th><th>Pelapor</th><th>Dibuat</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($daftarLaporan as $l): ?>
        <tr>
          <td><a href="detail.php?id=<?= (int)$l['id_laporan'] ?>"><?= e($l['nama_barang']) ?></a></td>
          <td><?= $l['jenis_laporan'] === 'kehilangan' ? 'Hilang' : 'Ditemukan' ?></td>
          <td><?= e($l['pelapor']) ?></td>
          <td><?= e(tanggal_id($l['dibuat_pada'])) ?></td>
          <td colspan="2">
            <form method="post" style="display:flex;gap:8px">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="status_laporan">
              <input type="hidden" name="id_laporan" value="<?= (int)$l['id_laporan'] ?>">
              <select name="status" aria-label="Status laporan <?= e($l['nama_barang']) ?>">
                <?php foreach ($statusLaporan as $s): ?>
                  <option value="<?= e($s) ?>" <?= $l['status_laporan'] === $s ? 'selected' : '' ?>><?= e(label_status($s)) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="tombol tombol-kecil tombol-garis">Simpan</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/footer.php'; ?>
