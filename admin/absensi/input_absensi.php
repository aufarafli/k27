<?php
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$pertemuan_ke = isset($_GET['pertemuan']) ? (int)$_GET['pertemuan'] : 1;

// PROSES BUKA/TUTUP ABSENSI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_absen'])) {
    $status_baru = $_POST['status_baru']; // 1 atau 0
    
    // Menggunakan tabel baru 'status_pertemuan'
    $stmt = $conn->prepare("INSERT INTO status_pertemuan (pertemuan_ke, is_open) VALUES (:pertemuan_ke, :is_open) 
                            ON DUPLICATE KEY UPDATE is_open = :is_open");
    $stmt->execute([
        'pertemuan_ke' => $pertemuan_ke,
        'is_open' => $status_baru
    ]);
    
    // Redirect agar tidak re-submit form
    header("Location: input_absensi.php?pertemuan=$pertemuan_ke");
    exit();
}

// Ambil list semua mahasiswa urut NIM
$stmt_siswa = $conn->query("SELECT id, nis, nama_lengkap, foto_profil FROM siswa ORDER BY nis ASC");
$siswa_list = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);

// Cek status buka/tutup dari tabel baru
$check_status = $conn->prepare("SELECT is_open FROM status_pertemuan WHERE pertemuan_ke = :pertemuan_ke");
$check_status->execute(['pertemuan_ke' => $pertemuan_ke]);
$is_open = $check_status->fetchColumn();
if ($is_open === false) $is_open = 0; // Default tertutup jika belum ada

// Ambil data absensi (hanya data asli, tanpa dummy)
$sql_absen = "SELECT siswa_id, status, tanggal, metode FROM absensi_pertemuan WHERE pertemuan_ke = :pertemuan_ke";
$stmt_absen = $conn->prepare($sql_absen);
$stmt_absen->execute(['pertemuan_ke' => $pertemuan_ke]);
$data_pertemuan = $stmt_absen->fetchAll(PDO::FETCH_ASSOC);

$absensi_data = [];
$info_pertemuan = ['tanggal' => date('Y-m-d'), 'metode' => null]; 

if (count($data_pertemuan) > 0) {
    $info_pertemuan['tanggal'] = $data_pertemuan[0]['tanggal'] ?: date('Y-m-d');
    $info_pertemuan['metode'] = $data_pertemuan[0]['metode'] ?: null; 
    
    foreach ($data_pertemuan as $row) {
        $absensi_data[$row['siswa_id']] = $row['status'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Absensi Pertemuan <?= $pertemuan_ke ?> - Keamanan Informasi K2</title>
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
        
        .custom-checkbox { width: 1.5rem; height: 1.5rem; cursor: pointer; accent-color: #003087; }
        
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .animate-modal { animation: fadeIn 0.3s ease-out forwards; }

        .peer:checked + .method-box {
            background-color: #003087 !important;
            color: #FFFFFF !important;
            border-color: #003087 !important;
        }

        .btn-calendar:hover, .btn-calendar:focus, .btn-calendar:active {
            background-color: #003087 !important;
            color: #FFFFFF !important;
            outline: none !important;
        }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 border-b border-gray-300 pb-4">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h1 class="text-2xl font-bold text-navy">Input Absensi: Pertemuan <?= $pertemuan_ke ?></h1>
                    <!-- Indikator Status Buka/Tutup -->
                    <?php if ($is_open == 1): ?>
                        <span class="bg-green-100 text-green-700 text-xs font-bold px-2.5 py-1 rounded border border-green-300">
                            <i class="fas fa-lock-open mr-1"></i> Absensi Dibuka
                        </span>
                    <?php else: ?>
                        <span class="bg-red-100 text-red-700 text-xs font-bold px-2.5 py-1 rounded border border-red-300">
                            <i class="fas fa-lock mr-1"></i> Absensi Ditutup
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-gray-500">Silakan tentukan tanggal, metode, dan status kehadiran mahasiswa</p>
            </div>
            
            <div class="flex flex-wrap gap-2">
                <!-- Tombol Buka/Tutup Absensi -->
                <form method="POST" class="inline">
                    <input type="hidden" name="toggle_absen" value="1">
                    <?php if ($is_open == 1): ?>
                        <input type="hidden" name="status_baru" value="0">
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-sm" onclick="return confirm('Tutup absensi ini? Mahasiswa tidak akan bisa absen.')">
                            <i class="fas fa-lock"></i> Tutup Absensi
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="status_baru" value="1">
                        <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-sm" onclick="return confirm('Buka absensi ini? Mahasiswa akan bisa absen.')">
                            <i class="fas fa-lock-open"></i> Buka Absensi
                        </button>
                    <?php endif; ?>
                </form>
                
                <a href="export_pertemuan.php?pertemuan=<?= $pertemuan_ke ?>&format=pdf" target="_blank" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="export_pertemuan.php?pertemuan=<?= $pertemuan_ke ?>&format=excel" target="_blank" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-file-excel"></i> Excel
                </a>

                <a href="../dashboard/index.php" class="px-4 py-2 bg-navy hover:bg-blue-900 text-white rounded-lg text-sm transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Card Form -->
        <form action="proses_pertemuan.php" method="POST" class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
            <input type="hidden" name="pertemuan_ke" value="<?= $pertemuan_ke ?>">
            
            <!-- Info Tanggal & Metode -->
            <div class="flex flex-col sm:flex-row gap-6 items-start mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div class="w-full sm:w-1/3">
                    <label class="text-sm font-semibold text-navy block mb-2"><i class="fas fa-calendar-alt mr-2"></i> Tanggal Pertemuan</label>
                    <div class="relative flex items-center">
                        <input type="date" name="tanggal" id="inputTanggal" value="<?= $info_pertemuan['tanggal'] ?>" 
                               class="w-full bg-white border border-gray-300 rounded-lg pl-4 pr-12 py-2 text-gray-800 focus:outline-none focus:border-navy focus:ring-1 focus:ring-navy">
                        <button type="button" onclick="document.getElementById('inputTanggal').showPicker()" 
                                class="btn-calendar absolute right-2 p-1.5 bg-blue-50 text-navy rounded transition-colors" title="Pilih Tanggal">
                            <i class="fas fa-calendar-day"></i>
                        </button>
                    </div>
                </div>

                <div class="w-full sm:w-2/3">
                    <label class="text-sm font-semibold text-navy block mb-2"><i class="fas fa-chalkboard-teacher mr-2"></i> Metode Perkuliahan</label>
                    <div class="flex flex-wrap gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="metode" value="Luring" class="peer sr-only" <?= $info_pertemuan['metode'] === 'Luring' ? 'checked' : '' ?>>
                            <div class="method-box px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-navy font-medium transition-all shadow-sm">
                                <i class="fas fa-building mr-1"></i> Luring
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="metode" value="Daring" class="peer sr-only" <?= $info_pertemuan['metode'] === 'Daring' ? 'checked' : '' ?>>
                            <div class="method-box px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-navy font-medium transition-all shadow-sm">
                                <i class="fas fa-video mr-1"></i> Daring
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="metode" value="Asynchronous" class="peer sr-only" <?= $info_pertemuan['metode'] === 'Asynchronous' ? 'checked' : '' ?>>
                            <div class="method-box px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-navy font-medium transition-all shadow-sm">
                                <i class="fas fa-clock mr-1"></i> Asynchronous
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Tabel Mahasiswa -->
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-navy text-white text-sm">
                            <th class="py-3 px-4 w-12 text-center font-medium">No</th>
                            <th class="py-3 px-4 w-40 font-medium">NIM</th>
                            <th class="py-3 px-4 font-medium">Nama Mahasiswa</th>
                            <th class="py-3 px-4 text-center w-24 font-medium">
                                <div class="flex flex-col items-center justify-center gap-1">
                                    <span>Hadir</span>
                                    <button type="button" id="btnHadir" onclick="toggleAllStatus('Hadir')" class="w-8 h-6 bg-white text-navy hover:bg-gold hover:text-white rounded flex items-center justify-center text-xs shadow transition-colors duration-200">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </div>
                            </th>
                            <th class="py-3 px-4 text-center w-24 font-medium">
                                <div class="flex flex-col items-center justify-center gap-1">
                                    <span>Alpha</span>
                                    <button type="button" id="btnAlpha" onclick="toggleAllStatus('Alpha')" class="w-8 h-6 bg-white text-navy hover:bg-gold hover:text-white rounded flex items-center justify-center text-xs shadow transition-colors duration-200">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </div>
                            </th>
                            <th class="py-3 px-4 text-center w-24 font-medium">Izin</th>
                            <th class="py-3 px-4 text-center w-24 font-medium">Sakit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <?php foreach ($siswa_list as $index => $mhs): 
                            $status_skrg = isset($absensi_data[$mhs['id']]) ? $absensi_data[$mhs['id']] : null;
                        ?>
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="py-3 px-4 text-center text-gray-600"><?= $index + 1 ?></td>
                            <td class="py-3 px-4 font-mono text-gray-800"><?= htmlspecialchars($mhs['nis']) ?></td>
                            <td class="py-3 px-4 font-medium text-gray-800"><?= htmlspecialchars($mhs['nama_lengkap']) ?></td>
                            
                            <input type="hidden" name="siswa_id[]" value="<?= $mhs['id'] ?>">
                            
                            <td class="py-3 px-4 text-center">
                                <input type="radio" name="status_<?= $mhs['id'] ?>" value="Hadir" class="custom-checkbox status-radio" data-type="Hadir" <?= $status_skrg === 'Hadir' ? 'checked' : '' ?>>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <input type="radio" name="status_<?= $mhs['id'] ?>" value="Alpha" class="custom-checkbox status-radio" data-type="Alpha" <?= $status_skrg === 'Alpha' ? 'checked' : '' ?>>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <input type="radio" name="status_<?= $mhs['id'] ?>" value="Izin" class="custom-checkbox status-radio" data-type="Izin" <?= $status_skrg === 'Izin' ? 'checked' : '' ?>>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <input type="radio" name="status_<?= $mhs['id'] ?>" value="Sakit" class="custom-checkbox status-radio" data-type="Sakit" <?= $status_skrg === 'Sakit' ? 'checked' : '' ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tombol Aksi -->
            <div class="mt-6 flex justify-between items-center border-t border-gray-200 pt-4">
                <button type="button" onclick="showDeleteModal()" class="px-6 py-2 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded-lg font-medium transition-colors flex items-center gap-2 border border-red-200">
                    <i class="fas fa-trash-alt"></i> Hapus Data
                </button>

                <button type="submit" class="px-6 py-2 bg-gold hover:bg-yellow-600 text-white rounded-lg font-bold transition-colors shadow-md flex items-center gap-2">
                    <i class="fas fa-save"></i> Simpan Kehadiran
                </button>
            </div>
        </form>
    </div>

    <!-- Modal Konfirmasi Hapus Data -->
    <div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
        <div class="bg-white p-8 rounded-xl max-w-sm w-full text-center animate-modal shadow-2xl border-t-4 border-red-500">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Data?</h3>
            <p class="text-gray-500 mb-6 text-sm">Apakah Anda yakin ingin menghapus seluruh data kehadiran untuk <strong>Pertemuan <?= $pertemuan_ke ?></strong>? Tindakan ini tidak dapat dibatalkan.</p>
            
            <div class="flex gap-3">
                <button onclick="hideDeleteModal()" class="w-1/2 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition-colors">
                    Tidak
                </button>
                <form action="hapus_absensi.php" method="POST" class="w-1/2">
                    <input type="hidden" name="pertemuan_ke" value="<?= $pertemuan_ke ?>">
                    <button type="submit" class="w-full py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-colors">
                        Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Pop-up Sukses -->
    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white p-8 rounded-xl max-w-sm w-full text-center animate-modal shadow-2xl border-t-4 border-green-500">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Berhasil!</h3>
            <p class="text-gray-500 mb-6 text-sm">Data kehadiran Pertemuan <?= $pertemuan_ke ?> telah berhasil disimpan.</p>
            <button onclick="goBackToDashboard()" class="w-full py-2 bg-navy hover:bg-blue-900 text-white font-semibold rounded-lg transition-colors shadow-md">
                Oke, Kembali
            </button>
        </div>
    </div>
    <?php endif; ?>

    <script>
        let isHadirActive = false;
        let isAlphaActive = false;

        function toggleAllStatus(type) {
            const checkboxes = document.querySelectorAll(`.status-radio[data-type="${type}"]`);
            const btnHadir = document.getElementById('btnHadir');
            const btnAlpha = document.getElementById('btnAlpha');

            if (type === 'Hadir') {
                if (!isHadirActive) {
                    checkboxes.forEach(cb => { cb.checked = true; });
                    btnHadir.classList.remove('bg-white', 'text-navy');
                    btnHadir.classList.add('bg-gold', 'text-white');
                    isHadirActive = true;
                    if (isAlphaActive) {
                        btnAlpha.classList.remove('bg-gold', 'text-white');
                        btnAlpha.classList.add('bg-white', 'text-navy');
                        isAlphaActive = false;
                    }
                } else {
                    checkboxes.forEach(cb => { cb.checked = false; });
                    btnHadir.classList.remove('bg-gold', 'text-white');
                    btnHadir.classList.add('bg-white', 'text-navy');
                    isHadirActive = false;
                }
            }

            if (type === 'Alpha') {
                if (!isAlphaActive) {
                    checkboxes.forEach(cb => { cb.checked = true; });
                    btnAlpha.classList.remove('bg-white', 'text-navy');
                    btnAlpha.classList.add('bg-gold', 'text-white');
                    isAlphaActive = true;
                    if (isHadirActive) {
                        btnHadir.classList.remove('bg-gold', 'text-white');
                        btnHadir.classList.add('bg-white', 'text-navy');
                        isHadirActive = false;
                    }
                } else {
                    checkboxes.forEach(cb => { cb.checked = false; });
                    btnAlpha.classList.remove('bg-gold', 'text-white');
                    btnAlpha.classList.add('bg-white', 'text-navy');
                    isAlphaActive = false;
                }
            }
        }

        function goBackToDashboard() {
            window.location.href = '../dashboard/index.php';
        }

        function showDeleteModal() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</body>
</html>