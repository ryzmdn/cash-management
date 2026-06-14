<?php
require_once 'config/auth_check.php';

// Fetch Total Saldo from UDF
try {
    $saldoStmt = $pdo->query("SELECT HitungSaldoTotal() AS saldo_sekarang");
    $saldoResult = $saldoStmt->fetch();
    $saldoTotal = floatval($saldoResult['saldo_sekarang'] ?? 0);
} catch (PDOException $e) {
    $saldoTotal = 0;
}

// Fetch Monthly Income
try {
    $incomeStmt = $pdo->query("
        SELECT SUM(jumlah) AS total_masuk 
        FROM kas_masuk 
        WHERE MONTH(tanggal_bayar) = MONTH(CURRENT_DATE()) 
          AND YEAR(tanggal_bayar) = YEAR(CURRENT_DATE())
    ");
    $incomeResult = $incomeStmt->fetch();
    $totalIncomeThisMonth = floatval($incomeResult['total_masuk'] ?? 0);
} catch (PDOException $e) {
    $totalIncomeThisMonth = 0;
}

// Fetch Monthly Expense
try {
    $expenseStmt = $pdo->query("
        SELECT SUM(jumlah) AS total_keluar 
        FROM kas_keluar 
        WHERE MONTH(tanggal_keluar) = MONTH(CURRENT_DATE()) 
          AND YEAR(tanggal_keluar) = YEAR(CURRENT_DATE())
    ");
    $expenseResult = $expenseStmt->fetch();
    $totalExpenseThisMonth = floatval($expenseResult['total_keluar'] ?? 0);
} catch (PDOException $e) {
    $totalExpenseThisMonth = 0;
}

$page_title = 'Dashboard - Kas RT';
include 'views/header.php';
?>

<div class="dashboard-stats">
    <div class="stat-card balance">
        <div>
            <div class="stat-label">Total Saldo Kas</div>
            <div class="stat-value">Rp <?= number_format($saldoTotal, 0, ',', '.') ?></div>
        </div>
        <div class="stat-desc">Akumulasi seluruh pemasukan dikurangi pengeluaran</div>
    </div>
    <div class="stat-card income">
        <div>
            <div class="stat-label">Kas Masuk Bulan Ini</div>
            <div class="stat-value">Rp <?= number_format($totalIncomeThisMonth, 0, ',', '.') ?></div>
        </div>
        <div class="stat-desc">Pemasukan bulan <?= date('F Y') ?></div>
    </div>
    <div class="stat-card expense">
        <div>
            <div class="stat-label">Kas Keluar Bulan Ini</div>
            <div class="stat-value">Rp <?= number_format($totalExpenseThisMonth, 0, ',', '.') ?></div>
        </div>
        <div class="stat-desc">Pengeluaran bulan <?= date('F Y') ?></div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Kas Masuk Terbaru</h3>
            <a href="kas_masuk.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Warga</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $latestInStmt = $pdo->query("
                        SELECT km.tanggal_bayar, w.nama AS nama_warga, km.jumlah 
                        FROM kas_masuk km
                        LEFT JOIN warga w ON km.id_warga = w.id_warga
                        ORDER BY km.tanggal_bayar DESC, km.id_masuk DESC
                        LIMIT 5
                    ");
                    $hasIn = false;
                    while ($row = $latestInStmt->fetch()) {
                        $hasIn = true;
                    ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($row['tanggal_bayar'])) ?></td>
                            <td><?= htmlspecialchars($row['nama_warga'] ?? 'Anonim') ?></td>
                            <td style="color: #4ade80; font-weight: 600;">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                        </tr>
                    <?php } ?>
                    <?php if (!$hasIn): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #94a3b8;">Belum ada pemasukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Kas Keluar Terbaru</h3>
            <a href="kas_keluar.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keperluan</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $latestOutStmt = $pdo->query("
                        SELECT tanggal_keluar, keperluan, jumlah 
                        FROM kas_keluar
                        ORDER BY tanggal_keluar DESC, id_keluar DESC
                        LIMIT 5
                    ");
                    $hasOut = false;
                    while ($row = $latestOutStmt->fetch()) {
                        $hasOut = true;
                    ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($row['tanggal_keluar'])) ?></td>
                            <td><?= htmlspecialchars(strlen($row['keperluan']) > 25 ? substr($row['keperluan'], 0, 22) . '...' : $row['keperluan']) ?></td>
                            <td style="color: #fca5a5; font-weight: 600;">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                        </tr>
                    <?php } ?>
                    <?php if (!$hasOut): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #94a3b8;">Belum ada pengeluaran.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>
