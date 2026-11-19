-- Database Lost&Found Udayana (versi simpel, 5 tabel)
CREATE DATABASE IF NOT EXISTS lost_found_udayana
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lost_found_udayana;

-- 1. Pengguna (admin juga disimpan di sini, dibedakan lewat kolom peran)
CREATE TABLE pengguna (
  id_pengguna     INT PRIMARY KEY AUTO_INCREMENT,
  nama            VARCHAR(100) NOT NULL,
  email           VARCHAR(100) NOT NULL UNIQUE,
  nomor_telepon   VARCHAR(20)  NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,   -- hasil password_hash(), bukan password asli
  peran           ENUM('pengguna','admin') NOT NULL DEFAULT 'pengguna',
  dibuat_pada     DATETIME DEFAULT CURRENT_TIMESTAMP,
  diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Kategori barang (dipakai untuk filter)
CREATE TABLE kategori (
  id_kategori   INT PRIMARY KEY AUTO_INCREMENT,
  nama_kategori VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO kategori (nama_kategori) VALUES
  ('Elektronik'), ('Dokumen'), ('Aksesoris'), ('Pakaian'),
  ('Kendaraan'), ('Perlengkapan Kuliah'), ('Lainnya');

-- 3. Laporan (kehilangan dan penemuan dalam satu tabel)
CREATE TABLE laporan (
  id_laporan       INT PRIMARY KEY AUTO_INCREMENT,
  id_pengguna      INT NOT NULL,
  id_kategori      INT NOT NULL,
  jenis_laporan    ENUM('kehilangan','penemuan') NOT NULL,
  nama_barang      VARCHAR(100) NOT NULL,
  warna            VARCHAR(50) NULL,
  deskripsi_umum   TEXT NOT NULL,            -- tampil ke publik
  ciri_khusus      TEXT NULL,                -- PRIVAT: hanya untuk verifikasi
  lokasi_kejadian  VARCHAR(150) NOT NULL,
  tanggal_kejadian DATETIME NOT NULL,
  foto             VARCHAR(255) NULL,
  status_laporan   ENUM('aktif','dalam_proses','diklaim','dikembalikan','kedaluwarsa','ditutup')
                   NOT NULL DEFAULT 'aktif',
  dibuat_pada      DATETIME DEFAULT CURRENT_TIMESTAMP,
  kedaluwarsa_pada DATETIME NOT NULL,
  FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna),
  FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori),
  INDEX (jenis_laporan, status_laporan)
);

-- 4. Klaim (satu klaim = satu chat verifikasi)
CREATE TABLE klaim (
  id_klaim              INT PRIMARY KEY AUTO_INCREMENT,
  id_laporan_penemuan   INT NOT NULL,        -- harus laporan berjenis 'penemuan' (dicek di PHP)
  id_laporan_kehilangan INT NULL,            -- opsional, berjenis 'kehilangan' milik pengklaim
  id_pengguna           INT NOT NULL,        -- pengklaim
  id_admin_verifikator  INT NULL,            -- kosong sampai admin memeriksa
  nama_pengklaim        VARCHAR(100) NOT NULL,
  nomor_telepon         VARCHAR(20)  NOT NULL,
  email                 VARCHAR(100) NOT NULL,
  detail_kepemilikan    TEXT NOT NULL,
  status_klaim          ENUM('menunggu','dihubungi','disetujui','ditolak','selesai')
                        NOT NULL DEFAULT 'menunggu',
  catatan_admin         TEXT NULL,
  diajukan_pada         DATETIME DEFAULT CURRENT_TIMESTAMP,
  diperbarui_pada       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_laporan_penemuan)   REFERENCES laporan(id_laporan),
  FOREIGN KEY (id_laporan_kehilangan) REFERENCES laporan(id_laporan),
  FOREIGN KEY (id_pengguna)           REFERENCES pengguna(id_pengguna),
  FOREIGN KEY (id_admin_verifikator)  REFERENCES pengguna(id_pengguna)
);

-- 5. Pesan chat verifikasi (pengirim bisa admin atau pengguna)
CREATE TABLE pesan (
  id_pesan     INT PRIMARY KEY AUTO_INCREMENT,
  id_klaim     INT NOT NULL,
  id_pengirim  INT NOT NULL,
  isi_pesan    TEXT NOT NULL,
  dikirim_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
  sudah_dibaca TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (id_klaim)    REFERENCES klaim(id_klaim),
  FOREIGN KEY (id_pengirim) REFERENCES pengguna(id_pengguna),
  INDEX (id_klaim)
);
