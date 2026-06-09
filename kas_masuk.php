<?php
require_once 'config/auth_check.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = $_POST['nik'] ?? '';
    $nama_iuran = $_POST['nama_iuran'] ?? '';
    $tanggal_bayar = $_POST['tanggal_bayar'] ?? '';
    $jumlah = floatval($_POST['jumlah'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($nik !== '' && $nama_iuran !== '' && $tanggal_bayar !== '' && $jumlah > 0) {
        try {
            $stmt = $pdo->prepare("CALL TambahKasMasuk(?, ?, ?, ?, ?)");
            $stmt->execute([$nik, $nama_iuran, $tanggal_bayar, $jumlah, $keterangan]);
            $success = 'Penerimaan kas masuk berhasil dicatat!';
        } catch (PDOException $e) {
            $error = 'Gagal mencatat kas masuk: ' . $e->getMessage();
        }
    } else {
        $error = 'Semua field wajib diisi dengan benar, dan jumlah harus lebih dari 0!';
    }
}

$page_title = 'Kas Masuk - Kas RT';
include 'views/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Catat Pemasukan Kas (Kas Masuk)</h3>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="kas_masuk.php" method="POST">
        <div class="form-row">
            <div class="form-group">
                <label for="nik">Warga (Pembayar)</label>
                <select id="nik" name="nik" class="form-control" required>
                    <option value="">-- Pilih Warga --</option>
                    <?php
                    $wargaStmt = $pdo->query("SELECT nik, nama, no_rumah FROM warga WHERE status_aktif = 'aktif' ORDER BY nama ASC");
                    while ($w = $wargaStmt->fetch()) {
                        echo '<option value="' . htmlspecialchars($w['nik']) . '">' . htmlspecialchars($w['nama']) . ' (Rumah ' . htmlspecialchars($w['no_rumah']) . ')</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nama_iuran">Kategori Iuran</label>
                <select id="nama_iuran" name="nama_iuran" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php
                    $katStmt = $pdo->query("SELECT nama_iuran, nominal_default FROM kategori_iuran ORDER BY nama_iuran ASC");
                    while ($k = $katStmt->fetch()) {
                        echo '<option value="' . htmlspecialchars($k['nama_iuran']) . '" data-nominal="' . intval($k['nominal_default']) . '">' . htmlspecialchars($k['nama_iuran']) . ' (Default: Rp ' . number_format($k['nominal_default'], 0, ',', '.') . ')</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="tanggal_bayar">Tanggal Bayar</label>
                <input type="date" id="tanggal_bayar" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label for="jumlah">Jumlah Setoran (Rp)</label>
                <input type="number" id="jumlah" name="jumlah" class="form-control" min="1" step="1000" placeholder="Masukkan jumlah pembayaran" required>
            </div>
        </div>

        <div class="form-group">
            <label for="keterangan">Keterangan</label>
            <textarea id="keterangan" name="keterangan" class="form-control" rows="3" placeholder="Keterangan transaksi..."></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Riwayat Kas Masuk</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama Warga</th>
                    <th>Rumah</th>
                    <th>Kategori Iuran</th>
                    <th>Jumlah</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("
                    SELECT 
                        km.id_masuk,
                        km.tanggal_bayar,
                        w.nama AS nama_warga,
                        w.no_rumah,
                        ki.nama_iuran,
                        km.jumlah,
                        km.keterangan
                    FROM kas_masuk km
                    INNER JOIN kategori_iuran ki ON km.id_kategori = ki.id_kategori
                    LEFT JOIN warga w ON km.id_warga = w.id_warga
                    ORDER BY km.tanggal_bayar DESC, km.id_masuk DESC
                ");
                $no = 1;
                while ($row = $stmt->fetch()) {
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal_bayar'])) ?></td>
                        <td><?= htmlspecialchars($row['nama_warga'] ?? 'Warga Terhapus/Anonim') ?></td>
                        <td><?= htmlspecialchars($row['no_rumah'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['nama_iuran']) ?></td>
                        <td style="color: #4ade80; font-weight: 600;">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                        <td><?= htmlspecialchars($row['keterangan'] ?? '') ?></td>
                    </tr>
                <?php } ?>
                <?php if ($no === 1): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8;">Belum ada riwayat transaksi kas masuk.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('nama_iuran').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const nominal = selectedOption.getAttribute('data-nominal');
    if (nominal) {
        document.getElementById('jumlah').value = nominal;
    }
});
</script>

<?php include 'views/footer.php'; ?>
