DROP DATABASE IF EXISTS manajemen_kas_rt;
CREATE DATABASE manajemen_kas_rt;
USE manajemen_kas_rt;

-- Tabel Users (Pengurus)
CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('ketua', 'sekretaris', 'bendahara') DEFAULT 'bendahara',
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL
);

-- Tabel Warga
CREATE TABLE IF NOT EXISTS warga (
    id_warga INT AUTO_INCREMENT PRIMARY KEY,
    no_kk VARCHAR(16) NOT NULL,
    nik VARCHAR(16) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    no_rumah VARCHAR(10) NOT NULL,
    rt_rw VARCHAR(7) NOT NULL DEFAULT '001/011',
    status_aktif ENUM('aktif', 'pindah', 'meninggal') DEFAULT 'aktif'
);

-- Tabel Kategori Iuran
CREATE TABLE IF NOT EXISTS kategori_iuran (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_iuran VARCHAR(50) NOT NULL,
    tipe ENUM('wajib', 'sukarela') DEFAULT 'wajib',
    nominal_default DECIMAL(10,2) DEFAULT 0.00
);

-- Tabel Kas Masuk
CREATE TABLE IF NOT EXISTS kas_masuk (
    id_masuk INT AUTO_INCREMENT PRIMARY KEY,
    id_warga INT,
    id_kategori INT,
    tanggal_bayar DATE NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    keterangan TEXT,
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE SET NULL,
    FOREIGN KEY (id_kategori) REFERENCES kategori_iuran(id_kategori)
);

-- Tabel Kas Keluar
CREATE TABLE IF NOT EXISTS kas_keluar (
    id_keluar INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_keluar DATE NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    keperluan TEXT NOT NULL,
    penanggung_jawab VARCHAR(100) NOT NULL
);

-- Trigger: Cek Nominal Minus
DELIMITER $$
CREATE TRIGGER before_kas_masuk_insert
BEFORE INSERT ON kas_masuk
FOR EACH ROW
BEGIN
    IF NEW.jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Jumlah setoran kas masuk harus lebih dari 0!';
    END IF;
END$$
DELIMITER ;

-- Procedure: Input Kas Masuk Cepat
DELIMITER $$
CREATE PROCEDURE TambahKasMasuk(
    IN p_nik VARCHAR(16),
    IN p_nama_iuran VARCHAR(50),
    IN p_tanggal DATE,
    IN p_jumlah DECIMAL(10,2),
    IN p_keterangan TEXT
)
BEGIN
    DECLARE v_id_warga INT;
    DECLARE v_id_kategori INT;

    SELECT id_warga INTO v_id_warga FROM warga WHERE nik = p_nik;
    SELECT id_kategori INTO v_id_kategori FROM kategori_iuran WHERE nama_iuran = p_nama_iuran;

    IF v_id_warga IS NOT NULL AND v_id_kategori IS NOT NULL THEN
        INSERT INTO kas_masuk (id_warga, id_kategori, tanggal_bayar, jumlah, keterangan)
        VALUES (v_id_warga, v_id_kategori, p_tanggal, p_jumlah, p_keterangan);
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Data warga atau kategori iuran tidak ditemukan!';
    END IF;
END$$
DELIMITER ;


-- Seed default user (email: admin@admin.com, password: admin123)
INSERT INTO users (email, password, nama, role) 
VALUES ('admin@admin.com', '$2y$12$HckDkCX/21vqb68ErfAXDuckzoAlIMHwhcF3fPGzwW74D0g/h8DQO', 'Administrator', 'bendahara')
ON DUPLICATE KEY UPDATE id_user=id_user;
