-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 21 Feb 2026 pada 10.36
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
(7, 1, '2026-02-21', '07:09:53', NULL, '2026-02-21 00:09:53');

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
(1, 1, 'Quest Master: 5', 'Menyelesaikan 5 quest harian!', '2026-02-21 16:23:13');

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
(18, 1, '2026-02-25', 'PIKET', '', '2026-02-21 07:49:27', 0),
(20, 1, '2026-02-23', 'UPDATE: CLOCK.PHP', '', '2026-02-21 08:08:26', 0);

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
(1, 1, 'Belajar coding 30 menit', 1, '2026-02-21 16:21:57'),
(2, 1, 'Selesaikan 1 task kerjaan', 1, '2026-02-21 16:21:57'),
(3, 1, 'Olahraga 30 menit', 1, '2026-02-21 16:21:57'),
(4, 1, 'Bersihkan inbox', 1, '2026-02-21 16:21:57'),
(5, 1, 'Cek keuangan aplikasi', 1, '2026-02-21 16:21:57');

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
  `selesai` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tugas`
--

INSERT INTO `tugas` (`id`, `user_id`, `parent_id`, `nama_tugas`, `deadline`, `selesai`) VALUES
(10, 1, NULL, 'Masukin idos: saleh', '2026-02-12', 1),
(11, 1, NULL, 'idos: bagas', '2026-02-12', 1),
(12, 1, NULL, 'IDOS: MISKANA', '2026-02-12', 1),
(20, 1, NULL, 'Routing Pengiriman', '2026-02-12', 1),
(21, 1, NULL, 'tes', '2026-02-12', 1),
(22, 1, 21, '1', '2026-02-12', 1),
(23, 1, NULL, 'Pulang', '2026-02-20', 1),
(24, 1, NULL, 'coba', '2026-02-21', 1),
(25, 1, NULL, 'qq', '2026-02-20', 1),
(26, 1, 10, 'aa', '2026-02-20', 0),
(27, 1, 10, 'aa', '2026-02-20', 0),
(28, 1, 10, 'a', '2026-02-20', 0),
(29, 1, 10, 'aaaaa', '2026-02-20', 1),
(30, 1, 10, 'aaaaa', '2026-02-20', 0),
(31, 1, NULL, 'BACKUP', '2026-02-21', 1);

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
  `points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `instagram`, `facebook`, `twitter`, `tiktok`, `linkedin`, `sosmed_public`, `points`) VALUES
(1, 'Dim', 'dimas@gmail.com', '$2y$10$Nka6226wwMVL/alYeXQsceNl9meo4k6076wbDt2XvR0oDBFE3apOG', 'www.instagram.com', '', '', '', '', 1, -3),
(2, 'budi', 'budi123@gmail.com', '$2y$10$ZJw8kaqQHmKCmjKu9NIKO.l5/WmdO.Ik8duSVrtLZSwgJvEvoITUy', NULL, NULL, NULL, NULL, NULL, 1, 0);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `backup_files`
--
ALTER TABLE `backup_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `calendar_marks`
--
ALTER TABLE `calendar_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `daily_quest`
--
ALTER TABLE `daily_quest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `daily_quest_master`
--
ALTER TABLE `daily_quest_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `money_plan`
--
ALTER TABLE `money_plan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

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
