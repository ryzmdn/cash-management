<?php
require_once 'config/auth_check.php';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'insert') {
        $nama_iuran = trim($_POST['nama_iuran'] ?? '');
        $tipe = $_POST['tipe'] ?? 'wajib';
        $nominal_default = floatval($_POST['nominal_default'] ?? 0);

        if ($nama_iuran !== '') {
            try {
                $stmt = $pdo->prepare("INSERT INTO kategori_iuran (nama_iuran, tipe, nominal_default) VALUES (?, ?, ?)");
                $stmt->execute([$nama_iuran, $tipe, $nominal_default]);
                $success = 'Kategori iuran berhasil ditambahkan!';
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Gagal menambahkan kategori iuran: ' . $e->getMessage();
                $action = 'add';
            }
        } else {
            $error = 'Nama iuran wajib diisi!';
            $action = 'add';
        }
    } elseif ($postAction === 'update') {
        $id_kategori = intval($_POST['id_kategori'] ?? 0);
        $nama_iuran = trim($_POST['nama_iuran'] ?? '');
        $tipe = $_POST['tipe'] ?? 'wajib';
        $nominal_default = floatval($_POST['nominal_default'] ?? 0);

        if ($id_kategori > 0 && $nama_iuran !== '') {
            try {
                $stmt = $pdo->prepare("UPDATE kategori_iuran SET nama_iuran = ?, tipe = ?, nominal_default = ? WHERE id_kategori = ?");
                $stmt->execute([$nama_iuran, $tipe, $nominal_default, $id_kategori]);
                $success = 'Kategori iuran berhasil diperbarui!';
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui kategori iuran: ' . $e->getMessage();
                $action = 'edit';
            }
        } else {
            $error = 'Nama iuran wajib diisi!';
            $action = 'edit';
        }
    }
}

if ($action === 'delete') {
    $id_kategori = intval($_GET['id'] ?? 0);
    if ($id_kategori > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM kategori_iuran WHERE id_kategori = ?");
            $stmt->execute([$id_kategori]);
            $success = 'Kategori iuran berhasil dihapus!';
        } catch (PDOException $e) {
            $error = 'Gagal menghapus kategori iuran (mungkin sudah digunakan dalam data transaksi): ' . $e->getMessage();
        }
    }
    $action = 'list';
}

$page_title = 'Kategori Iuran - Kas RT';
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
            <h3 class="card-title">Tambah Kategori Iuran</h3>
            <a href="kategori.php" class="btn btn-secondary">Kembali</a>
        </div>
        <form action="kategori.php" method="POST">
            <input type="hidden" name="action" value="insert">
            <div class="form-group">
                <label for="nama_iuran">Nama Iuran</label>
                <input type="text" id="nama_iuran" name="nama_iuran" class="form-control" placeholder="Contoh: Iuran Keamanan, Dana Sosial" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="tipe">Tipe Iuran</label>
                    <select id="tipe" name="tipe" class="form-control">
                        <option value="wajib">Wajib</option>
                        <option value="sukarela">Sukarela</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nominal_default">Nominal Default (Rp)</label>
                    <input type="number" id="nominal_default" name="nominal_default" class="form-control" min="0" step="1000" placeholder="0" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Kategori</button>
            </div>
        </form>

    <?php elseif ($action === 'edit'): ?>
        <?php
        $id_kategori = intval($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM kategori_iuran WHERE id_kategori = ?");
        $stmt->execute([$id_kategori]);
        $k = $stmt->fetch();
        if (!$k) {
            echo '<div class="alert alert-danger">Kategori tidak ditemukan.</div>';
            echo '<a href="kategori.php" class="btn btn-secondary">Kembali</a>';
        } else {
        ?>
        <div class="card-header">
            <h3 class="card-title">Edit Kategori Iuran</h3>
            <a href="kategori.php" class="btn btn-secondary">Kembali</a>
        </div>
        <form action="kategori.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id_kategori" value="<?= $k['id_kategori'] ?>">
            <div class="form-group">
                <label for="nama_iuran">Nama Iuran</label>
                <input type="text" id="nama_iuran" name="nama_iuran" class="form-control" value="<?= htmlspecialchars($k['nama_iuran']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="tipe">Tipe Iuran</label>
                    <select id="tipe" name="tipe" class="form-control">
                        <option value="wajib" <?= $k['tipe'] === 'wajib' ? 'selected' : '' ?>>Wajib</option>
                        <option value="sukarela" <?= $k['tipe'] === 'sukarela' ? 'selected' : '' ?>>Sukarela</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nominal_default">Nominal Default (Rp)</label>
                    <input type="number" id="nominal_default" name="nominal_default" class="form-control" value="<?= intval($k['nominal_default']) ?>" min="0" step="1000" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
        <?php } ?>

    <?php else: ?>
        <div class="card-header">
            <h3 class="card-title">Kategori Iuran</h3>
            <a href="kategori.php?action=add" class="btn btn-primary">Tambah Kategori</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Iuran</th>
                        <th>Tipe</th>
                        <th>Nominal Default</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM kategori_iuran ORDER BY nama_iuran ASC");
                    $no = 1;
                    while ($row = $stmt->fetch()) {
                        $badgeClass = $row['tipe'] === 'wajib' ? 'badge-info' : 'badge-secondary';
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_iuran']) ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($row['tipe'])) ?></span></td>
                            <td>Rp <?= number_format($row['nominal_default'], 0, ',', '.') ?></td>
                            <td class="actions-cell">
                                <a href="kategori.php?action=edit&id=<?= $row['id_kategori'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="kategori.php?action=delete&id=<?= $row['id_kategori'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori iuran ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if ($no === 1): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94a3b8;">Belum ada kategori iuran.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'views/footer.php'; ?>
