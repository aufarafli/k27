<?php
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$pertemuan = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$notif_success = isset($_GET['status']) && $_GET['status'] === 'success';

// Ambil kelas default
$stmt_kelas = $conn->query("SELECT DISTINCT kelas FROM siswa LIMIT 1");
$kelas_default = $stmt_kelas->fetchColumn() ?? 'Kelas Default';

// Ambil daftar siswa dan status absensinya
$sql = "SELECT s.id, s.nis, s.nama_lengkap, s.foto_profil, a.status 
        FROM siswa s 
        LEFT JOIN absensi_pertemuan a ON s.id = a.siswa_id AND a.pertemuan_ke = :pertemuan
        WHERE s.kelas = :kelas
        ORDER BY s.nama_lengkap ASC";

$stmt = $conn->prepare($sql);
$stmt->execute(['pertemuan' => $pertemuan, 'kelas' => $kelas_default]);
$siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cek apakah data untuk pertemuan ini sudah pernah diisi (di-save)
$is_saved = false;
$total_hadir = 0; $total_alpha = 0; $total_izin = 0;

foreach ($siswa_list as $s) {
    if ($s['status'] !== null) {
        $is_saved = true; 
        if ($s['status'] === 'Hadir') $total_hadir++;
        elseif ($s['status'] === 'Alpha') $total_alpha++;
        elseif ($s['status'] === 'Izin') $total_izin++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pertemuan <?= $pertemuan ?> - Keamanan Informasi 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .glass-effect { background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(147, 51, 234, 0.3); }
        .menu-active { background: linear-gradient(to right, rgba(147, 51, 234, 0.2), rgba(147, 51, 234, 0.05)); border-left: 4px solid #9333ea; }
        body { background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 100%); }
        
        /* Styling Radio Button Kustom */
        .custom-radio {
            width: 24px; height: 24px; cursor: pointer;
            appearance: none; border-radius: 50%;
            border: 2px solid #4B5563; background-color: transparent;
            transition: all 0.2s ease;
        }
        .custom-radio:checked {
            border-color: #9333ea;
            background-color: #9333ea;
            box-shadow: inset 0 0 0 3px #1E1B4B;
        }
        .custom-radio:disabled {
            cursor: not-allowed;
            opacity: 0.8;
        }
        .custom-radio:checked:disabled {
            opacity: 1; /* Tetap terang meskipun disabled */
            border-color: #9333ea;
            background-color: #9333ea;
        }
    </style>
</head>
<body class="min-h-screen text-white bg-fixed flex">
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

    <main class="lg:ml-64 min-h-screen w-full p-4 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <header class="flex flex-wrap justify-between items-center mb-6 gap-4">
                <div class="flex items-center gap-4">
                    <a href="index.php" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold">Pertemuan <?= $pertemuan ?></h1>
                        <p class="text-gray-400">Kelas <?= htmlspecialchars($kelas_default) ?></p>
                    </div>
                </div>
                <!-- UPDATE TOMBOL EXPORT DI SINI -->
                <div class="flex gap-3">
                    <a href="export_pertemuan.php?p=<?= $pertemuan ?>&format=pdf" target="_blank" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-medium flex items-center gap-2">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <a href="export_pertemuan.php?p=<?= $pertemuan ?>&format=excel" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-medium flex items-center gap-2">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                </div>
            </header>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="glass-effect rounded-xl p-4 flex items-center border-l-4 border-green-500">
                    <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center mr-4"><i class="fas fa-check text-green-500 text-xl"></i></div>
                    <div><p class="text-sm text-gray-400">Hadir</p><p class="text-2xl font-bold" id="count-hadir"><?= $total_hadir ?></p></div>
                </div>
                <div class="glass-effect rounded-xl p-4 flex items-center border-l-4 border-red-500">
                    <div class="w-12 h-12 bg-red-500/20 rounded-full flex items-center justify-center mr-4"><i class="fas fa-times text-red-500 text-xl"></i></div>
                    <div><p class="text-sm text-gray-400">Alpha</p><p class="text-2xl font-bold" id="count-alpha"><?= $total_alpha ?></p></div>
                </div>
                <div class="glass-effect rounded-xl p-4 flex items-center border-l-4 border-purple-500">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-full flex items-center justify-center mr-4"><i class="fas fa-envelope text-purple-500 text-xl"></i></div>
                    <div><p class="text-sm text-gray-400">Izin / Sakit</p><p class="text-2xl font-bold" id="count-izin"><?= $total_izin ?></p></div>
                </div>
            </div>

            <form action="proses_pertemuan.php" method="POST" id="formAbsensi">
                <input type="hidden" name="pertemuan" value="<?= $pertemuan ?>">
                
                <div class="glass-effect rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap">
                            <thead>
                                <tr class="bg-gray-800/50 text-gray-300 text-left">
                                    <th class="px-6 py-4 text-sm font-medium">Nama Mahasiswa</th>
                                    <th class="px-6 py-4 text-sm font-medium">NIS / NIM</th>
                                    <th class="px-6 py-4 text-sm font-medium text-center border-l border-gray-700">
                                        <div class="flex flex-col items-center gap-1">
                                            <span>Hadir</span>
                                            <input type="checkbox" class="custom-checkbox toggle-all" data-target="hadir" onclick="toggleAll('hadir', this)" <?= $is_saved ? 'disabled' : '' ?>>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-sm font-medium text-center border-l border-gray-700">
                                        <div class="flex flex-col items-center gap-1">
                                            <span>Alpha</span>
                                            <input type="checkbox" class="custom-checkbox toggle-all" data-target="alpha" onclick="toggleAll('alpha', this)" <?= $is_saved ? 'disabled' : '' ?>>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-sm font-medium text-center border-l border-gray-700">
                                        <div class="flex flex-col items-center gap-1">
                                            <span>Izin / Sakit</span>
                                            <input type="checkbox" class="custom-checkbox toggle-all" data-target="izin" onclick="toggleAll('izin', this)" <?= $is_saved ? 'disabled' : '' ?>>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800">
                                <?php foreach ($siswa_list as $mhs): ?>
                                    <?php $status = $mhs['status'] ?? 'Alpha'; ?>
                                    <tr class="hover:bg-purple-900/5 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <img src="../../<?= $mhs['foto_profil'] ?: 'assets/default/avatar.png' ?>" class="w-8 h-8 rounded-full object-cover mr-3">
                                                <span><?= htmlspecialchars($mhs['nama_lengkap']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm"><?= htmlspecialchars($mhs['nis']) ?></td>
                                        
                                        <input type="hidden" name="siswa_id[]" value="<?= $mhs['id'] ?>">
                                        
                                        <td class="px-6 py-4 text-center border-l border-gray-800">
                                            <input type="radio" name="status_<?= $mhs['id'] ?>" value="Hadir" class="custom-radio radio-hadir" <?= $status === 'Hadir' ? 'checked' : '' ?> <?= $is_saved ? 'disabled' : '' ?>>
                                        </td>
                                        <td class="px-6 py-4 text-center border-l border-gray-800">
                                            <input type="radio" name="status_<?= $mhs['id'] ?>" value="Alpha" class="custom-radio radio-alpha" <?= $status === 'Alpha' ? 'checked' : '' ?> <?= $is_saved ? 'disabled' : '' ?>>
                                        </td>
                                        <td class="px-6 py-4 text-center border-l border-gray-800">
                                            <input type="radio" name="status_<?= $mhs['id'] ?>" value="Izin" class="custom-radio radio-izin" <?= $status === 'Izin' ? 'checked' : '' ?> <?= $is_saved ? 'disabled' : '' ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-6 border-t border-gray-800 flex justify-end">
                        <?php if ($is_saved): ?>
                            <button type="button" onclick="enableEdit()" id="btnEdit" class="px-8 py-3 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-bold shadow-lg flex items-center gap-2 transition-colors">
                                <i class="fas fa-edit"></i> Edit Kehadiran
                            </button>
                            <button type="submit" id="btnSimpan" class="hidden px-8 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold shadow-lg flex items-center gap-2 transition-colors">
                                <i class="fas fa-save"></i> Simpan Kehadiran
                            </button>
                        <?php else: ?>
                            <button type="submit" class="px-8 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold shadow-lg flex items-center gap-2 transition-colors">
                                <i class="fas fa-save"></i> Simpan Kehadiran
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
        <?php if ($notif_success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Data kehadiran berhasil disimpan.',
            background: '#1E293B',
            color: '#fff',
            confirmButtonColor: '#9333ea'
        }).then((result) => {
            window.history.replaceState(null, null, window.location.pathname + "?p=<?= $pertemuan ?>");
        });
        <?php endif; ?>

        function toggleAll(type, source) {
            const isChecked = source.checked;
            let checkboxes;
            if (type === 'hadir') checkboxes = document.querySelectorAll('.radio-hadir');
            else if (type === 'alpha') checkboxes = document.querySelectorAll('.radio-alpha');
            else if (type === 'izin') checkboxes = document.querySelectorAll('.radio-izin');

            checkboxes.forEach(cb => {
                if (!cb.disabled) cb.checked = isChecked;
            });
        }

        function enableEdit() {
            document.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(el => {
                el.disabled = false;
            });
            document.getElementById('btnEdit').classList.add('hidden');
            document.getElementById('btnSimpan').classList.remove('hidden');
        }
    </script>
</body>
</html>