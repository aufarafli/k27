<?php
require_once '../../config/database.php';
require_once '../../vendor/autoload.php'; // Pastikan DOMPDF sudah terinstal

use Dompdf\Dompdf;

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$pertemuan_ke = isset($_GET['pertemuan']) ? (int)$_GET['pertemuan'] : 1;
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

// Ambil info pertemuan (tanggal dan metode)
$stmt_info = $conn->prepare("SELECT tanggal, metode FROM absensi_pertemuan WHERE pertemuan_ke = :pertemuan_ke LIMIT 1");
$stmt_info->execute(['pertemuan_ke' => $pertemuan_ke]);
$info = $stmt_info->fetch(PDO::FETCH_ASSOC);

$tanggal_pertemuan = $info && $info['tanggal'] ? date('d F Y', strtotime($info['tanggal'])) : '-';
$metode_pertemuan = $info && $info['metode'] ? $info['metode'] : '-';

// Ambil data absensi mahasiswa
$sql = "SELECT s.nis, s.nama_lengkap, ap.status 
        FROM absensi_pertemuan ap
        JOIN siswa s ON ap.siswa_id = s.id
        WHERE ap.pertemuan_ke = :pertemuan_ke
        ORDER BY s.nama_lengkap ASC";
$stmt = $conn->prepare($sql);
$stmt->execute(['pertemuan_ke' => $pertemuan_ke]);
$data_absensi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- PROSES EXPORT EXCEL (CSV) ---
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Absensi_Pertemuan_' . $pertemuan_ke . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<table border="1">';
    echo '<thead>';
    echo '<tr><th colspan="3" style="text-align:center; font-weight:bold;">Daftar Kehadiran Pertemuan ' . $pertemuan_ke . '</th></tr>';
    echo '<tr><th colspan="3" style="text-align:center;">Tanggal: ' . $tanggal_pertemuan . ' | Metode: ' . $metode_pertemuan . '</th></tr>';
    echo '<tr style="background-color:#f2f2f2;"><th>NIS/NIM</th><th>Nama Mahasiswa</th><th>Status Kehadiran</th></tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($data_absensi as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['nis']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>';
        echo '<td>' . htmlspecialchars($row['status']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    exit();
}

// --- PROSES EXPORT PDF ---
if ($format === 'pdf') {
    // PERBAIKAN: Menambahkan desain dan memunculkan variabel tanggal & metode
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 20px; }
            .header h1 { margin: 0; font-size: 18px; }
            .header p { margin: 5px 0; color: #555; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
            .status-hadir { color: green; font-weight: bold; }
            .status-alpha { color: red; font-weight: bold; }
            .status-izin { color: orange; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Daftar Kehadiran Pertemuan ' . $pertemuan_ke . '</h1>
            <p><strong>Tanggal:</strong> ' . $tanggal_pertemuan . ' | <strong>Metode:</strong> ' . $metode_pertemuan . '</p>
        </div>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="20%">NIS / NIM</th>
                    <th width="45%">Nama Mahasiswa</th>
                    <th width="30%">Status Kehadiran</th>
                </tr>
            </thead>
            <tbody>';

    $no = 1;
    foreach ($data_absensi as $row) {
        $status_class = '';
        if ($row['status'] == 'Hadir') $status_class = 'status-hadir';
        if ($row['status'] == 'Alpha') $status_class = 'status-alpha';
        if ($row['status'] == 'Izin/Sakit') $status_class = 'status-izin';

        $html .= '
                <tr>
                    <td style="text-align:center;">' . $no++ . '</td>
                    <td>' . htmlspecialchars($row['nis']) . '</td>
                    <td>' . htmlspecialchars($row['nama_lengkap']) . '</td>
                    <td class="' . $status_class . '" style="text-align:center;">' . htmlspecialchars($row['status']) . '</td>
                </tr>';
    }

    $html .= '
            </tbody>
        </table>
    </body>
    </html>';

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $filename = "Absensi_Pertemuan_" . $pertemuan_ke . ".pdf";
    $dompdf->stream($filename, array("Attachment" => false));
    exit();
}
?>