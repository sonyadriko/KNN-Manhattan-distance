-- Database: knn
-- KNN Sistem Rekomendasi Pemilihan Helm

CREATE DATABASE IF NOT EXISTS `knn`;
USE `knn`;

-- Tabel users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO `users` (`username`, `password`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Tabel helm
CREATE TABLE `helm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_helm` varchar(100) NOT NULL,
  `merk` varchar(50) NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `berat` decimal(5,2) NOT NULL,
  `material` varchar(50) NOT NULL,
  `warna` varchar(30) NOT NULL,
  `ukuran` varchar(10) NOT NULL,
  `rating_keamanan` decimal(3,2) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Tabel kriteria
CREATE TABLE `kriteria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kriteria` varchar(50) NOT NULL,
  `kode_kriteria` varchar(5) NOT NULL,
  `bobot` decimal(5,4) NOT NULL,
  `jenis` enum('benefit','cost') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_kriteria` (`kode_kriteria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert kriteria default
INSERT INTO `kriteria` (`nama_kriteria`, `kode_kriteria`, `bobot`, `jenis`) VALUES
('Harga', 'C1', 0.2500, 'cost'),
('Berat', 'C2', 0.1500, 'cost'),
('Rating Keamanan', 'C3', 0.4000, 'benefit'),
('Material', 'C4', 0.1000, 'benefit'),
('Merk', 'C5', 0.1000, 'benefit');

-- Tabel rekomendasi
CREATE TABLE `rekomendasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nama_user` varchar(100) NOT NULL,
  `budget_min` decimal(12,2) NOT NULL,
  `budget_max` decimal(12,2) NOT NULL,
  `ukuran_kepala` varchar(10) NOT NULL,
  `preferensi_warna` varchar(30) DEFAULT NULL,
  `preferensi_merk` varchar(50) DEFAULT NULL,
  `hasil_rekomendasi` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `rekomendasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Tabel detail_rekomendasi
CREATE TABLE `detail_rekomendasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rekomendasi_id` int(11) NOT NULL,
  `helm_id` int(11) NOT NULL,
  `jarak_euclidean` decimal(10,6) NOT NULL,
  `ranking` int(3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rekomendasi_id` (`rekomendasi_id`),
  KEY `helm_id` (`helm_id`),
  CONSTRAINT `detail_rekomendasi_ibfk_1` FOREIGN KEY (`rekomendasi_id`) REFERENCES `rekomendasi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detail_rekomendasi_ibfk_2` FOREIGN KEY (`helm_id`) REFERENCES `helm` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Sample data helm
INSERT INTO `helm` (`nama_helm`, `merk`, `harga`, `berat`, `material`, `warna`, `ukuran`, `rating_keamanan`) VALUES
('KYT Falcon', 'KYT', 450000.00, 1.40, 'ABS', 'Hitam', 'L', 4.5),
('INK Centro Jet', 'INK', 320000.00, 1.20, 'ABS', 'Putih', 'M', 4.2),
('ZEUS ZS-610K', 'ZEUS', 680000.00, 1.50, 'Polycarbonate', 'Merah', 'XL', 4.8),
('AGV K1', 'AGV', 1200000.00, 1.35, 'Thermoplastic', 'Biru', 'L', 4.9),
('Shoei J-Cruise', 'SHOEI', 2500000.00, 1.25, 'AIM+', 'Hitam', 'M', 5.0);

-- Tabel training data untuk KNN Classification
CREATE TABLE `knn_training_data` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `no_data` INT NOT NULL,
    `merk` VARCHAR(50) NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `jenis` ENUM('Fullface', 'Halfface', 'Retro', 'Modular') NOT NULL,
    `harga` INT NOT NULL,
    `standar` VARCHAR(100) NOT NULL,
    `kaca` ENUM('Ya', 'No') NOT NULL DEFAULT 'Ya',
    `double_visor` ENUM('Ya', 'No') NOT NULL DEFAULT 'No',
    `ventilasi_udara` ENUM('Ya', 'No') NOT NULL DEFAULT 'Ya',
    `berat` DECIMAL(3,1) NOT NULL,
    `wire_lock` ENUM('Ya', 'No') NOT NULL DEFAULT 'No',
    `kelas` ENUM('Mahal', 'Murah') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert training data untuk KNN
INSERT INTO `knn_training_data` (`no_data`, `merk`, `nama`, `jenis`, `harga`, `standar`, `kaca`, `double_visor`, `ventilasi_udara`, `berat`, `wire_lock`, `kelas`) VALUES
(4, 'KYT', 'Vendetta 2', 'Fullface', 1499000, 'SNI, DOT', 'Ya', 'Ya', 'Ya', 1.6, 'No', 'Mahal'),
(19, 'KYT', 'Elsico', 'Halfface', 375000, 'SNI, DOT', 'Ya', 'No', 'Ya', 1.2, 'Ya', 'Murah'),
(30, 'KYT', 'Voodo', 'Retro', 435000, 'SNI, DOT', 'Ya', 'No', 'No', 1.2, 'No', 'Mahal'),
(41, 'INK', 'MF-1', 'Modular', 560000, 'SNI, DOT', 'Ya', 'Ya', 'Ya', 1.6, 'Ya', 'Murah'),
(53, 'INK', 'Dynamic', 'Halfface', 385000, 'SNI, DOT', 'Ya', 'No', 'Ya', 1.5, 'No', 'Murah'),
(59, 'INK', 'Trooper', 'Fullface', 650000, 'SNI, DOT', 'Ya', 'No', 'Ya', 1.2, 'No', 'Mahal'),
(63, 'RSV', 'Windtail Ryujin', 'Halfface', 600000, 'SNI, DOT, ECE', 'Ya', 'No', 'Ya', 1.3, 'No', 'Mahal'),
(86, 'MDS', 'Zarra', 'Halfface', 335000, 'SNI', 'Ya', 'Ya', 'Ya', 1.4, 'Ya', 'Murah'),
(117, 'NJS', 'Kairoz', 'Halfface', 585000, 'SNI', 'Ya', 'No', 'Ya', 1.4, 'No', 'Murah'),
(121, 'NJS', 'Shadow', 'Fullface', 495000, 'SNI', 'Ya', 'Ya', 'Ya', 1.5, 'No', 'Murah');

-- Tabel untuk menyimpan history prediksi KNN
CREATE TABLE `knn_predictions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `harga` INT NOT NULL,
    `standar` VARCHAR(100) NOT NULL,
    `kaca` ENUM('Ya', 'No') NOT NULL,
    `double_visor` ENUM('Ya', 'No') NOT NULL,
    `ventilasi_udara` ENUM('Ya', 'No') NOT NULL,
    `berat` DECIMAL(3,1) NOT NULL,
    `wire_lock` ENUM('Ya', 'No') NOT NULL,
    `predicted_class` ENUM('Mahal', 'Murah') NOT NULL,
    `confidence_score` DECIMAL(5,4),
    `k_value` INT DEFAULT 3,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;