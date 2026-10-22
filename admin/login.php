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

        // Menggunakan pengecekan teks biasa (plaintext) agar sesuai dengan database
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
        body {
            background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 100%);
        }

        .glass-effect {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(147, 51, 234, 0.3);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 text-white">
    <div class="w-full max-w-md animate-fade-in">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full mb-4">
                <img src="../assets/default/logo-smk40.png" alt="Logo" class="w-24 h-24 object-contain">
            </div>
            <div class="inline-block bg-purple-600 text-white text-xs font-bold px-3 py-1 rounded-full mb-3 tracking-wider">
                KEAMANAN INFORMASI K2
            </div>
            <h1 class="text-3xl font-bold mb-2">Login Admin</h1>
            <p class="text-gray-400">Masuk ke akun Admin Anda</p>
        </div>

        <div class="glass-effect rounded-xl p-8 shadow-2xl">
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-500 rounded-lg p-3 mb-6 text-sm flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-5">
                    <label class="text-sm font-medium text-gray-300 block mb-2">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="username" 
                            class="w-full bg-gray-800/50 border border-gray-700 rounded-lg pl-10 pr-4 py-3 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                            placeholder="Masukkan username" required>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="text-sm font-medium text-gray-300 block mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="password"
                            class="w-full bg-gray-800/50 border border-gray-700 rounded-lg pl-10 pr-10 py-3 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                            placeholder="Masukkan password" required>
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-300">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-lg transition-colors flex items-center justify-center mt-6">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login
                </button>
            </form>
        </div>

        <div class="text-center mt-8 text-sm text-gray-500">
            <p>&copy; <?= date('Y') ?> Keamanan Informasi K2</p>
            <p class="mt-1">Sistem Informasi Keamanan Informasi K2</p>
            <a href="../siswa/login.php" class="inline-flex items-center gap-2 mt-3 text-purple-400 hover:text-purple-300 transition-colors">
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