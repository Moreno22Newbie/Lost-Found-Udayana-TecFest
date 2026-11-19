<?php
// Fungsi bersama yang dipakai semua halaman.
require_once __DIR__ . '/db.php';

// Aman menampilkan teks di HTML (mencegah XSS)
function e($teks)
{
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// ---------- CSRF: token rahasia di setiap form POST ----------
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_cek()
{
    $kirim = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $kirim)) {
        http_response_code(400);
        exit('Sesi tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

// ---------- Pesan singkat satu kali tampil ----------
function flash($pesan = null)
{
    if ($pesan !== null) {
        $_SESSION['flash'] = $pesan;
        return null;
    }
    $ambil = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $ambil;
}

// ---------- Login ----------
function pengguna_sekarang()
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $cache = null;
    if (!empty($_SESSION['id_pengguna'])) {
        $st = ambilKoneksi()->prepare(
            'SELECT id_pengguna, nama, email, nomor_telepon, peran FROM pengguna WHERE id_pengguna = ?'
        );
        $st->execute([$_SESSION['id_pengguna']]);
        $cache = $st->fetch() ?: null;
    }
    return $cache;
}

function wajib_login()
{
    $p = pengguna_sekarang();
    if (!$p) {
        // simpan halaman tujuan agar setelah login kembali ke sini
        $_SESSION['kembali'] = basename($_SERVER['PHP_SELF'])
            . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        redirect('masuk.php');
    }
    return $p;
}

function wajib_admin()
{
    $p = wajib_login();
    if ($p['peran'] !== 'admin') {
        http_response_code(403);
        exit('Halaman ini hanya untuk admin.');
    }
    return $p;
}

function tidak_ditemukan()
{
    http_response_code(404);
    $judul = 'Tidak ditemukan';
    require __DIR__ . '/header.php';
    echo '<main class="wadah isi"><h1>Halaman tidak ditemukan</h1>'
       . '<p>Laporan atau halaman yang Anda cari tidak tersedia.</p>'
       . '<p><a href="index.php">Kembali ke beranda</a></p></main>';
    require __DIR__ . '/footer.php';
    exit;
}

// ---------- Tampilan ----------
function tanggal_id($waktu, $denganJam = false)
{
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($waktu);
    if (!$t) {
        return '';
    }
    $teks = date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
    if ($denganJam) {
        $teks .= ', ' . date('H.i', $t);
    }
    return $teks;
}

function label_status($status)
{
    return ucfirst(str_replace('_', ' ', $status));
}

function foto_html($foto, $nama)
{
    if ($foto) {
        return '<img src="uploads/' . e($foto) . '" alt="Foto ' . e($nama) . '" loading="lazy">';
    }
    return '<div class="tanpa-foto" aria-hidden="true">' . e(mb_strtoupper(mb_substr($nama, 0, 1))) . '</div>';
}

// ---------- Upload foto: jpg/png/webp, maks 2 MB, nama acak ----------
// Mengembalikan [namaFile atau null, pesanError atau null]
function upload_foto($berkas)
{
    if (empty($berkas['name'])) {
        return [null, null]; // foto tidak wajib
    }
    if ($berkas['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Foto gagal diunggah. Coba lagi.'];
    }
    if ($berkas['size'] > MAKS_FOTO) {
        return [null, 'Ukuran foto maksimal 2 MB.'];
    }
    $info = @getimagesize($berkas['tmp_name']);
    $tipe = $info['mime'] ?? '';
    $ekstensi = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($ekstensi[$tipe])) {
        return [null, 'Foto harus berformat JPG, PNG, atau WebP.'];
    }
    $nama = bin2hex(random_bytes(12)) . '.' . $ekstensi[$tipe];
    if (!move_uploaded_file($berkas['tmp_name'], UPLOAD_DIR . $nama)) {
        return [null, 'Foto gagal disimpan di server.'];
    }
    return [$nama, null];
}

// ---------- Smart Matching sederhana ----------
// Cari laporan lawan jenis (hilang <-> ditemukan) dengan kategori sama.
// Skor: +2 tiap kata nama barang yang sama, +1 warna sama, +1 tiap kata lokasi yang sama.
function kata_kunci($teks)
{
    $kata = preg_split('/\s+/', mb_strtolower(trim((string)$teks)));
    return array_values(array_filter($kata, function ($k) {
        return mb_strlen($k) >= 3;
    }));
}

function cari_kecocokan($laporan, $batas = 3)
{
    $lawan = $laporan['jenis_laporan'] === 'kehilangan' ? 'penemuan' : 'kehilangan';
    $st = ambilKoneksi()->prepare(
        "SELECT id_laporan, jenis_laporan, nama_barang, warna, lokasi_kejadian, tanggal_kejadian, foto
         FROM laporan
         WHERE jenis_laporan = ? AND id_kategori = ? AND id_laporan <> ?
           AND status_laporan = 'aktif' AND kedaluwarsa_pada > NOW()
         ORDER BY dibuat_pada DESC LIMIT 100"
    );
    $st->execute([$lawan, $laporan['id_kategori'], $laporan['id_laporan']]);

    $kataNama   = kata_kunci($laporan['nama_barang']);
    $kataLokasi = kata_kunci($laporan['lokasi_kejadian']);
    $warna      = mb_strtolower(trim((string)$laporan['warna']));
    $hasil = [];

    foreach ($st->fetchAll() as $baris) {
        $skor = 0;
        $namaLain   = mb_strtolower($baris['nama_barang']);
        $lokasiLain = mb_strtolower($baris['lokasi_kejadian']);
        foreach ($kataNama as $k) {
            if (mb_strpos($namaLain, $k) !== false) {
                $skor += 2;
            }
        }
        foreach ($kataLokasi as $k) {
            if (mb_strpos($lokasiLain, $k) !== false) {
                $skor += 1;
            }
        }
        if ($warna !== '' && $warna === mb_strtolower(trim((string)$baris['warna']))) {
            $skor += 1;
        }
        if ($skor >= 2) { // minimal ada kemiripan nama barang atau gabungan warna + lokasi
            $baris['skor'] = $skor;
            $hasil[] = $baris;
        }
    }
    usort($hasil, function ($a, $b) {
        return $b['skor'] <=> $a['skor'];
    });
    return array_slice($hasil, 0, $batas);
}
