<?php
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// 1. Ambil data pertemuan dan info tanggal/metodenya dari tabel absensi_pertemuan
$query_pertemuan = "SELECT pertemuan_ke, tanggal, metode FROM absensi_pertemuan GROUP BY pertemuan_ke ORDER BY pertemuan_ke ASC";
$stmt_pertemuan = $conn->query($query_pertemuan);
$data_pertemuan = $stmt_pertemuan->fetchAll(PDO::FETCH_ASSOC);

$info_pertemuan = [];
foreach ($data_pertemuan as $row) {
    $info_pertemuan[$row['pertemuan_ke']] = [
        'tanggal' => $row['tanggal'] ? date('d M Y', strtotime($row['tanggal'])) : '-',
        'metode' => $row['metode'] ? $row['metode'] : '-'
    ];
}

$pertemuan_terisi = array_column($data_pertemuan, 'pertemuan_ke');

// 2. Ambil status Buka/Tutup secara terpisah dari tabel status_pertemuan
$query_status = "SELECT pertemuan_ke, is_open FROM status_pertemuan";
$stmt_status = $conn->query($query_status);
$status_buka = [];
while ($row = $stmt_status->fetch(PDO::FETCH_ASSOC)) {
    $status_buka[$row['pertemuan_ke']] = $row['is_open'];
}

$total_pertemuan_kuliah = 14; 
$list_pertemuan = range(1, $total_pertemuan_kuliah);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Keamanan Informasi K2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Custom Colors */
        .bg-navy { background-color: #003087; }
        .text-navy { color: #003087; }
        .border-navy { border-color: #003087; }
        .bg-gold { background-color: #F2A900; }
        .text-gold { color: #F2A900; }
        
        body { background-color: #F8FAFC; }
        
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Card styling */
        .card { 
            background: #FFFFFF; 
            border: 1px solid #E2E8F0; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); 
            transition: all 0.3s ease;
        }
        .card:hover { 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); 
            transform: translateY(-2px); 
            border-color: #CBD5E1;
        }
        
        /* Class khusus untuk card yang sudah selesai diisi (Stroke Navy) */
        .card-filled { 
            border: 2px solid #003087 !important; 
        }
    </style>
</head>

<body class="min-h-screen text-gray-800">
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
            <a href="index.php" class="flex items-center gap-3 bg-gold text-white p-3 rounded-lg font-medium shadow-md transition-colors">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="../siswa/" class="flex items-center gap-3 text-blue-100 p-3 rounded-lg hover:bg-blue-800 transition-colors">
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
                <header class="mb-8 border-b border-gray-300 pb-4">
                    <h1 class="text-2xl md:text-3xl font-bold text-navy">Daftar Pertemuan</h1>
                    <p class="text-gray-500 mt-1">Pilih pertemuan untuk melihat dan mengisi daftar kehadiran.</p>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php 
                    foreach ($list_pertemuan as $pertemuan_ke): 
                        $is_terisi = in_array($pertemuan_ke, $pertemuan_terisi);
                        $is_open = isset($status_buka[$pertemuan_ke]) ? $status_buka[$pertemuan_ke] : 0;
                        
                        // KONDISI SELESAI: Harus ada data (terisi) DAN sudah ditutup (is_open == 0)
                        $is_selesai = $is_terisi && ($is_open == 0);

                        $count_hadir = 0;
                        $count_alpha = 0;
                        $count_izin = 0;
                        $count_sakit = 0;

                        if ($is_terisi) {
                            $sql_absen = "SELECT status, COUNT(*) as count 
                                          FROM absensi_pertemuan 
                                          WHERE pertemuan_ke = :pertemuan_ke 
                                          AND approval_status = 'Approved'
                                          GROUP BY status";
                            $stmt_absen = $conn->prepare($sql_absen);
                            $stmt_absen->execute(['pertemuan_ke' => $pertemuan_ke]);
                            $data_absen = $stmt_absen->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($data_absen as $row) {
                                if ($row['status'] == 'Hadir') $count_hadir = $row['count'];
                                if ($row['status'] == 'Alpha') $count_alpha = $row['count'];
                                if ($row['status'] == 'Izin') $count_izin = $row['count'];
                                if ($row['status'] == 'Sakit') $count_sakit = $row['count'];
                            }
                        }

                        $tgl_tampil = isset($info_pertemuan[$pertemuan_ke]['tanggal']) ? $info_pertemuan[$pertemuan_ke]['tanggal'] : '-';
                        $metode_tampil = isset($info_pertemuan[$pertemuan_ke]['metode']) ? $info_pertemuan[$pertemuan_ke]['metode'] : '-';
                    ?>
                    
                    <!-- Kotak Pertemuan -->
                    <div onclick="window.location.href='../absensi/input_absensi.php?pertemuan=<?= $pertemuan_ke ?>'" 
                         class="card rounded-xl p-5 cursor-pointer flex flex-col justify-between
                         <?= $is_selesai ? 'card-filled' : '' ?>">
                        
                        <div>
                            <div class="flex justify-between items-start mb-4 pb-3 border-b border-gray-100">
                                <div>
                                    <h3 class="text-lg font-bold text-navy">Pertemuan <?= $pertemuan_ke ?></h3>
                                    <div class="flex flex-col gap-1 mt-1">
                                        <!-- Status Teks -->
                                        <p class="text-xs font-semibold 
                                            <?php 
                                                if ($is_selesai) echo 'text-green-600';
                                                elseif ($is_open == 1) echo 'text-gold'; // Sedang Berlangsung (Kuning)
                                                else echo 'text-gray-400';
                                            ?>">
                                            <i class="fas 
                                                <?php 
                                                    if ($is_selesai) echo 'fa-check-circle';
                                                    elseif ($is_open == 1) echo 'fa-spinner';
                                                    else echo 'fa-clock';
                                                ?> mr-1
                                            "></i>
                                            <?= $is_selesai ? 'Selesai Diisi' : ($is_open == 1 ? 'Sedang Berlangsung' : 'Belum Diisi') ?>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <i class="fas fa-calendar-alt mr-1 text-gray-400"></i> <?= $is_terisi ? $tgl_tampil : 'Belum ada jadwal' ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <i class="fas fa-chalkboard-teacher mr-1 text-gray-400"></i> <?= $is_terisi ? $metode_tampil : '-' ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Ikon Gembok -->
                                <?php if ($is_open == 1): ?>
                                    <!-- Gembok Terbuka (Sedang Berlangsung) -->
                                    <div class="h-10 w-10 rounded-full flex items-center justify-center bg-yellow-50 border border-yellow-200">
                                        <i class="fas fa-lock-open text-gold text-lg"></i>
                                    </div>
                                <?php elseif ($is_selesai): ?>
                                    <!-- Gembok Tertutup (Selesai) -->
                                    <div class="h-10 w-10 rounded-full flex items-center justify-center bg-gray-100 border border-gray-200">
                                        <i class="fas fa-lock text-navy text-lg"></i>
                                    </div>
                                <?php else: ?>
                                    <!-- Gembok Tertutup (Belum Diisi) -->
                                    <div class="h-10 w-10 rounded-full flex items-center justify-center bg-gray-100 border border-gray-200">
                                        <i class="fas fa-lock text-gray-400 text-lg"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Grid Statistik -->
                            <!-- Warna hanya muncul jika $is_selesai == true -->
                            <div class="grid grid-cols-4 gap-1 text-center">
                                <div class="<?= $is_selesai ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200' ?> rounded border py-1.5">
                                    <p class="text-[10px] text-gray-500 font-medium mb-0.5">Hadir</p>
                                    <p class="text-sm font-bold <?= $is_selesai ? 'text-green-700' : 'text-gray-400' ?>"><?= $count_hadir ?></p>
                                </div>
                                <div class="<?= $is_selesai ? 'bg-red-50 border-red-200' : 'bg-white border-gray-200' ?> rounded border py-1.5">
                                    <p class="text-[10px] text-gray-500 font-medium mb-0.5">Alpha</p>
                                    <p class="text-sm font-bold <?= $is_selesai ? 'text-red-700' : 'text-gray-400' ?>"><?= $count_alpha ?></p>
                                </div>
                                <div class="<?= $is_selesai ? 'bg-blue-50 border-blue-200' : 'bg-white border-gray-200' ?> rounded border py-1.5">
                                    <p class="text-[10px] text-gray-500 font-medium mb-0.5">Izin</p>
                                    <p class="text-sm font-bold <?= $is_selesai ? 'text-blue-700' : 'text-gray-400' ?>"><?= $count_izin ?></p>
                                </div>
                                <div class="<?= $is_selesai ? 'bg-yellow-50 border-yellow-200' : 'bg-white border-gray-200' ?> rounded border py-1.5">
                                    <p class="text-[10px] text-gray-500 font-medium mb-0.5">Sakit</p>
                                    <p class="text-sm font-bold <?= $is_selesai ? 'text-yellow-700' : 'text-gray-400' ?>"><?= $count_sakit ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <span class="text-xs font-semibold <?= $is_selesai ? 'text-navy' : 'text-blue-500' ?>">
                                <i class="fas fa-mouse-pointer mr-1"></i> Klik untuk isi absensi
                            </span>
                        </div>

                    </div>
                    <?php endforeach; ?>
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