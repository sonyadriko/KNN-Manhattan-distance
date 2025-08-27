<?php
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator("KNN Sistem Rekomendasi Helm")
    ->setLastModifiedBy("KNN System")
    ->setTitle("Template Data Training KNN")
    ->setSubject("Template Excel untuk import data training")
    ->setDescription("Template Excel yang dapat digunakan untuk mengupload data training KNN");

// Define headers
$headers = [
    'A1' => 'No Data',
    'B1' => 'Merk', 
    'C1' => 'Nama',
    'D1' => 'Jenis',
    'E1' => 'Harga',
    'F1' => 'Standar',
    'G1' => 'Kaca',
    'H1' => 'Double Visor',
    'I1' => 'Ventilasi Udara',
    'J1' => 'Berat',
    'K1' => 'Wire Lock',
    'L1' => 'Kelas'
];

// Set headers
foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// Style the header row
$headerRange = 'A1:L1';
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

// Add sample data
$sampleData = [
    [1, 'Honda', 'XR150', 'Full Face', 500000, 'SNI, DOT', 'Ya', 'No', 'Ya', 1.5, 'Ya', 'Murah'],
    [2, 'Arai', 'RX7-V', 'Full Face', 8000000, 'SNI, DOT, ECE, SNELL', 'Ya', 'Ya', 'Ya', 1.6, 'Ya', 'Mahal'],
    [3, 'Shoei', 'NXR2', 'Full Face', 7500000, 'SNI, DOT, ECE', 'Ya', 'No', 'Ya', 1.4, 'Ya', 'Mahal'],
    [4, 'KYT', 'K2 Rider', 'Half Face', 300000, 'SNI', 'No', 'No', 'No', 0.8, 'No', 'Murah'],
    [5, 'AGV', 'K3 SV', 'Full Face', 3500000, 'SNI, DOT, ECE', 'Ya', 'No', 'Ya', 1.5, 'Ya', 'Mahal']
];

$row = 2;
foreach ($sampleData as $data) {
    $col = 'A';
    foreach ($data as $value) {
        $sheet->setCellValue($col . $row, $value);
        $col++;
    }
    $row++;
}

// Style the data rows
$sheet->getStyle('A2:L6')->applyFromArray([
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

// Auto-size columns
foreach (range('A', 'L') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Add instructions below the data
$sheet->setCellValue('A8', 'PETUNJUK PENGGUNAAN:');
$sheet->getStyle('A8')->getFont()->setBold(true);

$instructions = [
    'A9' => '1. Hapus baris contoh data (baris 2-6) sebelum mengisi data Anda',
    'A10' => '2. Isi data mulai dari baris 2, jangan mengubah header di baris 1',
    'A11' => '3. Kolom yang wajib diisi: No Data, Merk, Nama, Kelas',
    'A12' => '4. Format Kaca/Double Visor/Ventilasi/Wire Lock: Ya atau No',
    'A13' => '5. Format Kelas: Mahal atau Murah',
    'A14' => '6. Format Harga: angka tanpa titik/koma (contoh: 500000)',
    'A15' => '7. Format Berat: desimal dengan titik (contoh: 1.5)',
    'A16' => '8. Simpan file dalam format .xlsx sebelum upload'
];

foreach ($instructions as $cell => $instruction) {
    $sheet->setCellValue($cell, $instruction);
}

// Style instructions
$sheet->getStyle('A9:A16')->applyFromArray([
    'font' => ['size' => 10],
    'alignment' => ['wrapText' => true]
]);

// Set column width for instructions
$sheet->getColumnDimension('A')->setWidth(80);

// Set filename and headers for download
$filename = 'Template_Data_Training_KNN_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

// Save file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;