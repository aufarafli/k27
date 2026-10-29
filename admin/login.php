<?php
require_once '../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard/");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && $password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['nama_lengkap'];
            $_SESSION['admin_photo'] = $admin['foto_profil'];
            
            $log_stmt = $conn->prepare("INSERT INTO activity_log (user_type, user_id, activity_type, description, created_at) VALUES ('admin', :user_id, 'login', :description, NOW())");
            $log_stmt->execute([
                'user_id' => $admin['id'],
                'description' => 'Admin ' . $admin['nama_lengkap'] . ' login ke sistem'
            ]);

            header("Location: dashboard/");
            exit();
        } else {
            $error = 'Username atau password salah';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Keamanan Informasi K2</title>
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
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md animate-fade-in">
        
        <!-- Header yang Sudah Dirapikan -->
        <div class="text-center mb-8">
            <!-- Menyatukan Logo dan Badge dalam satu baris flexbox -->
            <div class="flex items-center justify-center gap-4 mb-6">
                <div class="w-20 h-20 bg-white shadow-sm border border-gray-200 rounded-full p-1.5 flex-shrink-0">
                    <img src="../assets/default/logo-smk40.png" alt="Logo" class="w-full h-full object-contain">
                </div>
                <div class="bg-navy text-white text-xs font-bold px-4 py-2 rounded-full tracking-wider shadow-md">
                    KEAMANAN INFORMASI K2
                </div>
            </div>
            
            <!-- Judul dan Sub-judul -->
            <h1 class="text-3xl font-extrabold text-navy mb-2">Login Admin</h1>
            <p class="text-gray-500 text-sm">Masuk ke akun Admin Anda</p>
        </div>

        <!-- Card Form Login -->
        <div class="bg-white rounded-xl shadow-lg p-8 border border-gray-200">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg p-3 mb-6 text-sm flex items-center shadow-sm">
                    <i class="fas fa-exclamation-circle mr-3 text-lg"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-5">
                    <label class="text-sm font-semibold text-gray-700 block mb-2">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="username" 
                            class="w-full bg-gray-50 border border-gray-300 rounded-lg pl-10 pr-4 py-3 text-gray-800 focus:outline-none focus:border-navy focus:ring-1 focus:ring-navy transition-colors"
                            placeholder="Masukkan username" required>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="text-sm font-semibold text-gray-700 block mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="password"
                            class="w-full bg-gray-50 border border-gray-300 rounded-lg pl-10 pr-10 py-3 text-gray-800 focus:outline-none focus:border-navy focus:ring-1 focus:ring-navy transition-colors"
                            placeholder="Masukkan password" required>
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-navy transition-colors">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-navy hover:bg-blue-900 text-white font-bold py-3 rounded-lg transition-colors flex items-center justify-center shadow-md">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-sm text-gray-500">
            <p>&copy; <?= date('Y') ?> Keamanan Informasi K2</p>
            <p class="mt-1">Sistem Informasi Keamanan Informasi K2</p>
            <a href="../siswa/login.php" class="inline-flex items-center gap-2 mt-4 text-navy font-semibold hover:text-gold transition-colors">
                <i class="fas fa-user-graduate"></i> Login Mahasiswa
            </a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>