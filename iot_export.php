<?php
// ============================================================
//  iot_export.php  —  Export sensor data to CSV
//  Adapted from the standalone IoT Monitor's export_csv.php.
//  Login required (any role), via the merged session system.
// ============================================================

require_once __DIR__ . '/db.php';
requireLogin();

// This endpoint sends a CSV file, not JSON — override the
// default JSON content-type that db.php sets for every other
// endpoint in the merged API.
header_remove('Content-Type');
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=sensor_data.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Temperature', 'Humidity', 'Date']);

$stmt = $pdo->query("SELECT id, temperature, humidity, created_at FROM sensor_data ORDER BY id DESC");
foreach ($stmt as $row) {
    fputcsv($output, $row);
}

fclose($output);