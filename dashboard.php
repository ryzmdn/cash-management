<?php
require_once 'config/auth_check.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Manajemen Kas RT</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div style="padding: 40px; max-width: 800px; margin: 0 auto; text-align: center;">
        <h1 style="color: #38bdf8; margin-bottom: 20px;">Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h1>
        <p style="color: #94a3b8; margin-bottom: 30px;">Anda masuk sebagai pengurus dengan peran: <strong><?php echo htmlspecialchars(ucfirst($_SESSION['role'])); ?></strong></p>
        <a href="logout.php" class="btn btn-primary">Keluar dari Sistem</a>
    </div>
</body>
</html>
