<aside class="sidebar">
    <div class="sidebar-brand">
        <h3>Kas RT/RW</h3>
    </div>
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <span>Dashboard</span>
        </a>
        <a href="warga.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'warga.php' ? 'active' : '' ?>">
            <span>Data Warga</span>
        </a>
        <a href="kategori.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'kategori.php' ? 'active' : '' ?>">
            <span>Kategori Iuran</span>
        </a>
        <a href="kas_masuk.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'kas_masuk.php' ? 'active' : '' ?>">
            <span>Kas Masuk</span>
        </a>
        <a href="kas_keluar.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'kas_keluar.php' ? 'active' : '' ?>">
            <span>Kas Keluar</span>
        </a>
        <a href="laporan.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : '' ?>">
            <span>Laporan Keuangan</span>
        </a>
        <a href="backup.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : '' ?>">
            <span>Backup & Recovery</span>
        </a>
    </nav>
</aside>
