<?php
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Ambil kelas default (karena baru 1 kelas, kita ambil dari data siswa pertama)
$stmt_kelas = $conn->query("SELECT DISTINCT kelas FROM siswa LIMIT 1");
$kelas_default = $stmt_kelas->fetchColumn() ?? 'Kelas Default';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pertemuan - Keamanan Informasi 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .glass-effect { background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(147, 51, 234, 0.3); }
        .menu-active { background: linear-gradient(to right, rgba(147, 51, 234, 0.2), rgba(147, 51, 234, 0.05)); border-left: 4px solid #9333ea; }
        body { background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 100%); }
        .pertemuan-card { transition: all 0.2s ease; }
        .pertemuan-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(147, 51, 234, 0.4); }
    </style>
</head>

<body class="min-h-screen text-white bg-fixed flex">
    <!-- Sidebar -->
    <aside class="fixed top-0 left-0 h-screen w-64 glass-effect border-r border-purple-900/30 z-50 hidden lg:block">
        <div class="flex items-center gap-3 p-6 border-b border-purple-900/30">
            <img src="../../assets/default/logo-smk40.png" alt="Logo" class="h-10 w-auto">
            <div>
                <h1 class="font-semibold text-base">IPB University</h1>
                <p class="text-xs text-gray-400">Keamanan Informasi 2</p>
            </div>
        </div>
        <nav class="p-4 space-y-2">
            <a href="../dashboard/" class="flex items-center gap-3 text-gray-400 p-3 rounded-lg hover:bg-purple-500/10"><i class="fas fa-home"></i> Dashboard</a>
            <a href="index.php" class="flex items-center gap-3 text-white/90 p-3 rounded-lg menu-active"><i class="fas fa-calendar-check text-purple-500"></i> Absensi</a>
            <a href="../siswa/" class="flex items-center gap-3 text-gray-400 p-3 rounded-lg hover:bg-purple-500/10"><i class="fas fa-users"></i> Data Mahasiswa</a>
            <a href="../laporan/" class="flex items-center gap-3 text-gray-400 p-3 rounded-lg hover:bg-purple-500/10"><i class="fas fa-file-alt"></i> Laporan</a>
            <a href="../logout.php" class="flex items-center gap-3 text-gray-400 p-3 rounded-lg hover:bg-red-500/10 hover:text-red-500 mt-10"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen w-full p-4 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <header class="mb-8">
                <h1 class="text-2xl font-bold">Daftar Pertemuan</h1>
                <p class="text-gray-400">Pilih pertemuan untuk mengisi daftar kehadiran kelas <strong><?= htmlspecialchars($kelas_default) ?></strong></p>
            </header>

            <!-- Grid Pertemuan 1-14 -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <?php for($i = 1; $i <= 14; $i++): ?>
                    <a href="pertemuan.php?p=<?= $i ?>" class="pertemuan-card glass-effect rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer">
                        <div class="w-12 h-12 bg-purple-600/20 rounded-full flex items-center justify-center mb-3">
                            <span class="text-xl font-bold text-purple-400"><?= $i ?></span>
                        </div>
                        <h3 class="font-semibold">Pertemuan <?= $i ?></h3>
                        <p class="text-xs text-gray-400 mt-1">Klik untuk isi</p>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </main>
</body>
</html>