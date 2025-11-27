-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 27 Nov 2025 pada 03.11
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `delima`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`) VALUES
(1, 'admin', 'password');

-- --------------------------------------------------------

--
-- Struktur dari tabel `distribusi`
--

CREATE TABLE `distribusi` (
  `id_distribusi` int(11) NOT NULL,
  `nama_toko` varchar(100) DEFAULT NULL,
  `tanggal_distribusi` date DEFAULT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah_pesanan` int(11) DEFAULT NULL,
  `tanggal_pesanan` date DEFAULT NULL,
  `status_pengiriman` varchar(50) DEFAULT NULL,
  `nama_distributor` varchar(100) DEFAULT NULL,
  `alamat_distributor` varchar(255) DEFAULT NULL,
  `id_distributor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `distribusi_detail`
--

CREATE TABLE `distribusi_detail` (
  `id_detail` int(11) NOT NULL,
  `id_distribusi` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `id_stok` int(11) NOT NULL,
  `jumlah_kg` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal`
--

CREATE TABLE `jadwal` (
  `id_jadwal` int(11) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `waktu` time DEFAULT NULL,
  `waktu_mulai` time DEFAULT NULL,
  `waktu_selesai` time DEFAULT NULL,
  `jenis_kegiatan` varchar(100) DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan`
--

CREATE TABLE `laporan` (
  `id_laporan` int(11) NOT NULL,
  `kategori_laporan` varchar(50) DEFAULT NULL,
  `periode_laporan` varchar(50) DEFAULT NULL,
  `total_pesanan` int(11) DEFAULT NULL,
  `total_dikemas` int(11) DEFAULT NULL,
  `total_stok` int(11) DEFAULT NULL,
  `total_distribusi` int(11) DEFAULT NULL,
  `total_reject` int(11) DEFAULT NULL,
  `total_gaji` int(11) DEFAULT NULL,
  `total_produksi` int(11) DEFAULT NULL,
  `rekap_jadwal` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pekerja_lepas`
--

CREATE TABLE `pekerja_lepas` (
  `id_pekerja` int(11) NOT NULL,
  `nama_pekerja` varchar(100) NOT NULL,
  `kontak` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `status_pembayaran` varchar(50) DEFAULT NULL,
  `id_admin` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pekerja_lepas`
--

INSERT INTO `pekerja_lepas` (`id_pekerja`, `nama_pekerja`, `kontak`, `alamat`, `status_pembayaran`, `id_admin`) VALUES
(1, 'Zahwa', '085729269751', 'Krasak', 'Dibayar', 1),
(2, 'Fatimah', '088216476342', 'Krasak', 'Dibayar', 1),
(3, 'Wanti', '082164903970', 'Krasak', 'Dibayar', 1),
(4, 'Ardi', '082232192297', 'Krasak', 'Dibayar', 1),
(5, 'Edwin', '089922347764', 'Krasak', 'Dibayar', 1),
(6, 'Yudhis', '087766547789', 'Krasak', 'Dibayar', 1),
(7, 'Salma', '085729269751', 'Krasak', 'Dibayar', 1),
(9, 'Kalista', '086326534778', 'Krasak', 'Dibayar', 1),
(10, 'Anas', '086675432311', 'Krasak', 'Belum Dibayar', 1),
(11, 'Risma', '088543651200', 'Krasak', 'Belum Dibayar', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran_gaji`
--

CREATE TABLE `pembayaran_gaji` (
  `id_pembayaran` int(11) NOT NULL,
  `id_pekerja` int(11) NOT NULL,
  `tanggal_pembayaran` date DEFAULT NULL,
  `upah_per_kg` int(11) DEFAULT NULL,
  `berat_dikemas_kg` decimal(10,2) DEFAULT NULL,
  `total_gaji` int(11) DEFAULT NULL,
  `status_pembayaran` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengambilan_stok`
--

CREATE TABLE `pengambilan_stok` (
  `id_pengambilan` int(11) NOT NULL,
  `id_stok` int(11) NOT NULL,
  `id_pekerja` int(11) NOT NULL,
  `jumlah_diambil` int(11) DEFAULT NULL,
  `tanggal_pengambilan` date DEFAULT NULL,
  `jumlah_ambil` int(11) DEFAULT NULL,
  `tanggal_ambil` datetime DEFAULT NULL,
  `keperluan` varchar(100) DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengambilan_stok_pekerja`
--

CREATE TABLE `pengambilan_stok_pekerja` (
  `id_pengambilan` int(11) NOT NULL,
  `id_pekerja` int(11) DEFAULT NULL,
  `id_stok` int(11) DEFAULT NULL,
  `tanggal_ambil` date DEFAULT NULL,
  `jumlah_kg` int(11) DEFAULT NULL,
  `total_gaji` bigint(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `nama_produk` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id_produk`, `nama_produk`) VALUES
(4602, 'Agar-Agar Pita'),
(4901, 'Agar-Agar Pelangi');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produksi`
--

CREATE TABLE `produksi` (
  `id_produksi` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `id_jadwal` int(11) DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `jumlah_produksi` int(11) DEFAULT NULL,
  `tgl_produksi` date DEFAULT NULL,
  `jumlah_dikemas` int(11) DEFAULT NULL,
  `jumlah_reject` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `produksi`
--
DELIMITER $$
CREATE TRIGGER `trg_produksi_fill_date_ins` BEFORE INSERT ON `produksi` FOR EACH ROW BEGIN
  IF NEW.tgl_produksi IS NULL AND NEW.id_jadwal IS NOT NULL THEN
    SET NEW.tgl_produksi = (
      SELECT tanggal FROM jadwal WHERE id_jadwal = NEW.id_jadwal LIMIT 1
    );
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_produksi_fill_date_upd` BEFORE UPDATE ON `produksi` FOR EACH ROW BEGIN
  IF (NEW.tgl_produksi IS NULL OR NEW.tgl_produksi = '') AND NEW.id_jadwal IS NOT NULL THEN
    SET NEW.tgl_produksi = (
      SELECT tanggal FROM jadwal WHERE id_jadwal = NEW.id_jadwal LIMIT 1
    );
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_gaji`
--

CREATE TABLE `riwayat_gaji` (
  `id_gaji` int(11) NOT NULL,
  `id_pekerja` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `berat_barang_kg` decimal(10,2) DEFAULT NULL,
  `tarif_per_kg` int(11) DEFAULT NULL,
  `total_gaji` bigint(20) DEFAULT NULL,
  `keterangan` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `stok`
--

CREATE TABLE `stok` (
  `id_stok` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `id_produksi` int(11) DEFAULT NULL,
  `jumlah_stok` int(11) DEFAULT NULL,
  `sisa_stok` int(11) DEFAULT NULL,
  `status_stok` varchar(50) DEFAULT NULL,
  `jumlah_reject` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_produksi_fix`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_produksi_fix` (
`id_produksi` int(11)
,`id_produk` int(11)
,`id_jadwal` int(11)
,`jumlah_produksi` int(11)
,`jumlah_dikemas` int(11)
,`jumlah_reject` int(11)
,`tanggal_produksi` date
);

-- --------------------------------------------------------

--
-- Struktur untuk view `v_produksi_fix`
--
DROP TABLE IF EXISTS `v_produksi_fix`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_produksi_fix`  AS SELECT `pr`.`id_produksi` AS `id_produksi`, `pr`.`id_produk` AS `id_produk`, `pr`.`id_jadwal` AS `id_jadwal`, `pr`.`jumlah_produksi` AS `jumlah_produksi`, `pr`.`jumlah_dikemas` AS `jumlah_dikemas`, `pr`.`jumlah_reject` AS `jumlah_reject`, coalesce(`pr`.`tgl_produksi`,`j`.`tanggal`) AS `tanggal_produksi` FROM (`produksi` `pr` left join `jadwal` `j` on(`j`.`id_jadwal` = `pr`.`id_jadwal`)) ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`);

--
-- Indeks untuk tabel `distribusi`
--
ALTER TABLE `distribusi`
  ADD PRIMARY KEY (`id_distribusi`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `distribusi_detail`
--
ALTER TABLE `distribusi_detail`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_distribusi` (`id_distribusi`),
  ADD KEY `id_produk` (`id_produk`),
  ADD KEY `id_stok` (`id_stok`);

--
-- Indeks untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `fk_jadwal_admin` (`id_admin`);

--
-- Indeks untuk tabel `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id_laporan`);

--
-- Indeks untuk tabel `pekerja_lepas`
--
ALTER TABLE `pekerja_lepas`
  ADD PRIMARY KEY (`id_pekerja`);

--
-- Indeks untuk tabel `pembayaran_gaji`
--
ALTER TABLE `pembayaran_gaji`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `id_pekerja` (`id_pekerja`);

--
-- Indeks untuk tabel `pengambilan_stok`
--
ALTER TABLE `pengambilan_stok`
  ADD PRIMARY KEY (`id_pengambilan`),
  ADD KEY `id_stok` (`id_stok`),
  ADD KEY `id_pekerja` (`id_pekerja`);

--
-- Indeks untuk tabel `pengambilan_stok_pekerja`
--
ALTER TABLE `pengambilan_stok_pekerja`
  ADD PRIMARY KEY (`id_pengambilan`),
  ADD KEY `id_pekerja` (`id_pekerja`),
  ADD KEY `id_stok` (`id_stok`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`);

--
-- Indeks untuk tabel `produksi`
--
ALTER TABLE `produksi`
  ADD PRIMARY KEY (`id_produksi`),
  ADD KEY `id_produk` (`id_produk`),
  ADD KEY `fk_produksi_jadwal` (`id_jadwal`);

--
-- Indeks untuk tabel `riwayat_gaji`
--
ALTER TABLE `riwayat_gaji`
  ADD PRIMARY KEY (`id_gaji`),
  ADD KEY `id_pekerja` (`id_pekerja`);

--
-- Indeks untuk tabel `stok`
--
ALTER TABLE `stok`
  ADD PRIMARY KEY (`id_stok`),
  ADD KEY `id_produk` (`id_produk`),
  ADD KEY `fk_stok_produksi` (`id_produksi`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `distribusi`
--
ALTER TABLE `distribusi`
  MODIFY `id_distribusi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `distribusi_detail`
--
ALTER TABLE `distribusi_detail`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pekerja_lepas`
--
ALTER TABLE `pekerja_lepas`
  MODIFY `id_pekerja` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `pembayaran_gaji`
--
ALTER TABLE `pembayaran_gaji`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengambilan_stok`
--
ALTER TABLE `pengambilan_stok`
  MODIFY `id_pengambilan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengambilan_stok_pekerja`
--
ALTER TABLE `pengambilan_stok_pekerja`
  MODIFY `id_pengambilan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4902;

--
-- AUTO_INCREMENT untuk tabel `produksi`
--
ALTER TABLE `produksi`
  MODIFY `id_produksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `riwayat_gaji`
--
ALTER TABLE `riwayat_gaji`
  MODIFY `id_gaji` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `stok`
--
ALTER TABLE `stok`
  MODIFY `id_stok` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `distribusi`
--
ALTER TABLE `distribusi`
  ADD CONSTRAINT `distribusi_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Ketidakleluasaan untuk tabel `distribusi_detail`
--
ALTER TABLE `distribusi_detail`
  ADD CONSTRAINT `distribusi_detail_ibfk_1` FOREIGN KEY (`id_distribusi`) REFERENCES `distribusi` (`id_distribusi`) ON DELETE CASCADE,
  ADD CONSTRAINT `distribusi_detail_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`),
  ADD CONSTRAINT `distribusi_detail_ibfk_3` FOREIGN KEY (`id_stok`) REFERENCES `stok` (`id_stok`);

--
-- Ketidakleluasaan untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `fk_jadwal_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`);

--
-- Ketidakleluasaan untuk tabel `pembayaran_gaji`
--
ALTER TABLE `pembayaran_gaji`
  ADD CONSTRAINT `pembayaran_gaji_ibfk_1` FOREIGN KEY (`id_pekerja`) REFERENCES `pekerja_lepas` (`id_pekerja`);

--
-- Ketidakleluasaan untuk tabel `pengambilan_stok`
--
ALTER TABLE `pengambilan_stok`
  ADD CONSTRAINT `pengambilan_stok_ibfk_1` FOREIGN KEY (`id_stok`) REFERENCES `stok` (`id_stok`),
  ADD CONSTRAINT `pengambilan_stok_ibfk_2` FOREIGN KEY (`id_pekerja`) REFERENCES `pekerja_lepas` (`id_pekerja`);

--
-- Ketidakleluasaan untuk tabel `pengambilan_stok_pekerja`
--
ALTER TABLE `pengambilan_stok_pekerja`
  ADD CONSTRAINT `pengambilan_stok_pekerja_ibfk_1` FOREIGN KEY (`id_pekerja`) REFERENCES `pekerja_lepas` (`id_pekerja`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengambilan_stok_pekerja_ibfk_2` FOREIGN KEY (`id_stok`) REFERENCES `stok` (`id_stok`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `produksi`
--
ALTER TABLE `produksi`
  ADD CONSTRAINT `fk_produksi_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal` (`id_jadwal`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `produksi_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Ketidakleluasaan untuk tabel `riwayat_gaji`
--
ALTER TABLE `riwayat_gaji`
  ADD CONSTRAINT `riwayat_gaji_ibfk_1` FOREIGN KEY (`id_pekerja`) REFERENCES `pekerja_lepas` (`id_pekerja`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `stok`
--
ALTER TABLE `stok`
  ADD CONSTRAINT `fk_stok_produksi` FOREIGN KEY (`id_produksi`) REFERENCES `produksi` (`id_produksi`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `stok_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
