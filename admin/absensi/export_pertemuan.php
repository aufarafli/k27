<?php
require_once '../../config/database.php';
require_once '../../vendor/autoload.php'; // Panggil library DOMPDF

use Dompdf\Dompdf;

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$pertemuan = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

// Ambil kelas default
$stmt_kelas = $conn->query("SELECT DISTINCT kelas FROM siswa LIMIT 1");
$kelas_default = $stmt_kelas->fetchColumn() ?? 'Kelas Default';

// Ambil data siswa dan absensinya
$sql = "SELECT s.nis, s.nama_lengkap, a.status 
        FROM siswa s 
        LEFT JOIN absensi_pertemuan a ON s.id = a.siswa_id AND a.pertemuan_ke = :pertemuan
        WHERE s.kelas = :kelas
        ORDER BY s.nama_lengkap ASC";

$stmt = $conn->prepare($sql);
$stmt->execute(['pertemuan' => $pertemuan, 'kelas' => $kelas_default]);
$siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jika formatnya EXCEL (CSV)
if ($format === 'excel') {
    $filename = "Absensi_Pertemuan_" . $pertemuan . "_Kelas_" . str_replace(' ', '_', $kelas_default) . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Header kolom Excel
    fputcsv($output, ['No', 'NIS/NIM', 'Nama Mahasiswa', 'Status Kehadiran']);
    
    $no = 1;
    foreach ($siswa_list as $row) {
        $status = $row['status'] ?? 'Belum Diabsen';
        fputcsv($output, [$no++, $row['nis'], $row['nama_lengkap'], $status]);
    }
    
    fclose($output);
    exit;
}

// Jika formatnya PDF
if ($format === 'pdf') {
    $html = '
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 5px; }
        p { text-align: center; margin-top: 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-center { text-align: center; }
    </style>
    <h2>Daftar Kehadiran Pertemuan ' . $pertemuan . '</h2>
    <p>Kelas: ' . htmlspecialchars($kelas_default) . ' | IPB University</p>
    
    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="20%">NIS / NIM</th>
                <th width="45%">Nama Mahasiswa</th>
                <th width="30%" class="text-center">Status Kehadiran</th>
            </tr>
        </thead>
        <tbody>';
        
    $no = 1;
    foreach ($siswa_list as $row) {
        $status = $row['status'] ?? 'Belum Diabsen';
        $html .= '
            <tr>
                <td class="text-center">' . $no++ . '</td>
                <td>' . htmlspecialchars($row['nis']) . '</td>
                <td>' . htmlspecialchars($row['nama_lengkap']) . '</td>
                <td class="text-center">' . $status . '</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>';

    // Proses render PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Absensi_Pertemuan_" . $pertemuan . ".pdf", ["Attachment" => false]);
    exit;
}
?>