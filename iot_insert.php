<?php
// ============================================================
//  iot_insert.php  —  Receive sensor data from the Python bridge
//  Called via HTTP POST by sensor_sender.py, which reads serial
//  data from the Arduino. No user session involved here — the
//  sensor doesn't log in, it just posts readings — so this file
//  uses its own lightweight PDO connection instead of requiring
//  db.php's session/login machinery.
// ============================================================

header('Content-Type: text/plain');

require_once __DIR__ . '/iot_config.php';

// Same env-var-with-fallback pattern as db.php — see that file
// for why. This file intentionally has its OWN connection (no
// session/login needed for the sensor), so it needs its own copy.
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'biodataiot_db');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Database connection failed';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

if (!isset($_POST['temperature']) || !isset($_POST['humidity'])) {
    http_response_code(400);
    echo 'Missing parameters';
    exit;
}

$temperature = (float) $_POST['temperature'];
$humidity    = (float) $_POST['humidity'];

if ($temperature < -40 || $temperature > 80) {
    http_response_code(422);
    echo 'Temperature out of range';
    exit;
}
if ($humidity < 0 || $humidity > 100) {
    http_response_code(422);
    echo 'Humidity out of range';
    exit;
}

// The Arduino decides the alert state itself (so the red LED/buzzer react
// even if this server is unreachable) and sends it as 'alert' = "1" or "0".
// If it's missing — e.g. an older sensor_sender.py/firmware pair — fall
// back to computing it here from the same shared threshold.
if (isset($_POST['alert']) && ($_POST['alert'] === '0' || $_POST['alert'] === '1')) {
    $alert = (int) $_POST['alert'];
} else {
    $alert = ($temperature >= HIGH_TEMP_THRESHOLD_C) ? 1 : 0;
}

$stmt = $pdo->prepare("INSERT INTO sensor_data (temperature, humidity, alert) VALUES (?, ?, ?)");

if ($stmt->execute([$temperature, $humidity, $alert])) {
    echo 'OK';
} else {
    http_response_code(500);
    echo 'Insert failed';
}