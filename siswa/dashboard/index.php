<?php
require_once '../../config/database.php';

if (!isset($_SESSION['siswa_id'])) {
    header("Location: ../login.php");
    exit();
}

$siswa_id = $_SESSION['siswa_id'];
$siswa_name = $_SESSION['siswa_name'];
$siswa_nis = $_SESSION['siswa_nis'];

// PROSES ABSEN HADIR OLEH MAHASISWA
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['absen_hadir'])) {
    $pertemuan_ke = $_POST['pertemuan_ke'];
    
    // Cek apakah absensi untuk pertemuan ini sedang dibuka (dari tabel status_pertemuan)
    $check_stmt = $conn->prepare("SELECT is_open FROM status_pertemuan WHERE pertemuan_ke = :pertemuan_ke");
    $check_stmt->execute(['pertemuan_ke' => $pertemuan_ke]);
    $status_buka = $check_stmt->fetchColumn();

    if ($status_buka == 1) {
        // Cek apakah mahasiswa sudah absen di pertemuan ini
        $check_absen = $conn->prepare("SELECT id FROM absensi_pertemuan WHERE siswa_id = :siswa_id AND pertemuan_ke = :pertemuan_ke");
        $check_absen->execute(['siswa_id' => $siswa_id, 'pertemuan_ke' => $pertemuan_ke]);
        
        if ($check_absen->rowCount() > 0) {
            // Jika sudah ada, update status jadi Hadir
            $update_stmt = $conn->prepare("UPDATE absensi_pertemuan SET status = 'Hadir', approval_status = 'Approved' WHERE siswa_id = :siswa_id AND pertemuan_ke = :pertemuan_ke");
            $update_stmt->execute(['siswa_id' => $siswa_id, 'pertemuan_ke' => $pertemuan_ke]);
            $message = "Kehadiran Anda untuk Pertemuan $pertemuan_ke berhasil diperbarui menjadi Hadir.";
            $message_type = "success";
        } else {
            // Jika belum ada, insert baru
            $insert_stmt = $conn->prepare("INSERT INTO absensi_pertemuan (siswa_id, pertemuan_ke, status, approval_status) VALUES (:siswa_id, :pertemuan_ke, 'Hadir', 'Approved')");
            $insert_stmt->execute(['siswa_id' => $siswa_id, 'pertemuan_ke' => $pertemuan_ke]);
            $message = "Absensi Hadir untuk Pertemuan $pertemuan_ke berhasil dicatat.";
            $message_type = "success";
        }
    } else {
        $message = "Maaf, absensi untuk pertemuan ini sedang ditutup oleh Admin.";
        $message_type = "error";
    }
}

// Ambil daftar pertemuan yang SEDANG DIBUKA oleh admin (dari tabel status_pertemuan)
$sql_buka = "SELECT sp.pertemuan_ke, ap.tanggal, ap.metode 
             FROM status_pertemuan sp
             LEFT JOIN (SELECT pertemuan_ke, tanggal, metode FROM absensi_pertemuan GROUP BY pertemuan_ke) ap 
             ON sp.pertemuan_ke = ap.pertemuan_ke
             WHERE sp.is_open = 1 
             ORDER BY sp.pertemuan_ke ASC";
$stmt_buka = $conn->query($sql_buka);
$pertemuan_dibuka = $stmt_buka->fetchAll(PDO::FETCH_ASSOC);

// Ambil riwayat kehadiran mahasiswa ini
$sql_riwayat = "SELECT pertemuan_ke, status, tanggal FROM absensi_pertemuan WHERE siswa_id = :siswa_id ORDER BY pertemuan_ke DESC";
$stmt_riwayat = $conn->prepare($sql_riwayat);
$stmt_riwayat->execute(['siswa_id' => $siswa_id]);
$riwayat_absensi = $stmt_riwayat->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - Keamanan Informasi K2</title>
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
                    <?= substr($siswa_name, 0, 1) ?>
                </div>
                <div class="overflow-hidden">
                    <h2 class="font-bold text-sm truncate"><?= htmlspecialchars($siswa_name) ?></h2>
                    <p class="text-xs text-blue-200"><?= htmlspecialchars($siswa_nis) ?></p>
                </div>
            </div>
        </div>

        <nav class="p-4 space-y-2 overflow-y-auto no-scrollbar" style="max-height: calc(100vh - 180px);">
            <a href="index.php" class="flex items-center gap-3 bg-gold text-white p-3 rounded-lg font-medium shadow-md transition-colors">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="../profil/" class="flex items-center gap-3 text-blue-100 p-3 rounded-lg hover:bg-blue-800 transition-colors">
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
                <header class="mb-8 border-b border-gray-300 pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-navy">Selamat Datang, <?= explode(' ', $siswa_name)[0] ?>!</h1>
                        <p class="text-gray-500 mt-1"><?= date('l, d F Y') ?></p>
                    </div>
                    <div class="bg-white px-4 py-2 rounded-lg border border-gray-200 shadow-sm">
                        <i class="fas fa-clock text-gold mr-2"></i>
                        <span id="current-time" class="font-bold text-navy"></span>
                    </div>
                </header>

                <!-- Notifikasi -->
                <?php if ($message): ?>
                    <div class="<?= $message_type === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700' ?> border-l-4 p-4 mb-6 rounded shadow-sm flex items-center">
                        <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-3 text-lg"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Panel Kiri: Absensi yang Dibuka -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 h-fit">
                        <h3 class="text-lg font-bold text-navy mb-4 border-b border-gray-200 pb-3 flex items-center gap-2">
                            <i class="fas fa-door-open text-gold"></i> Absensi Tersedia
                        </h3>

                        <?php if (count($pertemuan_dibuka) > 0): ?>
                            <div class="space-y-4">
                                <?php foreach ($pertemuan_dibuka as $ptm): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 flex justify-between items-center bg-gray-50 hover:bg-white transition-colors">
                                        <div>
                                            <h4 class="font-bold text-navy">Pertemuan <?= $ptm['pertemuan_ke'] ?></h4>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-calendar-alt mr-1"></i> <?= $ptm['tanggal'] ? date('d M Y', strtotime($ptm['tanggal'])) : '-' ?> 
                                                | <i class="fas fa-chalkboard-teacher mr-1"></i> <?= $ptm['metode'] ?>
                                            </p>
                                        </div>
                                        <form method="POST">
                                            <input type="hidden" name="pertemuan_ke" value="<?= $ptm['pertemuan_ke'] ?>">
                                            <button type="submit" name="absen_hadir" class="px-6 py-2 bg-navy hover:bg-blue-900 text-white font-bold rounded-lg shadow transition-colors flex items-center gap-2">
                                                <i class="fas fa-check"></i> Hadir
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-lock text-2xl text-gray-400"></i>
                                </div>
                                <p class="text-gray-500 font-medium">Tidak ada absensi yang dibuka saat ini.</p>
                                <p class="text-sm text-gray-400 mt-1">Silakan tunggu Admin/Dosen membuka absensi.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Panel Kanan: Riwayat Singkat -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <h3 class="text-lg font-bold text-navy mb-4 border-b border-gray-200 pb-3 flex items-center gap-2">
                            <i class="fas fa-history text-gold"></i> Riwayat Kehadiran Anda
                        </h3>
                        
                        <div class="overflow-y-auto max-h-[400px] pr-2 custom-scrollbar">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="text-gray-500 border-b border-gray-200">
                                        <th class="pb-2 font-medium">Pertemuan</th>
                                        <th class="pb-2 font-medium">Tanggal</th>
                                        <th class="pb-2 font-medium text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if(count($riwayat_absensi) > 0): ?>
                                        <?php foreach ($riwayat_absensi as $riwayat): ?>
                                            <tr>
                                                <td class="py-3 font-semibold text-navy">Pertemuan <?= $riwayat['pertemuan_ke'] ?></td>
                                                <td class="py-3 text-gray-500"><?= $riwayat['tanggal'] ? date('d/m/y', strtotime($riwayat['tanggal'])) : '-' ?></td>
                                                <td class="py-3 text-right">
                                                    <?php 
                                                    $status = $riwayat['status'];
                                                    $warna = 'bg-gray-100 text-gray-600';
                                                    if ($status == 'Hadir') $warna = 'bg-green-100 text-green-700';
                                                    if ($status == 'Alpha') $warna = 'bg-red-100 text-red-700';
                                                    if ($status == 'Izin') $warna = 'bg-blue-100 text-blue-700';
                                                    if ($status == 'Sakit') $warna = 'bg-yellow-100 text-yellow-700';
                                                    ?>
                                                    <span class="px-2.5 py-1 rounded text-xs font-bold <?= $warna ?>">
                                                        <?= $status ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="py-4 text-center text-gray-400 italic">Belum ada riwayat absensi.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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

        function updateTime() {
            const timeElement = document.getElementById('current-time');
            if(timeElement) {
                const now = new Date();
                const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                timeElement.textContent = timeString + ' WIB';
            }
        }
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>