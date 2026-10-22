<?php
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pertemuan = $_POST['pertemuan'];
    $siswa_ids = $_POST['siswa_id'];
    
    try {
        $conn->beginTransaction();

        // Query untuk insert atau update (jika sudah ada)
        $sql = "INSERT INTO absensi_pertemuan (siswa_id, pertemuan_ke, status, approval_status) 
                VALUES (:siswa_id, :pertemuan, :status, 'Approved')
                ON DUPLICATE KEY UPDATE status = :status, approval_status = 'Approved'";
        
        $stmt = $conn->prepare($sql);

        // Looping semua siswa yang ada di tabel
        foreach ($siswa_ids as $siswa_id) {
            // Ambil status dari radio button yang dipilih (name="status_ID")
            $status = $_POST['status_' . $siswa_id] ?? 'Alpha';

            $stmt->execute([
                ':siswa_id' => $siswa_id,
                ':pertemuan' => $pertemuan,
                ':status' => $status
            ]);
        }

        $conn->commit();
        
        // Redirect kembali ke halaman pertemuan dengan pesan sukses
        header("Location: pertemuan.php?p=$pertemuan&status=success");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        die("Terjadi kesalahan saat menyimpan data: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit();
}
?>