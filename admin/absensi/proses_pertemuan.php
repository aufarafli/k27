<?php
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pertemuan_ke = $_POST['pertemuan_ke'];
    $tanggal = $_POST['tanggal'];
    $metode = $_POST['metode'];
    $siswa_ids = $_POST['siswa_id'];

    try {
        $conn->beginTransaction();

        // 1. Cek status is_open saat ini
        $check_status = $conn->prepare("SELECT is_open FROM status_pertemuan WHERE pertemuan_ke = :pertemuan_ke");
        $check_status->execute(['pertemuan_ke' => $pertemuan_ke]);
        $status_sekarang = $check_status->fetchColumn();
        
        // Jika belum ada di tabel status, anggap 0 (tertutup)
        if ($status_sekarang === false) {
            $status_sekarang = 0;
        }

        // 2. LOGIKA BARU: Jika absensi sedang dibuka, maka simpan ini akan menutupnya.
        // Jika sudah ditutup, tetap ditutup (tidak berubah).
        $status_baru = ($status_sekarang == 1) ? 0 : 0; 
        
        // Update status di tabel status_pertemuan
        $update_status = $conn->prepare("INSERT INTO status_pertemuan (pertemuan_ke, is_open) VALUES (:pertemuan_ke, :is_open) 
                                         ON DUPLICATE KEY UPDATE is_open = :is_open");
        $update_status->execute([
            'pertemuan_ke' => $pertemuan_ke,
            'is_open' => $status_baru
        ]);

        // 3. Hapus data lama untuk pertemuan ini (agar tidak duplikat)
        $del_stmt = $conn->prepare("DELETE FROM absensi_pertemuan WHERE pertemuan_ke = :pertemuan_ke");
        $del_stmt->execute(['pertemuan_ke' => $pertemuan_ke]);

        // 4. Masukkan data kehadiran baru
        $sql = "INSERT INTO absensi_pertemuan (siswa_id, pertemuan_ke, status, approval_status, tanggal, metode) 
                VALUES (:siswa_id, :pertemuan_ke, :status, 'Approved', :tanggal, :metode)";
        $stmt = $conn->prepare($sql);

        foreach ($siswa_ids as $siswa_id) {
            // Default 'Hadir' jika tidak ada yang dicentang (mencegah error)
            $status = $_POST['status_' . $siswa_id] ?? 'Hadir'; 
            
            $stmt->execute([
                ':siswa_id' => $siswa_id,
                ':pertemuan_ke' => $pertemuan_ke,
                ':status' => $status,
                ':tanggal' => $tanggal,
                ':metode' => $metode
            ]);
        }

        $conn->commit();
        
        // Redirect ke halaman input absensi dengan pesan sukses
        header("Location: input_absensi.php?pertemuan=$pertemuan_ke&status=success");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        // Jika error, redirect dengan pesan error
        header("Location: input_absensi.php?pertemuan=$pertemuan_ke&status=error");
        exit();
    }
} else {
    header("Location: ../dashboard/index.php");
    exit();
}
?>