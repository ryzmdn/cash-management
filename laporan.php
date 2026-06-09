<?php
require_once 'config/auth_check.php';

$selectedMonth = $_GET['bulan'] ?? date('m');
$selectedYear = $_GET['tahun'] ?? date('Y');

$months = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
];

// Determine date filters
$startDate = '';
$endDate = '';
$whereClause = '1=1';
$params = [];

if ($selectedMonth !== 'all' && $selectedYear !== 'all') {
    $startDate = "$selectedYear-$selectedMonth-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $whereClause = "tanggal >= :start_date AND tanggal <= :end_date";
    $params['start_date'] = $startDate;
    $params['end_date'] = $endDate;
} elseif ($selectedYear !== 'all') {
    $startDate = "$selectedYear-01-01";
    $endDate = "$selectedYear-12-31";
    
    $whereClause = "tanggal >= :start_date AND tanggal <= :end_date";
    $params['start_date'] = $startDate;
    $params['end_date'] = $endDate;
}

// 1. Calculate Saldo Awal (Balance before selected period)
$saldoAwal = 0.00;
if ($startDate !== '') {
    try {
        $masukAwalStmt = $pdo->prepare("SELECT IFNULL(SUM(jumlah), 0) AS total FROM kas_masuk WHERE tanggal_bayar < :start_date");
        $masukAwalStmt->execute(['start_date' => $startDate]);
        $totalMasukAwal = floatval($masukAwalStmt->fetch()['total']);

        $keluarAwalStmt = $pdo->prepare("SELECT IFNULL(SUM(jumlah), 0) AS total FROM kas_keluar WHERE tanggal_keluar < :start_date");
        $keluarAwalStmt->execute(['start_date' => $startDate]);
        $totalKeluarAwal = floatval($keluarAwalStmt->fetch()['total']);

        $saldoAwal = $totalMasukAwal - $totalKeluarAwal;
    } catch (PDOException $e) {
        $saldoAwal = 0.00;
    }
}

// 2. Fetch all transactions (Union of Kas Masuk and Kas Keluar) in the period
$transactions = [];
try {
    $sql = "
        SELECT * FROM (
            SELECT 
                km.tanggal_bayar AS tanggal, 
                'masuk' AS tipe, 
                CONCAT(w.nama, ' - ', ki.nama_iuran) AS keterangan, 
                km.keterangan AS detail,
                km.jumlah AS masuk, 
                0.00 AS keluar, 
                km.id_masuk AS seq_id
            FROM kas_masuk km
            INNER JOIN kategori_iuran ki ON km.id_kategori = ki.id_kategori
            LEFT JOIN warga w ON km.id_warga = w.id_warga
            
            UNION ALL
            
            SELECT 
                tanggal_keluar AS tanggal, 
                'keluar' AS tipe, 
                keperluan AS keterangan, 
                CONCAT('PJ: ', penanggung_jawab) AS detail,
                0.00 AS masuk, 
                jumlah AS keluar, 
                id_keluar AS seq_id
            FROM kas_keluar
        ) AS gabungan
        WHERE $whereClause
        ORDER BY tanggal ASC, tipe DESC, seq_id ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Gagal memuat data laporan: " . $e->getMessage();
}

// Calculate sums in current period
$totalMasukPeriod = 0;
$totalKeluarPeriod = 0;
foreach ($transactions as $t) {
    $totalMasukPeriod += floatval($t['masuk']);
    $totalKeluarPeriod += floatval($t['keluar']);
}
$saldoAkhirPeriod = $saldoAwal + $totalMasukPeriod - $totalKeluarPeriod;

$page_title = 'Laporan Keuangan - Kas RT';
include 'views/header.php';
?>

<div class="card no-print">
    <div class="card-header">
        <h3 class="card-title">Filter Laporan Keuangan</h3>
    </div>
    <form action="laporan.php" method="GET" class="report-filter-form">
        <div class="form-row">
            <div class="form-group">
                <label for="bulan">Bulan</label>
                <select id="bulan" name="bulan" class="form-control">
                    <option value="all" <?= $selectedMonth === 'all' ? 'selected' : '' ?>>Semua Bulan</option>
                    <?php foreach ($months as $mCode => $mName): ?>
                        <option value="<?= $mCode ?>" <?= $selectedMonth === $mCode ? 'selected' : '' ?>><?= $mName ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="tahun">Tahun</label>
                <select id="tahun" name="tahun" class="form-control">
                    <option value="all" <?= $selectedYear === 'all' ? 'selected' : '' ?>>Semua Tahun</option>
                    <?php
                    $yearStmt = $pdo->query("
                        SELECT DISTINCT YEAR(tanggal) AS thn FROM (
                            SELECT tanggal_bayar AS tanggal FROM kas_masuk
                            UNION
                            SELECT tanggal_keluar AS tanggal FROM kas_keluar
                        ) AS gabungan_thn ORDER BY thn DESC
                    ");
                    $hasYears = false;
                    while ($y = $yearStmt->fetch()) {
                        $hasYears = true;
                        echo '<option value="' . $y['thn'] . '" ' . ($selectedYear == $y['thn'] ? 'selected' : '') . '>' . $y['thn'] . '</option>';
                    }
                    if (!$hasYears) {
                        echo '<option value="' . date('Y') . '" selected>' . date('Y') . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="form-actions" style="margin-top: 10px;">
            <button type="submit" class="btn btn-secondary">Tampilkan Laporan</button>
            <button type="button" class="btn btn-primary" onclick="window.print()">Cetak Laporan</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header" style="flex-direction: column; align-items: center; text-align: center; gap: 8px;">
        <h2 style="color: #38bdf8; font-size: 1.6rem; font-weight: 700;">LAPORAN REKAPITULASI KAS RT/RW</h2>
        <h4 style="color: #94a3b8; font-weight: 500; font-size: 1rem;">
            Periode: 
            <?php 
            if ($selectedMonth === 'all' && $selectedYear === 'all') {
                echo 'Seluruh Periode';
            } elseif ($selectedMonth === 'all') {
                echo 'Tahun ' . htmlspecialchars($selectedYear);
            } else {
                echo htmlspecialchars($months[$selectedMonth]) . ' ' . htmlspecialchars($selectedYear);
            }
            ?>
        </h4>
    </div>

    <!-- Summary stats for selected period -->
    <div class="dashboard-stats" style="margin-top: 20px;">
        <div class="stat-card" style="background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255, 255, 255, 0.05);">
            <div>
                <div class="stat-label" style="color: #cbd5e1;">Saldo Awal Periode</div>
                <div class="stat-value" style="font-size: 1.5rem; color: #fff;">Rp <?= number_format($saldoAwal, 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="stat-card" style="background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255, 255, 255, 0.05);">
            <div>
                <div class="stat-label" style="color: #4ade80;">Total Pemasukan</div>
                <div class="stat-value" style="font-size: 1.5rem; color: #4ade80;">+ Rp <?= number_format($totalMasukPeriod, 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="stat-card" style="background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255, 255, 255, 0.05);">
            <div>
                <div class="stat-label" style="color: #fca5a5;">Total Pengeluaran</div>
                <div class="stat-value" style="font-size: 1.5rem; color: #fca5a5;">- Rp <?= number_format($totalKeluarPeriod, 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="stat-card" style="background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2);">
            <div>
                <div class="stat-label" style="color: #38bdf8;">Saldo Akhir Periode</div>
                <div class="stat-value" style="font-size: 1.5rem; color: #38bdf8;">Rp <?= number_format($saldoAkhirPeriod, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="table-responsive" style="margin-top: 20px;">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 120px;">Tanggal</th>
                    <th>Uraian / Keterangan</th>
                    <th style="text-align: right; width: 140px;">Pemasukan (Debet)</th>
                    <th style="text-align: right; width: 140px;">Pengeluaran (Kredit)</th>
                    <th style="text-align: right; width: 160px;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <!-- Row Saldo Awal -->
                <tr style="background: rgba(255, 255, 255, 0.01);">
                    <td style="font-style: italic; color: #94a3b8;"><?= $startDate !== '' ? date('d-m-Y', strtotime($startDate)) : '-' ?></td>
                    <td style="font-weight: 500; font-style: italic; color: #cbd5e1;">Saldo Awal Buku</td>
                    <td style="text-align: right; color: #94a3b8;">-</td>
                    <td style="text-align: right; color: #94a3b8;">-</td>
                    <td style="text-align: right; font-weight: 600; color: #cbd5e1;">Rp <?= number_format($saldoAwal, 0, ',', '.') ?></td>
                </tr>

                <?php
                $runningSaldo = $saldoAwal;
                foreach ($transactions as $t) {
                    $masukVal = floatval($t['masuk']);
                    $keluarVal = floatval($t['keluar']);
                    $runningSaldo = $runningSaldo + $masukVal - $keluarVal;
                ?>
                    <tr>
                        <td><?= date('d-m-Y', strtotime($t['tanggal'])) ?></td>
                        <td>
                            <div style="font-weight: 500; color: #f8fafc;"><?= htmlspecialchars($t['keterangan']) ?></div>
                            <?php if ($t['detail'] !== ''): ?>
                                <small style="color: #94a3b8; font-size: 0.8rem; display: block; margin-top: 2px;"><?= htmlspecialchars($t['detail']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; color: #4ade80; font-weight: 500;">
                            <?= $masukVal > 0 ? 'Rp ' . number_format($masukVal, 0, ',', '.') : '-' ?>
                        </td>
                        <td style="text-align: right; color: #fca5a5; font-weight: 500;">
                            <?= $keluarVal > 0 ? 'Rp ' . number_format($keluarVal, 0, ',', '.') : '-' ?>
                        </td>
                        <td style="text-align: right; font-weight: 600; color: #38bdf8;">
                            Rp <?= number_format($runningSaldo, 0, ',', '.') ?>
                        </td>
                    </tr>
                <?php } ?>

                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">Tidak ada catatan transaksi pada periode ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'views/footer.php'; ?>
