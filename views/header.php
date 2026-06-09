<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'Manajemen Kas RT' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include 'views/sidebar.php'; ?>
        <main class="main-content">
            <header class="content-header">
                <span class="user-greeting">Halo, <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Pengurus') ?></strong> (<?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'role')) ?>)</span>
                <a href="logout.php" class="btn-logout">Keluar</a>
            </header>
            <div class="content-body">
