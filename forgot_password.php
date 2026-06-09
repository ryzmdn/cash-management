<?php
require_once 'config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$simulated_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email !== '') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id_user = ?");
            $update->execute([$token, $expires, $user['id_user']]);

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $dir = dirname($_SERVER['PHP_SELF']);
            $resetLink = "$protocol://$host$dir/reset_password.php?token=$token";

            $mailContent = "Kepada: " . htmlspecialchars($email) . "\n";
            $mailContent .= "Subjek: Reset Password Akun Kas RT/RW\n\n";
            $mailContent .= "Halo " . htmlspecialchars($user['nama']) . ",\n";
            $mailContent .= "Kami menerima permintaan reset password untuk akun Anda. Silakan klik tautan di bawah ini untuk mereset password Anda:\n";
            $mailContent .= $resetLink . "\n\n";
            $mailContent .= "Tautan ini akan kedaluwarsa dalam 1 jam.\n";
            $mailContent .= "Jika Anda tidak meminta reset password, abaikan email ini.\n";

            file_put_contents(__DIR__ . '/assets/mail_log.txt', $mailContent);

            $success = 'Simulasi email berhasil dikirim! Silakan periksa kotak masuk di bawah.';
            $simulated_email = $resetLink;
        } else {
            $error = 'Email tidak ditemukan!';
        }
    } else {
        $error = 'Masukkan email Anda!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Manajemen Kas RT</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-container" style="max-width: 520px;">
        <div class="login-card">
            <div class="login-header">
                <h2>Lupa Password</h2>
                <p>Masukkan email terdaftar untuk menerima tautan reset password</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="login-form">
                <div class="form-group">
                    <label for="email">Email Terdaftar</label>
                    <input type="email" id="email" name="email" placeholder="Masukkan email Anda" required autocomplete="off">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Kirim Tautan Reset</button>
            </form>

            <?php if ($simulated_email): ?>
                <div style="margin-top: 30px; padding: 20px; background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); border-radius: 12px;">
                    <h4 style="color: #38bdf8; font-weight: 600; margin-bottom: 10px; font-size: 0.95rem;">[Simulasi Kotak Masuk Email]</h4>
                    <p style="color: #cbd5e1; font-size: 0.85rem; margin-bottom: 12px; line-height: 1.5;">
                        Pesan reset password telah dikirim ke email <strong><?= htmlspecialchars($_POST['email']) ?></strong>. 
                        Silakan klik tombol di bawah untuk membuka halaman reset password:
                    </p>
                    <a href="<?= $simulated_email ?>" class="btn btn-primary btn-sm" style="display: inline-block; width: auto; font-size: 0.85rem;">Reset Password Saya</a>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 24px;">
                <a href="login.php" style="color: #94a3b8; font-size: 0.9rem; text-decoration: none; font-weight: 500;">Kembali ke Halaman Login</a>
            </div>
        </div>
    </div>
</body>
</html>
