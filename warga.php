<?php
require_once 'config/auth_check.php';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'insert') {
        $no_kk = trim($_POST['no_kk'] ?? '');
        $nik = trim($_POST['nik'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $no_rumah = trim($_POST['no_rumah'] ?? '');
        $rt_rw = trim($_POST['rt_rw'] ?? '001/011');
        $status_aktif = $_POST['status_aktif'] ?? 'aktif';

        if ($no_kk !== '' && $nik !== '' && $nama !== '' && $no_rumah !== '') {
            try {
                $stmt = $pdo->prepare("INSERT INTO warga (no_kk, nik, nama, no_rumah, rt_rw, status_aktif) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$no_kk, $nik, $nama, $no_rumah, $rt_rw, $status_aktif]);
                $success = 'Data warga berhasil ditambahkan!';
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Gagal menambahkan data warga: ' . $e->getMessage();
                $action = 'add';
            }
        } else {
            $error = 'Semua field wajib diisi!';
            $action = 'add';
        }
    } elseif ($postAction === 'update') {
        $id_warga = intval($_POST['id_warga'] ?? 0);
        $no_kk = trim($_POST['no_kk'] ?? '');
        $nik = trim($_POST['nik'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $no_rumah = trim($_POST['no_rumah'] ?? '');
        $rt_rw = trim($_POST['rt_rw'] ?? '001/011');
        $status_aktif = $_POST['status_aktif'] ?? 'aktif';

        if ($id_warga > 0 && $no_kk !== '' && $nik !== '' && $nama !== '' && $no_rumah !== '') {
            try {
                $stmt = $pdo->prepare("UPDATE warga SET no_kk = ?, nik = ?, nama = ?, no_rumah = ?, rt_rw = ?, status_aktif = ? WHERE id_warga = ?");
                $stmt->execute([$no_kk, $nik, $nama, $no_rumah, $rt_rw, $status_aktif, $id_warga]);
                $success = 'Data warga berhasil diperbarui!';
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui data warga: ' . $e->getMessage();
                $action = 'edit';
            }
        } else {
            $error = 'Semua field wajib diisi!';
            $action = 'edit';
        }
    }
}

if ($action === 'delete') {
    $id_warga = intval($_GET['id'] ?? 0);
    if ($id_warga > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM warga WHERE id_warga = ?");
            $stmt->execute([$id_warga]);
            $success = 'Data warga berhasil dihapus!';
        } catch (PDOException $e) {
            $error = 'Gagal menghapus data warga: ' . $e->getMessage();
        }
    }
    $action = 'list';
}

$page_title = 'Manajemen Warga - Kas RT';
include 'views/header.php';
?>

<div class="card">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($action === 'add'): ?>
        <div class="card-header">
            <h3 class="card-title">Tambah Warga Baru</h3>
            <a href="warga.php" class="btn btn-secondary">Kembali</a>
        </div>
        <form action="warga.php" method="POST">
            <input type="hidden" name="action" value="insert">
            <div class="form-row">
                <div class="form-group">
                    <label for="no_kk">Nomor KK</label>
                    <input type="text" id="no_kk" name="no_kk" class="form-control" placeholder="16 Digit Nomor KK" required>
                </div>
                <div class="form-group">
                    <label for="nik">NIK</label>
                    <input type="text" id="nik" name="nik" class="form-control" placeholder="16 Digit NIK" required>
                </div>
            </div>
            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" class="form-control" placeholder="Nama Lengkap" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="no_rumah">Nomor Rumah</label>
                    <input type="text" id="no_rumah" name="no_rumah" class="form-control" placeholder="Contoh: A-12" required>
                </div>
                <div class="form-group">
                    <label for="rt_rw">RT / RW</label>
                    <input type="text" id="rt_rw" name="rt_rw" class="form-control" value="001/011" placeholder="RT/RW" required>
                </div>
            </div>
            <div class="form-group">
                <label for="status_aktif">Status Aktif</label>
                <select id="status_aktif" name="status_aktif" class="form-control">
                    <option value="aktif">Aktif</option>
                    <option value="pindah">Pindah</option>
                    <option value="meninggal">Meninggal</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Warga</button>
            </div>
        </form>

    <?php elseif ($action === 'edit'): ?>
        <?php
        $id_warga = intval($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM warga WHERE id_warga = ?");
        $stmt->execute([$id_warga]);
        $w = $stmt->fetch();
        if (!$w) {
            echo '<div class="alert alert-danger">Warga tidak ditemukan.</div>';
            echo '<a href="warga.php" class="btn btn-secondary">Kembali</a>';
        } else {
        ?>
        <div class="card-header">
            <h3 class="card-title">Edit Data Warga</h3>
            <a href="warga.php" class="btn btn-secondary">Kembali</a>
        </div>
        <form action="warga.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id_warga" value="<?= $w['id_warga'] ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="no_kk">Nomor KK</label>
                    <input type="text" id="no_kk" name="no_kk" class="form-control" value="<?= htmlspecialchars($w['no_kk']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="nik">NIK</label>
                    <input type="text" id="nik" name="nik" class="form-control" value="<?= htmlspecialchars($w['nik']) ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" class="form-control" value="<?= htmlspecialchars($w['nama']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="no_rumah">Nomor Rumah</label>
                    <input type="text" id="no_rumah" name="no_rumah" class="form-control" value="<?= htmlspecialchars($w['no_rumah']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="rt_rw">RT / RW</label>
                    <input type="text" id="rt_rw" name="rt_rw" class="form-control" value="<?= htmlspecialchars($w['rt_rw']) ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="status_aktif">Status Aktif</label>
                <select id="status_aktif" name="status_aktif" class="form-control">
                    <option value="aktif" <?= $w['status_aktif'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="pindah" <?= $w['status_aktif'] === 'pindah' ? 'selected' : '' ?>>Pindah</option>
                    <option value="meninggal" <?= $w['status_aktif'] === 'meninggal' ? 'selected' : '' ?>>Meninggal</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
        <?php } ?>

    <?php else: ?>
        <div class="card-header">
            <h3 class="card-title">Data Warga</h3>
            <a href="warga.php?action=add" class="btn btn-primary">Tambah Warga</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. KK</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>No. Rumah</th>
                        <th>RT/RW</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM warga ORDER BY nama ASC");
                    $no = 1;
                    while ($row = $stmt->fetch()) {
                        $badgeClass = 'badge-secondary';
                        if ($row['status_aktif'] === 'aktif') $badgeClass = 'badge-success';
                        elseif ($row['status_aktif'] === 'pindah') $badgeClass = 'badge-warning';
                        elseif ($row['status_aktif'] === 'meninggal') $badgeClass = 'badge-danger';
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['no_kk']) ?></td>
                            <td><?= htmlspecialchars($row['nik']) ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['no_rumah']) ?></td>
                            <td><?= htmlspecialchars($row['rt_rw']) ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($row['status_aktif'])) ?></span></td>
                            <td class="actions-cell">
                                <a href="warga.php?action=edit&id=<?= $row['id_warga'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="warga.php?action=delete&id=<?= $row['id_warga'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data warga ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if ($no === 1): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #94a3b8;">Belum ada data warga.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'views/footer.php'; ?>
