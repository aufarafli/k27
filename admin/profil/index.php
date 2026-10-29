<?php
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Proses Update Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    
    // Logika upload foto
    $foto_profil = $_SESSION['admin_photo']; 
    
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../../uploads/admin/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_name = "admin_" . $admin_id . "_" . time() . "." . pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_file)) {
            $foto_profil = "uploads/admin/" . $file_name;
        }
    }

    try {
        $stmt = $conn->prepare("UPDATE admin SET username = :username, nama_lengkap = :nama_lengkap, email = :email, foto_profil = :foto_profil WHERE id = :id");
        $stmt->execute([
            'username' => $username,
            'nama_lengkap' => $nama_lengkap,
            'email' => $email,
            'foto_profil' => $foto_profil,
            'id' => $admin_id
        ]);
        
        $_SESSION['admin_name'] = $nama_lengkap;
        $_SESSION['admin_photo'] = $foto_profil;
        
        $success = "Profil berhasil diperbarui!";
    } catch (Exception $e) {
        $error = "Gagal memperbarui profil: " . $e->getMessage();
    }
}

// Proses Update Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error_pass = "Konfirmasi password tidak cocok!";
    } elseif (strlen($new_password) < 6) {
        $error_pass = "Password minimal 6 karakter!";
    } else {
        $stmt = $conn->prepare("SELECT password FROM admin WHERE id = :id");
        $stmt->execute(['id' => $admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && $current_password === $admin['password']) {
            $stmt_update = $conn->prepare("UPDATE admin SET password = :password WHERE id = :id");
            $stmt_update->execute(['password' => $new_password, 'id' => $admin_id]);
            $success_pass = "Password berhasil diubah!";
        } else {
            $error_pass = "Password saat ini salah!";
        }
    }
}

// Ambil data admin untuk ditampilkan
$stmt_admin = $conn->prepare("SELECT * FROM admin WHERE id = :id");
$stmt_admin->execute(['id' => $admin_id]);
$admin_data = $stmt_admin->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin - Keamanan Informasi K2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Custom Colors */
        .bg-navy { background-color: #003087; }
        .text-navy { color: #003087; }
        .border-navy { border-color: #003087; }
        .bg-gold { background-color: #F2A900; }
        .text-gold { color: #F2A900; }
        
        body { background-color: #F8FAFC; color: #1E293B; }
        
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>

<body class="min-h-screen bg-fixed">
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar (Tema Navy) -->
    <aside id="sidebar" class="fixed top-0 left-0 h-screen w-72 bg-navy text-white z-50 sidebar-transition -translate-x-full lg:translate-x-0 shadow-xl">
        <div class="flex items-center justify-between p-4 lg:p-6 border-b border-blue-800">
            <div class="flex items-center gap-3">
                <img src="../../assets/default/logo-smk40.png" alt="Logo" class="h-8 lg:h-10 w-auto bg-white rounded-full p-1">
                <div>
                    <h1 class="font-bold text-sm lg:text-base whitespace-nowrap">Keamanan Informasi K2</h1>
                    <p class="text-xs text-blue-200">Sistem Absensi</p>
                </div>
            </div>
            <button class="text-blue-200 hover:text-white lg:hidden" onclick="toggleSidebar()">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <nav class="p-4 space-y-2 overflow-y-auto no-scrollbar" style="max-height: calc(100vh - 76px);">
            <a href="../dashboard/" class="flex items-center gap-3 text-blue-100 p-3 rounded-lg hover:bg-blue-800 transition-colors">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="../siswa/" class="flex items-center gap-3 text-blue-100 p-3 rounded-lg hover:bg-blue-800 transition-colors">
                <i class="fas fa-users"></i>
                <span>Data Mahasiswa</span>
            </a>
            <!-- Menu Aktif (Warna Emas) -->
            <a href="index.php" class="flex items-center gap-3 bg-gold text-white p-3 rounded-lg font-medium shadow-md transition-colors">
                <i class="fas fa-user-cog"></i>
                <span>Profil</span>
            </a>
            <a href="../logout.php" class="flex items-center gap-3 text-red-300 p-3 rounded-lg hover:bg-red-500/20 hover:text-red-100 transition-colors mt-8">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="lg:ml-72 min-h-screen">
        <div class="p-4 md:p-8">
            <div class="max-w-6xl mx-auto">
                <header class="mb-8 border-b border-gray-300 pb-4">
                    <h1 class="text-2xl md:text-3xl font-bold text-navy">Profil Administrator</h1>
                    <p class="text-gray-500 mt-1">Kelola informasi akun dan keamanan Anda</p>
                </header>

                <!-- Notifikasi -->
                <?php if (isset($success)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center">
                        <i class="fas fa-check-circle mr-3 text-lg"></i> <?= $success ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm flex items-center">
                        <i class="fas fa-exclamation-circle mr-3 text-lg"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Kartu Profil Kiri -->
                    <div class="bg-white rounded-xl shadow-sm p-6 text-center h-fit border border-gray-200">
                        <div class="relative w-32 h-32 mx-auto mb-4">
                            <img src="../../<?= $admin_data['foto_profil'] ?: 'assets/default/photo-profile.png' ?>" 
                                 alt="Profile" class="w-full h-full rounded-full object-cover border-4 border-blue-100 shadow-md">
                        </div>
                        <h2 class="text-xl font-bold text-navy"><?= htmlspecialchars($admin_data['nama_lengkap']) ?></h2>
                        <p class="text-xs font-semibold text-white bg-navy mt-2 inline-block px-4 py-1 rounded-full shadow-sm">Administrator</p>
                        
                        <div class="mt-6 text-left space-y-3">
                            <div class="flex items-center gap-3 text-gray-600 text-sm bg-gray-50 p-2 rounded-lg border border-gray-100">
                                <i class="fas fa-user text-navy w-5 text-center"></i>
                                <span><?= htmlspecialchars($admin_data['username']) ?></span>
                            </div>
                            <div class="flex items-center gap-3 text-gray-600 text-sm bg-gray-50 p-2 rounded-lg border border-gray-100">
                                <i class="fas fa-envelope text-navy w-5 text-center"></i>
                                <span class="truncate"><?= htmlspecialchars($admin_data['email']) ?></span>
                            </div>
                            <div class="flex items-center gap-3 text-gray-600 text-sm bg-gray-50 p-2 rounded-lg border border-gray-100 mt-4">
                                <i class="fas fa-clock text-navy w-5 text-center"></i>
                                <span>Login hari ini</span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Edit Profil Kanan -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Edit Profil -->
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                            <h3 class="text-lg font-bold text-navy mb-4 border-b border-gray-200 pb-3 flex items-center gap-2">
                                <i class="fas fa-user-edit text-gold"></i> Edit Profil
                            </h3>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="update_profile" value="1">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="text-sm font-semibold text-gray-600 block mb-1">Username</label>
                                        <input type="text" name="username" value="<?= htmlspecialchars($admin_data['username']) ?>" required
                                            class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-gray-800 focus:outline-none focus:border-navy focus:ring-1 focus:ring-navy">
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-gray-600 block mb-1">Nama Lengkap</label>
                                        <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($admin_data['nama_lengkap']) ?>" required
                                            class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-gray-800 focus:outline-none focus:border-navy focus:ring-1 focus:ring-navy">
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="text-sm font-semibold text-gray-600 block mb-1">Email</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($admin_data['email']) ?>" required
                                        class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-gray-800 focus:outline-none focus:border-navy focus:ring-1 focus:ring-navy">
                                </div>
                                <div class="mb-4">
                                    <label class="text-sm font-semibold text-gray-600 block mb-1">Foto Profil (Opsional)</label>
                                    <input type="file" name="foto_profil" accept="image/*"
                                        class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-gray-800 text-sm focus:outline-none file:mr-4 file:py-1.5 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-navy file:text-white hover:file:bg-blue-900">
                                </div>
                                <div class="flex justify-end mt-4">
                                    <button type="submit" class="px-6 py-2 bg-navy hover:bg-blue-900 text-white rounded-lg font-medium transition-colors shadow-md">
                                        <i class="fas fa-save mr-2"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Ubah Password -->
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                            <h3 class="text-lg font-bold text-navy mb-4 border-b border-gray-200 pb-3 flex items-center gap-2">
                                <i class="fas fa-lock text-gold"></i> Ubah Password
                            </h3>
                            
                            <?php if (isset($success_pass)): ?>
                                <div class="bg-green-100 text-green-700 text-sm p-3 rounded mb-4 border-l-4 border-green-500"><?= $success_pass ?></div>
                            <?php endif; ?>
                            <?php if (isset($error_pass)): ?>
                                <div class="bg-red-100 text-red-700 text-sm p-3 rounded mb-4 border-l-4 border-red-500"><?= $error_pass ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <input type="hidden" name="update_password" value="1">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="text-sm font-semibold text-gray-600 block mb-1">Password Saat Ini</label>
                                        <input type="password" name="current_password" required
                                            class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-gray-800 focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold">
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-gray-600 block mb-1">Password Baru</label>
                                        <input type="password" name="new_password" required
                                            class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-gray-800 focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold">
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-gray-600 block mb-1">Konfirmasi Password</label>
                                        <input type="password" name="confirm_password" required
                                            class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-gray-800 focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold">
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Minimal 6 karakter.</p>
                                <div class="flex justify-end mt-4">
                                    <button type="submit" class="px-6 py-2 bg-gold hover:bg-yellow-600 rounded-lg font-bold transition-colors text-white shadow-md">
                                        <i class="fas fa-key mr-2"></i> Ubah Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>
</body>
</html>