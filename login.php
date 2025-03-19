<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: " . ($user['role'] === 'admin' ? 'admin_panel.php' : 'index.php'));
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred';
        }
    } else {
        $error = 'Please fill in all fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FTP Manager - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af', // blue-800
                        accent: '#f97316', // orange-500
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-primary min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
        <h1 class="text-3xl font-semibold text-center mb-8 text-primary">FTP Yöneticisi</h1>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php 
                $errorMessages = [
                    'Invalid username or password' => 'Geçersiz kullanıcı adı veya şifre',
                    'Please fill in all fields' => 'Lütfen tüm alanları doldurun',
                    'Database error occurred' => 'Veritabanı hatası oluştu'
                ];
                echo htmlspecialchars($errorMessages[$error] ?? $error); 
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" required
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-accent focus:border-accent"
                    placeholder="Kullanıcı adınızı girin">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Şifre</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-accent focus:border-accent"
                    placeholder="Şifrenizi girin">
            </div>

            <button type="submit" 
                class="w-full bg-accent hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                Giriş Yap
            </button>
        </form>
    </div>
</body>
</html>