CREATE DATABASE IF NOT EXISTS manajemen_kas_rt;
USE manajemen_kas_rt;

CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('ketua', 'sekretaris', 'bendahara') DEFAULT 'bendahara'
);

CREATE TABLE IF NOT EXISTS warga (
    id_warga INT AUTO_INCREMENT PRIMARY KEY,
    no_kk VARCHAR(16) NOT NULL,
    nik VARCHAR(16) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    no_rumah VARCHAR(10) NOT NULL,
    rt_rw VARCHAR(7) NOT NULL DEFAULT '001/011',
    status_aktif ENUM('aktif', 'pindah', 'meninggal') DEFAULT 'aktif'
);

CREATE TABLE IF NOT EXISTS kategori_iuran (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_iuran VARCHAR(50) NOT NULL,
    tipe ENUM('wajib', 'sukarela') DEFAULT 'wajib',
    nominal_default DECIMAL(10,2) DEFAULT 0.00
);

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

CREATE TABLE IF NOT EXISTS kas_keluar (
    id_keluar INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_keluar DATE NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    keperluan TEXT NOT NULL,
    penanggung_jawab VARCHAR(100) NOT NULL
);

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

DELIMITER $$
CREATE FUNCTION HitungSaldoTotal()
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE t_masuk DECIMAL(10,2) DEFAULT 0.00;
    DECLARE t_keluar DECIMAL(10,2) DEFAULT 0.00;
    DECLARE saldo_akhir DECIMAL(10,2) DEFAULT 0.00;

    SELECT IFNULL(SUM(jumlah), 0) INTO t_masuk FROM kas_masuk;
    SELECT IFNULL(SUM(jumlah), 0) INTO t_keluar FROM kas_keluar;

    SET saldo_akhir = t_masuk - t_keluar;
    RETURN saldo_akhir;
END$$
DELIMITER ;

INSERT INTO users (username, password, nama, role) 
VALUES ('admin', '$2y$10$c7M59Wylw3.EUpIhyT2wI.09mHh48.330u3K3D6vQkK1b7F0hLp3G', 'Administrator', 'bendahara')
ON DUPLICATE KEY UPDATE id_user=id_user;
