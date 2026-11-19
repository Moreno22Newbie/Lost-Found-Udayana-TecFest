# Lost&Found Udayana

Website pusat laporan barang hilang dan ditemukan untuk lingkungan Universitas Udayana.
Dibuat untuk TecFest 2026 divisi Software Engineering (tema: Campus Solution Sprint).

## Masalah yang diselesaikan
Informasi barang hilang dan ditemukan tersebar di grup WhatsApp, mudah tertimbun, dan tidak
menjangkau civitas fakultas lain. Website ini menyatukannya dalam satu tempat yang bisa dicari.

## Fitur
- Daftar dan masuk akun (kata sandi disimpan sebagai hash)
- Laporan barang hilang dan barang ditemukan, dengan foto
- Pencarian dan filter (jenis, kategori)
- Smart Matching sederhana: laporan lawan jenis dengan kategori sama, dinilai dari kemiripan nama barang, warna, dan lokasi
- Pengajuan klaim dengan bukti kepemilikan
- Chat verifikasi yang dimulai admin; pengguna baru bisa membalas setelah dihubungi
- Keputusan admin (setujui atau tolak); setelah disetujui, nomor penemu tampil untuk pengklaim
- Dashboard admin: kelola klaim dan ubah status laporan
- Ciri khusus barang bersifat privat (hanya pemilik laporan dan admin yang bisa melihat)

## Teknologi
HTML, CSS, JavaScript biasa, PHP native (PDO), MySQL. Tanpa framework dan tanpa Composer.

## Cara menjalankan (Laragon)
1. Salin folder proyek ke `C:\laragon\www\lost-found-udayana\`.
2. Start All di Laragon.
3. Buka `http://localhost/phpmyadmin`, lalu Import `database.sql`.
4. Jika perlu, sesuaikan `config.php` (DB_USER, DB_PASS).
5. Buka `http://localhost/lost-found-udayana/`.

## Membuat akun admin
1. Daftar akun biasa lewat halaman Daftar.
2. Di phpMyAdmin, buka tabel `pengguna`, ubah kolom `peran` akun itu menjadi `admin`.
3. Keluar lalu masuk lagi. Menu Admin akan muncul.

## Struktur file
| File | Fungsi |
|---|---|
| `index.php` | Beranda, daftar laporan, pencarian |
| `detail.php` | Detail laporan dan Smart Matching |
| `laporan_baru.php`, `edit_laporan.php` | Buat dan ubah laporan |
| `klaim.php`, `klaim_detail.php` | Ajukan klaim, chat verifikasi, keputusan admin |
| `akun.php`, `admin.php` | Halaman pengguna dan admin |
| `masuk.php`, `daftar.php`, `keluar.php` | Autentikasi |
| `config.php`, `db.php`, `fungsi.php`, `header.php`, `footer.php` | File bersama |
| `database.sql`, `style.css` | Database dan tampilan |

## Keamanan dasar
Prepared statements (PDO), `htmlspecialchars` untuk semua output, token CSRF di semua form POST,
`password_hash`, validasi input, dan upload foto dibatasi (JPG/PNG/WebP, maksimal 2 MB, nama acak).

## Pengembangan selanjutnya
Notifikasi email, chat real-time, Smart Matching berbasis kemiripan teks yang lebih canggih, dan masuk dengan akun SSO kampus.
