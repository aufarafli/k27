<?php
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pertemuan_ke = $_POST['pertemuan_ke'];

    try {
        $conn->beginTransaction();

        // Hapus data untuk pertemuan ini
        $del_stmt = $conn->prepare("DELETE FROM absensi_pertemuan WHERE pertemuan_ke = :pertemuan_ke");
        $del_stmt->execute(['pertemuan_ke' => $pertemuan_ke]);

        $conn->commit();
        
        // Redirect langsung ke halaman daftar pertemuan (Dashboard)
        header("Location: ../dashboard/index.php");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        die("Terjadi kesalahan saat menghapus data: " . $e->getMessage());
    }
} else {
    header("Location: ../dashboard/index.php");
    exit();
}
?>