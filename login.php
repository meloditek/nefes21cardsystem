<?php
require_once 'db.php'; // PDO bağlantısı ve session_start() burada olacak

// Eğer admin zaten giriş yaptıysa admin paneline yönlendir
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Kullanıcıyı veritabanında ara ve admin olup olmadığını kontrol et
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username=? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password']) && $user['role'] === 'admin') {
        // Başarılı admin girişi
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header("Location: admin.php");
        exit;
    } else {
        $error = "Kullanıcı adı veya şifre yanlış, ya da admin yetkiniz yok!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi</title>
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background: #1e272e;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-box {
            background: #2c3e50;
            padding: 50px 35px;
            border-radius: 12px;
            box-shadow: 0 12px 25px rgba(0,0,0,0.5);
            width: 360px;
            text-align: center;
        }
        .login-box h2 {
            margin-bottom: 35px;
            color: #fff;
            font-size: 28px;
            letter-spacing: 1px;
        }
        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: 100%;
            padding: 14px 18px;
            margin: 12px 0;
            border-radius: 10px;
            border: none;
            background: #34495e;
            color: #fff;
            font-size: 15px;
        }
        .login-box input::placeholder {
            color: #ccc;
        }
        .login-box button {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: none;
            background: #1abc9c;
            color: #fff;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.3s;
        }
        .login-box button:hover {
            background: #16a085;
        }
        .error {
            color: #e74c3c;
            margin-top: 15px;
            font-size: 14px;
        }
        @media (max-width: 400px) {
            .login-box { width: 90%; padding: 40px 20px; }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Girişi</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Kullanıcı Adı" required>
            <input type="password" name="password" placeholder="Şifre" required>
            <button type="submit" name="login">Giriş</button>
        </form>
        <?php if ($error): ?>
                                    <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
