<?php
require_once __DIR__ . '/fungsi.php';
$pengguna = wajib_login();
$db = ambilKoneksi();
$admin = $pengguna['peran'] === 'admin';
$idSaya = (int)$pengguna['id_pengguna'];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $db->prepare(
    'SELECT c.*, p.nama_barang, p.lokasi_kejadian, p.tanggal_kejadian, p.foto, p.ciri_khusus,
            u.nama AS nama_penemu, u.nomor_telepon AS telepon_penemu
     FROM klaim c
     JOIN laporan p ON p.id_laporan = c.id_laporan_penemuan
     JOIN pengguna u ON u.id_pengguna = p.id_pengguna
     WHERE c.id_klaim = ?'
);
$st->execute([$id]);
$k = $st->fetch();

if (!$k || (!$admin && (int)$k['id_pengguna'] !== $idSaya)) { tidak_ditemukan(); }

$error = '';
$status = $k['status_klaim'];
$idPenemuan = (int)$k['id_laporan_penemuan'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = $_POST['aksi'] ?? '';
    $isi = trim($_POST['isi'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    $tambahPesan = $db->prepare('INSERT INTO pesan (id_klaim, id_pengirim, isi_pesan) VALUES (?, ?, ?)');

    if ($aksi === 'hubungi' && $admin && $status === 'menunggu') {
        if ($isi === '' || mb_strlen($isi) > 2000) {
            $error = 'Tulis pesan pembuka (maksimal 2000 karakter).';
        } else {
            $tambahPesan->execute([$id, $idSaya, $isi]);
            $db->prepare("UPDATE klaim SET status_klaim = 'dihubungi', id_admin_verifikator = ? WHERE id_klaim = ?")->execute([$idSaya, $id]);
            $db->prepare("UPDATE laporan SET status_laporan = 'dalam_proses' WHERE id_laporan = ? AND status_laporan = 'aktif'")->execute([$idPenemuan]);
            flash('Chat dibuka. Pengklaim sekarang bisa membalas.');
            redirect('klaim_detail.php?id=' . $id);
        }
    } elseif ($aksi === 'kirim' && $status === 'dihubungi') {
        if ($isi === '' || mb_strlen($isi) > 2000) {
            $error = 'Pesan tidak boleh kosong (maksimal 2000 karakter).';
        } else {
            $tambahPesan->execute([$id, $idSaya, $isi]);
            redirect('klaim_detail.php?id=' . $id);
        }
    } elseif ($aksi === 'setujui' && $admin && $status === 'dihubungi') {
        $db->prepare("UPDATE klaim SET status_klaim = 'disetujui', catatan_admin = ?, id_admin_verifikator = ? WHERE id_klaim = ?")
           ->execute([$catatan ?: null, $idSaya, $id]);
        $db->prepare("UPDATE laporan SET status_laporan = 'diklaim' WHERE id_laporan = ?")->execute([$idPenemuan]);
        $db->prepare("UPDATE klaim SET status_klaim = 'ditolak', catatan_admin = 'Barang sudah diklaim pengguna lain.'
                      WHERE id_laporan_penemuan = ? AND id_klaim <> ? AND status_klaim IN ('menunggu','dihubungi')")
           ->execute([$idPenemuan, $id]);
        flash('Klaim disetujui. Nomor penemu kini tampil untuk pengklaim.');
        redirect('klaim_detail.php?id=' . $id);
    } elseif ($aksi === 'tolak' && $admin && in_array($status, ['menunggu', 'dihubungi'], true)) {
        $db->prepare("UPDATE klaim SET status_klaim = 'ditolak', catatan_admin = ?, id_admin_verifikator = ? WHERE id_klaim = ?")
           ->execute([$catatan ?: null, $idSaya, $id]);
        $sisa = $db->prepare("SELECT COUNT(*) FROM klaim WHERE id_laporan_penemuan = ? AND status_klaim IN ('dihubungi','disetujui')");
        $sisa->execute([$idPenemuan]);
        if ((int)$sisa->fetchColumn() === 0) {
            $db->prepare("UPDATE laporan SET status_laporan = 'aktif' WHERE id_laporan = ? AND status_laporan = 'dalam_proses'")->execute([$idPenemuan]);
        }
        flash('Klaim ditolak.');
        redirect('klaim_detail.php?id=' . $id);
    } elseif ($aksi === 'selesai' && $admin && $status === 'disetujui') {
        $db->prepare("UPDATE klaim SET status_klaim = 'selesai' WHERE id_klaim = ?")->execute([$id]);
        $db->prepare("UPDATE laporan SET status_laporan = 'dikembalikan' WHERE id_laporan = ?")->execute([$idPenemuan]);
        if ($k['id_laporan_kehilangan']) {
            $db->prepare("UPDATE laporan SET status_laporan = 'dikembalikan' WHERE id_laporan = ?")->execute([$k['id_laporan_kehilangan']]);
        }
        flash('Klaim ditandai selesai. Barang sudah dikembalikan.');
        redirect('klaim_detail.php?id=' . $id);
    }
}

$pesan = [];
if ($status !== 'menunggu') {
    $ps = $db->prepare(
        'SELECT m.*, u.nama, u.peran FROM pesan m JOIN pengguna u ON u.id_pengguna = m.id_pengirim
         WHERE m.id_klaim = ? ORDER BY m.dikirim_pada, m.id_pesan'
    );
    $ps->execute([$id]);
    $pesan = $ps->fetchAll();
    $db->prepare('UPDATE pesan SET sudah_dibaca = 1 WHERE id_klaim = ? AND id_pengirim <> ?')->execute([$id, $idSaya]);
}
$telepon = preg_replace('/[^0-9+]/', '', $k['telepon_penemu']);

$judul = 'Klaim: ' . $k['nama_barang'];
require __DIR__ . '/header.php';
?>
<main class="wadah isi">
  <p><a href="<?= $admin ? 'admin.php' : 'akun.php' ?>">Kembali</a></p>
  <span class="status <?= e($status) ?>"><?= e(label_status($status)) ?></span>
  <h1 style="margin-top:10px">Klaim untuk <?= e($k['nama_barang']) ?></h1>
  <?php if ($error): ?><div class="galat" role="alert"><p><?= e($error) ?></p></div><?php endif; ?>

  <div class="dua-kolom">
    <div>
      <div class="panel">
        <h2>Barang yang ditemukan</h2>
        <dl class="rincian">
          <div><dt>Nama barang</dt><dd><?= e($k['nama_barang']) ?></dd></div>
          <div><dt>Lokasi ditemukan</dt><dd><?= e($k['lokasi_kejadian']) ?></dd></div>
          <div><dt>Waktu</dt><dd><?= e(tanggal_id($k['tanggal_kejadian'], true)) ?></dd></div>
        </dl>
        <p class="bantuan"><a href="detail.php?id=<?= $idPenemuan ?>">Lihat laporan</a></p>
      </div>

      <div class="panel">
        <h2>Data pengklaim</h2>
        <dl class="rincian">
          <div><dt>Nama</dt><dd><?= e($k['nama_pengklaim']) ?></dd></div>
          <div><dt>Diajukan</dt><dd><?= e(tanggal_id($k['diajukan_pada'], true)) ?></dd></div>
          <?php if ($admin): ?>
            <div><dt>Telepon</dt><dd><?= e($k['nomor_telepon']) ?></dd></div>
            <div><dt>Email</dt><dd><?= e($k['email']) ?></dd></div>
          <?php endif; ?>
        </dl>
        <h2 style="margin-top:16px">Bukti kepemilikan</h2>
        <p style="white-space:pre-line;margin:0"><?= e($k['detail_kepemilikan']) ?></p>
      </div>

      <?php if ($admin): ?>
        <div class="panel panel-rahasia">
          <h2>Ciri khusus dari penemu (rahasia)</h2>
          <p style="white-space:pre-line;margin:0"><?= e($k['ciri_khusus'] ?: 'Penemu tidak mengisi ciri khusus.') ?></p>
          <p class="bantuan">Cocokkan dengan bukti pengklaim di atas, jangan dibagikan ke pengklaim.</p>
        </div>
      <?php endif; ?>
    </div>

    <div>
      <?php if (in_array($status, ['disetujui', 'selesai'], true)): ?>
        <div class="panel panel-kuning">
          <h2>Klaim disetujui</h2>
          <p style="margin-top:0">Hubungi penemu untuk mengambil barang:</p>
          <p style="margin:0"><strong><?= e($k['nama_penemu']) ?></strong><br>
            <a href="tel:<?= e($telepon) ?>"><?= e($k['telepon_penemu']) ?></a></p>
          <?php if ($k['catatan_admin']): ?><p class="bantuan">Catatan admin: <?= e($k['catatan_admin']) ?></p><?php endif; ?>
        </div>
      <?php elseif ($status === 'ditolak'): ?>
        <div class="panel">
          <h2>Klaim ditolak</h2>
          <p style="margin:0"><?= e($k['catatan_admin'] ?: 'Bukti kepemilikan belum cukup untuk memastikan barang ini milik Anda.') ?></p>
        </div>
      <?php endif; ?>

      <div class="panel">
        <h2>Chat verifikasi</h2>
        <?php if ($status === 'menunggu'): ?>
          <?php if ($admin): ?>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $id ?>">
              <div class="baris-form">
                <label for="isi">Pesan pembuka untuk pengklaim</label>
                <textarea id="isi" name="isi" required maxlength="2000" placeholder="Contoh: Halo, kami ingin memastikan barang ini milik Anda. Bisa sebutkan..."></textarea>
              </div>
              <button type="submit" name="aksi" value="hubungi" class="tombol">Hubungi pengklaim</button>
            </form>
          <?php else: ?>
            <p style="margin:0">Menunggu admin menghubungi Anda. Chat terbuka setelah admin mengirim pesan pertama, jadi cek halaman ini secara berkala.</p>
          <?php endif; ?>
        <?php else: ?>
          <div class="chat" aria-live="polite">
            <?php foreach ($pesan as $m): ?>
              <div class="gelembung <?= (int)$m['id_pengirim'] === $idSaya ? 'milik-saya' : '' ?>">
                <div class="nama"><?= $m['peran'] === 'admin' ? 'Admin' : e($m['nama']) ?></div>
                <div class="isi-pesan"><?= e($m['isi_pesan']) ?></div>
                <div class="waktu"><?= e(tanggal_id($m['dikirim_pada'], true)) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if ($status === 'dihubungi'): ?>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $id ?>">
              <div class="baris-form">
                <label for="isi">Balas</label>
                <textarea id="isi" name="isi" required maxlength="2000" style="min-height:80px"></textarea>
              </div>
              <button type="submit" name="aksi" value="kirim" class="tombol">Kirim pesan</button>
            </form>
          <?php else: ?>
            <p class="bantuan">Percakapan ditutup.</p>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <?php if ($admin && in_array($status, ['menunggu', 'dihubungi', 'disetujui'], true)): ?>
        <div class="panel">
          <h2>Keputusan admin</h2>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <?php if ($status !== 'disetujui'): ?>
              <div class="baris-form">
                <label for="catatan">Catatan untuk pengklaim (opsional)</label>
                <textarea id="catatan" name="catatan" style="min-height:70px"></textarea>
              </div>
              <div class="tombol-baris">
                <?php if ($status === 'dihubungi'): ?>
                  <button type="submit" name="aksi" value="setujui" class="tombol" data-konfirmasi="Setujui klaim ini? Nomor penemu akan tampil untuk pengklaim.">Setujui</button>
                <?php endif; ?>
                <button type="submit" name="aksi" value="tolak" class="tombol tombol-bahaya" data-konfirmasi="Tolak klaim ini?">Tolak</button>
              </div>
              <?php if ($status === 'menunggu'): ?><p class="bantuan">Setujui tersedia setelah Anda menghubungi pengklaim lewat chat.</p><?php endif; ?>
            <?php else: ?>
              <button type="submit" name="aksi" value="selesai" class="tombol" data-konfirmasi="Tandai barang sudah dikembalikan?">Tandai selesai</button>
            <?php endif; ?>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php require __DIR__ . '/footer.php'; ?>
