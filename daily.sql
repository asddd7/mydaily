-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 26 Feb 2026 pada 08.18
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `daily`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absen`
--

CREATE TABLE `absen` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `absen`
--

INSERT INTO `absen` (`id`, `user_id`, `tanggal`, `jam_masuk`, `jam_pulang`, `created_at`) VALUES
(1, 1, '2026-02-11', '16:58:17', '17:11:54', '2026-02-11 09:58:17'),
(2, 1, '2026-02-12', '07:24:52', NULL, '2026-02-12 00:24:52'),
(3, 1, '2026-02-13', '07:37:44', NULL, '2026-02-13 00:37:44'),
(4, 1, '2026-02-14', '08:01:56', '19:33:59', '2026-02-14 01:01:56'),
(5, 1, '2026-02-18', '07:58:06', NULL, '2026-02-18 00:58:06'),
(6, 1, '2026-02-20', '07:38:25', '17:27:59', '2026-02-20 00:38:25'),
(7, 1, '2026-02-21', '07:09:53', '17:06:48', '2026-02-21 00:09:53'),
(8, 1, '2026-02-23', '07:21:16', '17:17:37', '2026-02-23 00:21:16'),
(9, 1, '2026-02-24', '07:16:28', NULL, '2026-02-24 00:16:28'),
(10, 1, '2026-02-25', '12:59:50', '17:27:57', '2026-02-25 05:59:50'),
(11, 1, '2026-02-26', '07:17:51', NULL, '2026-02-26 00:17:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `achievements`
--

CREATE TABLE `achievements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `unlocked_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `achievements`
--

INSERT INTO `achievements` (`id`, `user_id`, `title`, `description`, `unlocked_at`) VALUES
(1, 1, 'Quest Master: 5', 'Menyelesaikan 5 quest harian!', '2026-02-21 16:23:13'),
(2, 1, 'Quest Master: 5', 'Menyelesaikan 5 quest harian!', '2026-02-24 09:50:35');

-- --------------------------------------------------------

--
-- Struktur dari tabel `backup_files`
--

CREATE TABLE `backup_files` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `custom_name` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `backup_files`
--

INSERT INTO `backup_files` (`id`, `user_id`, `file_name`, `custom_name`, `uploaded_at`) VALUES
(8, 1, '1770956269_Data karyawan.xlsx', 'karyawan data', '2026-02-13 11:17:49'),
(10, 1, '1771034167_SOSRO SIMPANG RAYA.xlsx', 'SOSRO SIMPANG RAYA', '2026-02-14 08:56:07'),
(11, 1, '1771056920_Data karyawan(3).xlsx', 'DATA KARYAWAN 1', '2026-02-14 15:15:20'),
(12, 1, '1771577932_template_pricing_rules_long.xlsx', 'mydaily', '2026-02-20 15:58:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `calendar_marks`
--

CREATE TABLE `calendar_marks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `selesai` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `calendar_marks`
--

INSERT INTO `calendar_marks` (`id`, `user_id`, `tanggal`, `title`, `description`, `created_at`, `selesai`) VALUES
(3, 1, '2026-03-07', 'gajian', '', '2026-02-20 04:27:04', 0),
(18, 1, '2026-02-25', 'PIKET', '', '2026-02-21 07:49:27', 1),
(20, 1, '2026-02-23', 'UPDATE: CLOCK.PHP', '', '2026-02-21 08:08:26', 1),
(21, 1, '2026-02-24', 'UPDATE TASK.PHP', '', '2026-02-24 01:03:37', 1),
(22, 1, '2026-02-25', 'BERESIN TASK.PHP', 'ERROR SAAT MENHAPUS SUBTASK', '2026-02-25 00:15:38', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `daily_quest`
--

CREATE TABLE `daily_quest` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quest_title` varchar(255) NOT NULL,
  `is_done` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `daily_quest`
--

INSERT INTO `daily_quest` (`id`, `user_id`, `quest_title`, `is_done`, `created_at`) VALUES
(6, 2, 'Rencanakan menu makan', 0, '2026-02-21 16:41:55'),
(7, 2, 'Dengarkan podcast edukatif', 0, '2026-02-21 16:41:55'),
(8, 2, 'Tulis ide kreatif', 0, '2026-02-21 16:41:55'),
(9, 2, 'Refleksi hari ini', 0, '2026-02-21 16:41:55'),
(10, 2, 'Bersihkan inbox', 0, '2026-02-21 16:41:55'),
(16, 2, 'Tidur tepat waktu', 0, '2026-02-23 15:30:05'),
(17, 2, 'Tulis jurnal harian', 0, '2026-02-23 15:30:05'),
(18, 2, 'Evaluasi pengeluaran', 0, '2026-02-23 15:30:05'),
(19, 2, 'Bayar tagihan tepat waktu', 0, '2026-02-23 15:30:05'),
(20, 2, 'Selesaikan 1 task kerjaan', 0, '2026-02-23 15:30:05'),
(26, 1, 'Dengarkan podcast edukatif', 0, '2026-02-25 06:54:54'),
(27, 1, 'Tulis jurnal harian', 0, '2026-02-25 06:54:54'),
(28, 1, 'Update to-do list', 0, '2026-02-25 06:54:54'),
(29, 1, 'Cek social media max 30 menit', 0, '2026-02-25 06:54:54'),
(30, 1, 'Refleksi hari ini', 0, '2026-02-25 06:54:54'),
(31, 1, 'Bersihkan rumah 15 menit', 0, '2026-02-26 07:17:48'),
(32, 1, 'Berjalan kaki 5000 langkah', 0, '2026-02-26 07:17:48'),
(33, 1, 'Update to-do list', 0, '2026-02-26 07:17:48'),
(34, 1, 'Refleksi hari ini', 0, '2026-02-26 07:17:48'),
(35, 1, 'Selesaikan 1 task kerjaan', 0, '2026-02-26 07:17:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `daily_quest_master`
--

CREATE TABLE `daily_quest_master` (
  `id` int(11) NOT NULL,
  `quest_title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `daily_quest_master`
--

INSERT INTO `daily_quest_master` (`id`, `quest_title`) VALUES
(1, 'Minum 2 liter air'),
(2, 'Olahraga 30 menit'),
(3, 'Meditasi 10 menit'),
(4, 'Baca buku 20 halaman'),
(5, 'Belajar bahasa asing 15 menit'),
(6, 'Rapikan meja kerja'),
(7, 'Tulis jurnal harian'),
(8, 'Kirim email penting'),
(9, 'Bersihkan inbox'),
(10, 'Rencanakan menu makan'),
(11, 'Belanja kebutuhan harian'),
(12, 'Bayar tagihan tepat waktu'),
(13, 'Update to-do list'),
(14, 'Review target mingguan'),
(15, 'Selesaikan 1 task kerjaan'),
(16, 'Bersihkan rumah 15 menit'),
(17, 'Hubungi teman/family'),
(18, 'Tidur tepat waktu'),
(19, 'Sarapan sehat'),
(20, 'Evaluasi pengeluaran'),
(21, 'Belajar coding 30 menit'),
(22, 'Dengarkan podcast edukatif'),
(23, 'Cek keuangan aplikasi'),
(24, 'Rencanakan weekend'),
(25, 'Tulis ide kreatif'),
(26, 'Berjalan kaki 5000 langkah'),
(27, 'Minum vitamin'),
(28, 'Cek social media max 30 menit'),
(29, 'Baca berita 10 menit'),
(30, 'Refleksi hari ini');

-- --------------------------------------------------------

--
-- Struktur dari tabel `money_plan`
--

CREATE TABLE `money_plan` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `type` enum('income','expense') DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tanggal` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `money_plan`
--

INSERT INTO `money_plan` (`id`, `username`, `type`, `category`, `amount`, `description`, `tanggal`) VALUES
(8, 'Dim', 'income', 'Sisa Gaji', 120000.00, '', '2026-02-23'),
(9, 'Dim', 'expense', 'Ojek', 10000.00, '', '2026-02-23'),
(10, 'Dim', 'expense', 'Ojek', 10000.00, '', '2026-02-24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notes`
--

INSERT INTO `notes` (`id`, `user_id`, `title`, `content`, `created_at`, `updated_at`) VALUES
(6, 1, 'contohC', 'C', '2026-02-21 09:27:40', '2026-02-21 09:27:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `task_repeat`
--

CREATE TABLE `task_repeat` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `repeat_type` varchar(50) NOT NULL,
  `repeat_days` varchar(50) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `last_completed_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tugas`
--

CREATE TABLE `tugas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `nama_tugas` varchar(100) NOT NULL,
  `deadline` date NOT NULL,
  `selesai` tinyint(1) NOT NULL DEFAULT 0,
  `urutan` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tugas`
--

INSERT INTO `tugas` (`id`, `user_id`, `parent_id`, `nama_tugas`, `deadline`, `selesai`, `urutan`) VALUES
(10, 1, NULL, 'Masukin idos: saleh', '2026-02-12', 1, 10),
(11, 1, NULL, 'idos: bagas', '2026-02-12', 1, 11),
(12, 1, NULL, 'IDOS: MISKANA', '2026-02-12', 1, 12),
(20, 1, NULL, 'Routing Pengiriman', '2026-02-12', 1, 20),
(26, 1, 10, 'aa', '2026-02-20', 0, 26),
(27, 1, 10, 'aa', '2026-02-20', 0, 27),
(28, 1, 10, 'a', '2026-02-20', 0, 28),
(29, 1, 10, 'aaaaa', '2026-02-20', 1, 29),
(30, 1, 10, 'aaaaa', '2026-02-20', 0, 30),
(32, 1, NULL, 'OTSUKA', '2026-02-23', 1, 0),
(33, 1, 32, 'BERESIN IDOS PUNYA FIRMAN', '2026-02-23', 0, 33),
(34, 1, NULL, 'SOSRO CIREBON', '2026-02-23', 1, 1),
(35, 1, 34, 'BERESIN DISKON', '2026-02-23', 1, 35),
(36, 1, NULL, 'SOSRO AWN', '2026-02-23', 1, 2),
(37, 1, NULL, 'KINO', '2026-02-23', 1, 3),
(38, 1, NULL, 'DATABASE', '2026-02-23', 1, 4),
(39, 1, 38, 'GANTI NAMA SALES FURATUN > ARIS MAULANA, WAHYU > FURATUN (PINDAH KE MIX 1)', '2026-02-23', 1, 39),
(40, 1, 32, 'CEK OUTLET IDOS DARI PAK WENDI', '2026-02-23', 1, 40),
(41, 1, NULL, 'TAMBAH NOPOL', '2026-02-23', 1, 5),
(42, 1, 41, '58 FB  MBM.09263 - TOKO TOPIK PUTRA  45 DB MBM.03411 - WARUNG IBU IDA', '2026-02-23', 1, 42),
(43, 1, NULL, 'ACC RNO', '2026-02-24', 1, 1),
(44, 1, NULL, 'BENERIN LIST_PELANGGAN MBM (ASC BY ID)', '2026-02-24', 1, 0),
(45, 1, NULL, 'TAMBAH NOPOL', '2026-02-24', 1, 6),
(46, 1, 45, '58 FB  MBM.09263 - TOKO TOPIK PUTRA  45 DB MBM.03411 - WARUNG IBU IDA', '2026-02-24', 0, 46),
(47, 1, NULL, 'SOSRO CIREBON', '2026-02-24', 1, 5),
(48, 1, 47, 'BERESIN DISKON', '2026-02-24', 0, 48),
(49, 1, NULL, 'SOSRO AWN', '2026-02-24', 1, 3),
(50, 1, NULL, 'KINO', '2026-02-24', 1, 2),
(51, 1, NULL, 'DATABASE', '2026-02-24', 1, 4),
(52, 1, 51, 'GANTI NAMA SALES FURATUN > ARIS MAULANA, WAHYU > FURATUN (PINDAH KE MIX 1)', '2026-02-24', 0, 52),
(53, 1, NULL, 'ACC RNO', '2026-02-25', 0, 4),
(54, 1, NULL, 'KINO', '2026-02-25', 1, 0),
(55, 1, NULL, 'SOSRO AWN', '2026-02-25', 1, 1),
(56, 1, NULL, 'SOSRO CIREBON', '2026-02-25', 1, 2),
(61, 1, NULL, 'OTSUKA', '2026-02-25', 1, 3),
(63, 1, 53, 'aaaaa', '2026-02-25', 0, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `tiktok` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `sosmed_public` tinyint(1) DEFAULT 1,
  `points` int(11) DEFAULT 0,
  `clock_mode` varchar(20) DEFAULT 'clock',
  `countdown_seconds` int(11) DEFAULT 0,
  `pomodoro_work` int(11) DEFAULT 25,
  `pomodoro_break` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `instagram`, `facebook`, `twitter`, `tiktok`, `linkedin`, `sosmed_public`, `points`, `clock_mode`, `countdown_seconds`, `pomodoro_work`, `pomodoro_break`) VALUES
(1, 'Dim', 'dimas@gmail.com', '$2y$10$Nka6226wwMVL/alYeXQsceNl9meo4k6076wbDt2XvR0oDBFE3apOG', 'www.instagram.com', '', '', '', '', 1, -3, 'clock', 0, 25, 5),
(2, 'budi', 'budi123@gmail.com', '$2y$10$ZJw8kaqQHmKCmjKu9NIKO.l5/WmdO.Ik8duSVrtLZSwgJvEvoITUy', NULL, NULL, NULL, NULL, NULL, 1, 0, 'clock', 0, 25, 5);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absen`
--
ALTER TABLE `absen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_absen` (`user_id`,`tanggal`);

--
-- Indeks untuk tabel `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `backup_files`
--
ALTER TABLE `backup_files`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `calendar_marks`
--
ALTER TABLE `calendar_marks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `daily_quest`
--
ALTER TABLE `daily_quest`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `daily_quest_master`
--
ALTER TABLE `daily_quest_master`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `money_plan`
--
ALTER TABLE `money_plan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `task_repeat`
--
ALTER TABLE `task_repeat`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absen`
--
ALTER TABLE `absen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `backup_files`
--
ALTER TABLE `backup_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `calendar_marks`
--
ALTER TABLE `calendar_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `daily_quest`
--
ALTER TABLE `daily_quest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT untuk tabel `daily_quest_master`
--
ALTER TABLE `daily_quest_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `money_plan`
--
ALTER TABLE `money_plan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `task_repeat`
--
ALTER TABLE `task_repeat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `tugas`
--
ALTER TABLE `tugas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `calendar_marks`
--
ALTER TABLE `calendar_marks`
  ADD CONSTRAINT `calendar_marks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
