<?php
require_once '../../config/database.php';

if (!isset($_SESSION['siswa_id'])) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';

// Get student data
$sql = "SELECT * FROM siswa WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $_SESSION['siswa_id']]);
$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    header("Location: ../logout.php");
    exit();
}

// Process form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];

    try {
        $conn->beginTransaction();

        // Check if email is taken by another student
        $check_sql = "SELECT COUNT(*) FROM siswa WHERE email = :email AND id != :id";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute(['email' => $email, 'id' => $_SESSION['siswa_id']]);

        if ($check_stmt->fetchColumn() > 0) {
            throw new Exception("Email sudah digunakan oleh mahasiswa lain");
        }

        // Process photo upload if any
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($_FILES['foto']['type'], $allowed_types)) {
                throw new Exception("Format file tidak didukung. Gunakan JPG atau PNG");
            }

            $max_size = 2 * 1024 * 1024; // 2MB
            if ($_FILES['foto']['size'] > $max_size) {
                throw new Exception("Ukuran file terlalu besar. Maksimum 2MB");
            }

            $upload_dir = '../../uploads/siswa/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $filename = 'siswa_' . $_SESSION['siswa_id'] . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                $foto_path = 'uploads/siswa/' . $filename;
                $_SESSION['siswa_photo'] = $foto_path;
            } else {
                throw new Exception("Gagal mengunggah foto");
            }
        } else {
            $foto_path = $siswa['foto_profil'];
        }

        // Update student profile
        $sql = "UPDATE siswa SET 
                nama_lengkap = :nama_lengkap, 
                email = :email, 
                foto_profil = :foto_profil
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'nama_lengkap' => $nama_lengkap,
            'email' => $email,
            'foto_profil' => $foto_path,
            'id' => $_SESSION['siswa_id']
        ]);

        $_SESSION['siswa_name'] = $nama_lengkap;

        // Log activity
        $log_sql = "INSERT INTO activity_log (user_type, user_id, activity_type, description) 
                VALUES ('siswa', :siswa_id, 'update', :description)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->execute([
            'siswa_id' => $_SESSION['siswa_id'],
            'description' => "Mahasiswa mengubah profil"
        ]);

        $conn->commit();
        $success = "Profil berhasil diperbarui";

        // Refresh student data
        $sql = "SELECT * FROM siswa WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $_SESSION['siswa_id']]);
        $siswa = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Semua field password harus diisi.";
    } else if ($new_password !== $confirm_password) {
        $error = "Password baru dan konfirmasi password tidak cocok.";
    } else {
        try {
            $sql = "SELECT password FROM siswa WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['id' => $_SESSION['siswa_id']]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data && $data['password'] === $current_password) {
                $conn->beginTransaction();

                $sql = "UPDATE siswa SET password = :password WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'password' => $new_password,
                    'id' => $_SESSION['siswa_id']
                ]);

                $log_sql = "INSERT INTO activity_log (user_type, user_id, activity_type, description) 
                        VALUES ('siswa', :user_id, 'update', :description)";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->execute([
                    'user_id' => $_SESSION['siswa_id'],
                    'description' => "Mahasiswa mengubah password"
                ]);

                $conn->commit();
                $success = "Password berhasil diubah";
            } else {
                $error = "Password saat ini tidak benar.";
            }
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Mahasiswa - Keamanan Informasi K2</title>
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

    <!-- Sidebar -->
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

        <div class="p-4 border-b border-blue-800 bg-blue-900/50">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-gold flex items-center justify-center text-navy font-bold text-lg">
                    <?= substr($_SESSION['siswa_name'], 0, 1) ?>
                </div>
                <div class="overflow-hidden">
                    <h2 class="font-bold text-sm truncate"><?= htmlspecialchars($_SESSION['siswa_name']) ?></h2>
                    <p class="text-xs text-blue-200"><?= htmlspecialchars($_SESSION['siswa_nis']) ?></p>
                </div>
            </div>
        </div>

        <nav class="p-4 space-y-2 overflow-y-auto no-scrollbar" style="max-height: calc(100vh - 180px);">
            <a href="../dashboard/" class="flex items-center gap-3 text-blue-100 p-3 rounded-lg hover:bg-blue-800 transition-colors">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <!-- Menu Riwayat Absensi Dihapus -->
            <a href="index.php" class="flex items-center gap-3 bg-gold text-white p-3 rounded-lg font-medium shadow-md transition-colors">
                <i class="fas fa-user"></i>
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
            <div class="max-w-5xl mx-auto">
                <header class="mb-8 border-b border-gray-300 pb-4">
                    <h1 class="text-2xl md:text-3xl font-bold text-navy">Profil Mahasiswa</h1>
                    <p class="text-gray-500 mt-1">Kelola informasi akun dan keamanan Anda</p>
                </header>

                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-lg p-4 mb-6 text-sm flex items-center shadow-sm">
                        <i class="fas fa-check-circle mr-3 text-lg"></i>
                        <span><?= $success ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg p-4 mb-6 text-sm flex items-center shadow-sm">
                        <i class="fas fa-exclamation-circle mr-3 text-lg"></i>
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Profile Summary (Kiri) -->
                    <div class="bg-white rounded-xl shadow-sm p-6 text-center h-fit border border-gray-200">
                        <div class="relative w-32 h-32 mx-auto mb-4">
                            <?php $photo_path = !empty($siswa['foto_profil']) ? $siswa['foto_profil'] : 'assets/default/photo-profile.png'; ?>
                            <img src="../../<?= $photo_path ?>" alt="Profile" class="w-full h-full rounded-full object-cover border-4 border-blue-100 shadow-md">
                        </div>
                        <h2 class="text-xl font-bold text-navy"><?= htmlspecialchars($siswa['nama_lengkap']) ?></h2>
                        <p class="text-xs font-semibold text-white bg-navy mt-2 inline-block px-4 py-1 rounded-full shadow-sm">Mahasiswa</p>
                        
                        <div class="mt-6 text-left space-y-3">
                            <div class="flex items-center gap-3 text-gray-600 text-sm bg-gray-50 p-2 rounded-lg border border-gray-100">
                                <i class="fas fa-id-card text-navy w-5 text-center"></i>
                                <span><?= htmlspecialchars($siswa['nis']) ?></span>
                            </div>
                            <div class="flex items-center gap-3 text-gray-600 text-sm bg-gray-50 p-2 rounded-lg border border-gray-100">
                                <i class="fas fa-envelope text-navy w-5 text-center"></i>
                                <span class="truncate"><?= htmlspecialchars($siswa['email']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Profile & Password (Kanan) -->
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
                                        <label class="text-sm font-semibold text-gray-600 block mb-1">Nama Lengkap</label>
                                        <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($siswa['nama_lengkap']) ?>" required
                                            class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-gray-800 focus:outline-none focus:border-navy focus:ring-1 focus:ring-navy">
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-gray-600 block mb-1">NIM</label>
                                        <input type="text" value="<?= htmlspecialchars($siswa['nis']) ?>" disabled
                                            class="w-full bg-gray-100 border border-gray-200 rounded-lg px-4 py-2 text-gray-500 cursor-not-allowed">
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="text-sm font-semibold text-gray-600 block mb-1">Email</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($siswa['email']) ?>" required
                                        class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-gray-800 focus:outline-none focus:border-navy focus:ring-1 focus:ring-navy">
                                </div>
                                <div class="mb-4">
                                    <label class="text-sm font-semibold text-gray-600 block mb-1">Foto Profil (Opsional)</label>
                                    <input type="file" name="foto" accept="image/*"
                                        class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-gray-800 text-sm focus:outline-none file:mr-4 file:py-1.5 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-navy file:text-white hover:file:bg-blue-900">
                                    <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG (Maks. 2MB).</p>
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
                            <form method="POST">
                                <input type="hidden" name="change_password" value="1">
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