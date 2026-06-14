<?php
require_once 'config/auth_check.php';

$error = '';
$success = '';

$action = $_GET['action'] ?? '';

if ($action === 'export') {
    try {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="backup_kas_' . date('Y-m-d_H-i-s') . '.sql"');
        
        $tables = ['users', 'warga', 'kategori_iuran', 'kas_masuk', 'kas_keluar'];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch();
            echo "DROP TABLE IF EXISTS `$table`;\n";
            echo $row['Create Table'] . ";\n\n";
            
            $stmt = $pdo->query("SELECT * FROM `$table`");
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $keys = array_map(function($k) { return "`$k`"; }, array_keys($data));
                $values = array_map(function($v) use ($pdo) {
                    if ($v === null) return 'NULL';
                    return $pdo->quote($v);
                }, array_values($data));
                
                echo "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            echo "\n\n";
        }
        exit;
    } catch (Exception $e) {
        $error = "Gagal melakukan backup: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
    if ($_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $filePath = $_FILES['backup_file']['tmp_name'];
        $sql = file_get_contents($filePath);
        
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            
            $queries = [];
            $current = '';
            $inQuote = false;
            $quoteChar = '';
            $len = strlen($sql);
            for ($i = 0; $i < $len; $i++) {
                $char = $sql[$i];
                $prev = $i > 0 ? $sql[$i - 1] : '';
                
                if (($char === "'" || $char === '"') && $prev !== '\\') {
                    if (!$inQuote) {
                        $inQuote = true;
                        $quoteChar = $char;
                    } elseif ($char === $quoteChar) {
                        $inQuote = false;
                    }
                }
                
                $current .= $char;
                if ($char === ';' && !$inQuote) {
                    $queries[] = $current;
                    $current = '';
                }
            }
            if (trim($current) !== '') {
                $queries[] = $current;
            }
            
            $pdo->beginTransaction();
            foreach ($queries as $query) {
                $query = trim($query);
                if ($query !== '') {
                    $pdo->exec($query);
                }
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            $pdo->commit();
            $success = "Seluruh data berhasil dipulihkan dari file backup!";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;"); } catch (Exception $ex) {}
            $error = "Gagal memulihkan database: " . $e->getMessage();
        }
    } else {
        $error = "Terjadi kesalahan saat mengunggah file backup.";
    }
}

$table_stats = [];
try {
    $tables = [
        'users' => 'Pengguna (Sistem)',
        'warga' => 'Data Warga',
        'kategori_iuran' => 'Kategori Iuran',
        'kas_masuk' => 'Transaksi Kas Masuk',
        'kas_keluar' => 'Transaksi Kas Keluar'
    ];
    foreach ($tables as $tName => $tLabel) {
        $countStmt = $pdo->query("SELECT COUNT(*) AS total FROM `$tName`");
        $tCount = $countStmt->fetch()['total'];
        $table_stats[] = [
            'name' => $tLabel,
            'count' => $tCount
        ];
    }
} catch (PDOException $e) {
}

$page_title = 'Backup & Recovery - Kas RT';
include 'views/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Backup & Recovery Data</h3>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
        <?php
        $backup_info_cards = [
            [
                'title' => 'Ekspor Semua Data (Backup)',
                'description' => 'Gunakan fitur ini untuk mencadangkan semua data penting Anda. Sistem akan mengunduh file SQL yang berisi semua data transaksi, warga, kategori, dan user.',
                'action_type' => 'link',
                'action_label' => 'Unduh Backup Data (SQL)',
                'action_url' => 'backup.php?action=export',
                'btn_class' => 'btn-primary',
                'icon' => '💾'
            ],
            [
                'title' => 'Pulihkan Data (Recovery)',
                'description' => 'Unggah file backup SQL Anda di bawah ini untuk memulihkan seluruh data database yang terhapus atau bermasalah.',
                'action_type' => 'form',
                'btn_class' => 'btn-warning',
                'icon' => '⚡'
            ]
        ];

        foreach ($backup_info_cards as $card):
        ?>
            <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; border: 1px solid rgba(255, 255, 255, 0.08); background: rgba(30, 41, 59, 0.4);">
                <div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <span style="font-size: 2rem;"><?= $card['icon'] ?></span>
                        <h4 style="font-size: 1.15rem; font-weight: 600; color: #fff; margin: 0;"><?= htmlspecialchars($card['title']) ?></h4>
                    </div>
                    <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5; margin-bottom: 24px;"><?= htmlspecialchars($card['description']) ?></p>
                </div>
                <div>
                    <?php if ($card['action_type'] === 'link'): ?>
                        <a href="<?= $card['action_url'] ?>" class="btn <?= $card['btn_class'] ?> btn-block" style="display: block; text-align: center;">
                            <?= htmlspecialchars($card['action_label']) ?>
                        </a>
                    <?php else: ?>
                        <form action="backup.php" method="POST" enctype="multipart/form-data" style="margin: 0;" onsubmit="return confirm('PERINGATAN: Pemulihan data akan menimpa seluruh data saat ini! Apakah Anda yakin?')">
                            <div class="form-group" style="margin-bottom: 12px;">
                                <input type="file" name="backup_file" accept=".sql" class="form-control" required style="padding: 8px;">
                            </div>
                            <button type="submit" class="btn <?= $card['btn_class'] ?> btn-block" style="width: 100%;">Mulai Pemulihan Data</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Status Data Saat Ini</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Tabel/Kategori</th>
                    <th>Jumlah Data Terdaftar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($table_stats as $stat): ?>
                    <tr>
                        <td style="font-weight: 500; color: #fff;"><?= htmlspecialchars($stat['name']) ?></td>
                        <td>
                            <span class="badge <?= $stat['count'] > 0 ? 'badge-success' : 'badge-secondary' ?>" style="font-size: 0.85rem; padding: 6px 12px;">
                                <?= intval($stat['count']) ?> Baris Data
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'views/footer.php'; ?>
