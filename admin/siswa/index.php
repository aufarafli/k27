<?php
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// 1. Ambil data mahasiswa dari database
$query = "SELECT id, nis, nama_lengkap, email, foto_profil FROM siswa ORDER BY nis ASC";
$stmt = $conn->query($query);
$siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_siswa = count($siswa_list);

// 2. Ambil data rekap kehadiran semua siswa
// Query ini mengambil status per siswa per pertemuan
$query_rekap = "SELECT siswa_id, pertemuan_ke, status FROM absensi_pertemuan";
$stmt_rekap = $conn->query($query_rekap);
$rekap_data = [];

while ($row = $stmt_rekap->fetch(PDO::FETCH_ASSOC)) {
    // Struktur array: $rekap_data[siswa_id][pertemuan_ke] = status
    $rekap_data[$row['siswa_id']][$row['pertemuan_ke']] = $row['status'];
}

$total_pertemuan = 14; // Sesuaikan jumlah pertemuan
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa - Keamanan Informasi K2</title>
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

        /* Style untuk lingkaran rekap */
        .rekap-circle {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 10px;
            font-weight: bold;
            margin-right: 2px;
            cursor: help;
            color: white;
        }
        .rekap-hadir { background-color: #16a34a; } /* Hijau */
        .rekap-sakit { background-color: #eab308; } /* Kuning */
        .rekap-izin { background-color: #3b82f6; } /* Biru */
        .rekap-alpha { background-color: #ef4444; } /* Merah */
        .rekap-kosong { background-color: #e2e8f0; color: #94a3b8; } /* Abu-abu */
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

        <nav class="p-4 space-y-2 overflow-y-auto no-scrollbar" style="max-height: calc(100vh - 76px);">
            <a href="../dashboard/" class="flex items-center gap-3 text-blue-100 p-3 rounded-lg hover:bg-blue-800 transition-colors">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <!-- Menu Aktif (Warna Emas) -->
            <a href="index.php" class="flex items-center gap-3 bg-gold text-white p-3 rounded-lg font-medium shadow-md transition-colors">
                <i class="fas fa-users"></i>
                <span>Data Mahasiswa</span>
            </a>
            <a href="../profil/" class="flex items-center gap-3 text-blue-100 p-3 rounded-lg hover:bg-blue-800 transition-colors">
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
            <div class="max-w-7xl mx-auto">
                <header class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end border-b border-gray-300 pb-4 gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-navy">Data Mahasiswa</h1>
                        <p class="text-gray-500 mt-1">Daftar seluruh mahasiswa kelas Keamanan Informasi K2</p>
                    </div>
                    <div class="bg-white px-4 py-2 rounded-lg border border-gray-200 shadow-sm">
                        <span class="text-sm text-gray-500">Total: </span>
                        <span class="font-bold text-navy"><?= $total_siswa ?> Mahasiswa</span>
                    </div>
                </header>

                <!-- Tabel Data Mahasiswa -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-navy text-white text-sm">
                                    <th class="px-4 py-4 w-12 text-center font-medium">No</th>
                                    <th class="px-4 py-4 w-40 font-medium">NIM</th>
                                    <th class="px-4 py-4 font-medium w-64">Nama Mahasiswa</th>
                                    <th class="px-4 py-4 font-medium text-center">Rekap Kehadiran (Pertemuan 1-14)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($siswa_list as $index => $mhs): ?>
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-4 py-4 text-sm text-center text-gray-600"><?= $index + 1 ?></td>
                                        <td class="px-4 py-4 text-sm font-mono text-gray-800"><?= htmlspecialchars($mhs['nis']) ?></td>
                                        <td class="px-4 py-4 text-sm font-medium text-gray-800 flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-navy font-bold text-xs flex-shrink-0">
                                                <?= substr($mhs['nama_lengkap'], 0, 1) ?>
                                            </div>
                                            <span class="truncate"><?= htmlspecialchars($mhs['nama_lengkap']) ?></span>
                                        </td>
                                        <td class="px-4 py-4 text-sm">
                                            <!-- Looping Rekap Pertemuan -->
                                            <div class="flex flex-wrap gap-1 justify-center">
                                                <?php 
                                                for ($i = 1; $i <= $total_pertemuan; $i++):
                                                    $status_absen = isset($rekap_data[$mhs['id']][$i]) ? $rekap_data[$mhs['id']][$i] : null;
                                                    
                                                    $circle_class = 'rekap-kosong';
                                                    $icon = '<i class="fas fa-minus"></i>'; // Default kosong
                                                    $tooltip = "Pertemuan $i: Belum Diisi";
                                                    
                                                    if ($status_absen == 'Hadir') {
                                                        $circle_class = 'rekap-hadir';
                                                        $icon = '<i class="fas fa-check"></i>';
                                                        $tooltip = "Pertemuan $i: Hadir";
                                                    } elseif ($status_absen == 'Sakit') {
                                                        $circle_class = 'rekap-sakit';
                                                        $icon = 'S';
                                                        $tooltip = "Pertemuan $i: Sakit";
                                                    } elseif ($status_absen == 'Izin') {
                                                        $circle_class = 'rekap-izin';
                                                        $icon = 'I';
                                                        $tooltip = "Pertemuan $i: Izin";
                                                    } elseif ($status_absen == 'Alpha') {
                                                        $circle_class = 'rekap-alpha';
                                                        $icon = 'X';
                                                        $tooltip = "Pertemuan $i: Alpha";
                                                    }
                                                ?>
                                                    <div class="rekap-circle <?= $circle_class ?>" title="<?= $tooltip ?>">
                                                        <?= $icon ?>
                                                    </div>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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