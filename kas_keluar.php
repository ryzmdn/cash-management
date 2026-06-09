<?php
require_once 'config/auth_check.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal_keluar = $_POST['tanggal_keluar'] ?? '';
    $jumlah = floatval($_POST['jumlah'] ?? 0);
    $keperluan = trim($_POST['keperluan'] ?? '');
    $penanggung_jawab = trim($_POST['penanggung_jawab'] ?? '');

    if ($tanggal_keluar !== '' && $jumlah > 0 && $keperluan !== '' && $penanggung_jawab !== '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO kas_keluar (tanggal_keluar, jumlah, keperluan, penanggung_jawab) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tanggal_keluar, $jumlah, $keperluan, $penanggung_jawab]);
            $success = 'Pengeluaran kas keluar berhasil dicatat!';
        } catch (PDOException $e) {
            $error = 'Gagal mencatat kas keluar: ' . $e->getMessage();
        }
    } else {
        $error = 'Semua field wajib diisi dengan benar, dan jumlah harus lebih dari 0!';
    }
}

$page_title = 'Kas Keluar - Kas RT';
include 'views/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Catat Pengeluaran Kas (Kas Keluar)</h3>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="kas_keluar.php" method="POST">
        <div class="form-row">
            <div class="form-group">
                <label for="tanggal_keluar">Tanggal Pengeluaran</label>
                <input type="date" id="tanggal_keluar" name="tanggal_keluar" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label for="jumlah">Jumlah Pengeluaran (Rp)</label>
                <input type="number" id="jumlah" name="jumlah" class="form-control" min="1" step="1000" placeholder="Masukkan jumlah pengeluaran" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" style="grid-column: span 2;">
                <label for="penanggung_jawab">Penanggung Jawab</label>
                <input type="text" id="penanggung_jawab" name="penanggung_jawab" class="form-control" placeholder="Nama pengurus penanggung jawab" required>
            </div>
        </div>

        <div class="form-group">
            <label for="keperluan">Keperluan / Keterangan Pengeluaran</label>
            <textarea id="keperluan" name="keperluan" class="form-control" rows="3" placeholder="Untuk keperluan apa pengeluaran ini..." required></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Riwayat Kas Keluar</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Keperluan</th>
                    <th>Jumlah</th>
                    <th>Penanggung Jawab</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM kas_keluar ORDER BY tanggal_keluar DESC, id_keluar DESC");
                $no = 1;
                while ($row = $stmt->fetch()) {
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal_keluar'])) ?></td>
                        <td><?= htmlspecialchars($row['keperluan']) ?></td>
                        <td style="color: #fca5a5; font-weight: 600;">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                        <td><?= htmlspecialchars($row['penanggung_jawab']) ?></td>
                    </tr>
                <?php } ?>
                <?php if ($no === 1): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #94a3b8;">Belum ada riwayat transaksi kas keluar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'views/footer.php'; ?>
