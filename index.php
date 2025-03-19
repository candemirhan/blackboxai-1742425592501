<?php
require_once 'config.php';
require_once 'ftpService.php';
requireLogin();

$error = '';
$success = '';
$files = [];

try {
    $ftp = new FtpService();
    $currentPath = isset($_GET['path']) ? $_GET['path'] : '/';
    $files = $ftp->listFiles($currentPath);
    
    if ($files === false) {
        $error = $ftp->getError();
    }
} catch (Exception $e) {
    $error = 'FTP bağlantı hatası: ' . $e->getMessage();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    try {
        $uploadFile = $_FILES['file']['tmp_name'];
        $targetPath = $currentPath . '/' . $_FILES['file']['name'];
        
        if ($ftp->uploadFile($uploadFile, $targetPath)) {
            $success = 'Dosya başarıyla yüklendi';
            $files = $ftp->listFiles($currentPath); // Refresh file list
        } else {
            $error = 'Dosya yüklenirken bir hata oluştu: ' . $ftp->getError();
        }
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FTP Manager Dashboard</title>
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
                    <span class="text-xl font-semibold text-primary">FTP Yöneticisi</span>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (isAdmin()): ?>
                    <a href="admin_panel.php" class="text-gray-600 hover:text-accent">
                        <i class="fas fa-cog mr-1"></i> Yönetici Paneli
                    </a>
                    <?php endif; ?>
                    <span class="text-gray-600">
                        <i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <a href="logout.php" class="text-gray-600 hover:text-accent">
                        <i class="fas fa-sign-out-alt mr-1"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <!-- Current Path -->
            <div class="mb-6 flex items-center text-gray-600">
                <i class="fas fa-folder-open mr-2"></i>
                Mevcut Konum:
            </div>

            <!-- Upload Form -->
            <form method="POST" enctype="multipart/form-data" class="mb-6">
                <div class="flex items-center space-x-4">
                    <div class="flex-grow">
                        <input type="file" name="file" required
                            class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-accent file:text-white
                                hover:file:bg-orange-600">
                    </div>
                    <button type="submit"
                        class="bg-accent hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                        <i class="fas fa-upload mr-1"></i> Yükle
                    </button>
                </div>
            </form>

            <!-- File List -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İsim</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Boyut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Değiştirilme</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($currentPath !== '/'): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4">
                                <a href="?path=<?php echo urlencode(dirname($currentPath)); ?>"
                                   class="text-accent hover:text-orange-600">
                                    <i class="fas fa-level-up-alt mr-1"></i> Üst Dizin
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php if (is_array($files)): foreach ($files as $file): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($file['is_dir']): ?>
                                    <a href="?path=<?php echo urlencode($file['path']); ?>"
                                       class="text-primary hover:text-accent">
                                        <i class="fas fa-folder mr-1"></i>
                                        <?php echo htmlspecialchars($file['name']); ?>
                                    </a>
                                <?php else: ?>
                                    <i class="fas fa-file mr-1"></i>
                                    <?php echo htmlspecialchars($file['name']); ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $file['is_dir'] ? '-' : formatFileSize($file['size']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $file['modified']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if (!$file['is_dir']): ?>
                                    <a href="download.php?file=<?php echo urlencode($file['path']); ?>"
                                       class="text-accent hover:text-orange-600 mr-3">
                                        <i class="fas fa-download"></i> İndir
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Add file upload progress indicator
        document.querySelector('form').addEventListener('submit', function() {
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Yükleniyor...';
        });
    </script>
</body>
</html>

<?php
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return number_format($bytes, 2, ',', '.') . ' ' . $units[$pow];
}
?>