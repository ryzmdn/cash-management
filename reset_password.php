<?php
require_once 'config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$validToken = false;
$user = null;

if ($token !== '') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $validToken = true;
    } else {
        $error = 'Tautan reset password tidak valid atau telah kedaluwarsa!';
    }
} else {
    $error = 'Token tidak ditemukan!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken && $user) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== '' && $confirm_password !== '') {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id_user = ?");
            $update->execute([$hashed_password, $user['id_user']]);

            $_SESSION['reset_success'] = 'Password berhasil diperbarui! Silakan masuk dengan password baru Anda.';
            header('Location: login.php');
            exit;
        } else {
            $error = 'Konfirmasi password tidak cocok!';
        }
    } else {
        $error = 'Harap isi kedua kolom password!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Manajemen Kas RT</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>Reset Password</h2>
                <p>Masukkan password baru Anda di bawah ini</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <form action="" method="POST" class="login-form">
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Masukkan password baru" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password baru" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Ubah Password</button>
                </form>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 24px;">
                <a href="login.php" style="color: #94a3b8; font-size: 0.9rem; text-decoration: none; font-weight: 500;">Kembali ke Halaman Login</a>
            </div>
        </div>
    </div>
</body>
</html>
