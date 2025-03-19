<?php
require_once 'config.php';
requireAdmin();

$success = '';
$error = '';

// Handle user creation
if (isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$username, $hashedPassword]);
            $success = 'Kullanıcı başarıyla oluşturuldu';
        } catch (PDOException $e) {
            $error = 'Kullanıcı oluşturulamadı. Bu kullanıcı adı zaten kullanılıyor olabilir.';
        }
    }
}

// Handle FTP settings update
if (isset($_POST['action']) && $_POST['action'] === 'update_ftp') {
    $host = $_POST['host'] ?? '';
    $port = $_POST['port'] ?? 21;
    $ftp_username = $_POST['ftp_username'] ?? '';
    $ftp_password = $_POST['ftp_password'] ?? '';
    $secure = isset($_POST['secure']) ? 1 : 0;

    try {
        $pdo = getDbConnection();
        // Check if settings exist
        $stmt = $pdo->query("SELECT COUNT(*) FROM ftp_settings");
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE ftp_settings SET host = ?, port = ?, ftp_username = ?, ftp_password = ?, secure = ?");
        } else {
            $stmt = $pdo->prepare("INSERT INTO ftp_settings (host, port, ftp_username, ftp_password, secure) VALUES (?, ?, ?, ?, ?)");
        }
        
        $stmt->execute([$host, $port, $ftp_username, $ftp_password, $secure]);
        $success = 'FTP ayarları başarıyla güncellendi';
    } catch (PDOException $e) {
        $error = 'FTP ayarları güncellenirken bir hata oluştu';
    }
}

// Get current FTP settings
try {
    $ftpSettings = getFtpConfig();
} catch (PDOException $e) {
    $ftpSettings = false;
}

// Get list of users
try {
    $pdo = getDbConnection();
    $users = $pdo->query("SELECT id, username, role FROM users ORDER BY id")->fetchAll();
} catch (PDOException $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - FTP Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        accent: '#f97316',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-primary min-h-screen">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-semibold text-primary">FTP Yönetici Paneli</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-accent">
                        <i class="fas fa-home mr-1"></i> Ana Sayfa
                    </a>
                    <a href="logout.php" class="text-gray-600 hover:text-accent">
                        <i class="fas fa-sign-out-alt mr-1"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- User Management Section -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-semibold mb-6">Kullanıcı Yönetimi</h2>
                
                <form method="POST" class="mb-8">
                    <input type="hidden" name="action" value="create_user">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kullanıcı Adı</label>
                            <input type="text" name="username" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Şifre</label>
                            <input type="password" name="password" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Şifreyi Onayla</label>
                            <input type="password" name="confirm_password" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50">
                        </div>
                        <button type="submit"
                            class="w-full bg-accent hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            Kullanıcı Oluştur
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kullanıcı Adı</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($user['id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FTP Settings Section -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-semibold mb-6">FTP Ayarları</h2>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_ftp">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sunucu Adresi</label>
                            <input type="text" name="host" required
                                value="<?php echo htmlspecialchars($ftpSettings['host'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Port Numarası</label>
                            <input type="number" name="port" required
                                value="<?php echo htmlspecialchars($ftpSettings['port'] ?? '21'); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">FTP Kullanıcı Adı</label>
                            <input type="text" name="ftp_username" required
                                value="<?php echo htmlspecialchars($ftpSettings['ftp_username'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">FTP Şifresi</label>
                            <input type="password" name="ftp_password" required
                                value="<?php echo htmlspecialchars($ftpSettings['ftp_password'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50">
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="secure" id="secure"
                                <?php echo ($ftpSettings['secure'] ?? false) ? 'checked' : ''; ?>
                                class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded">
                            <label for="secure" class="ml-2 block text-sm text-gray-700">
                                Güvenli Bağlantı Kullan (FTPS)
                            </label>
                        </div>
                        <button type="submit"
                            class="w-full bg-accent hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            FTP Ayarlarını Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>