<?php
// ============================================================
//  iot_data.php  —  IoT Monitor dashboard data, as JSON
//  Adapted from the standalone IoT Monitor's dashboard.php.
//  That version server-rendered an HTML page; this version
//  returns JSON so the merged single-page site can render the
//  "IoT Monitor" nav section the same way it renders Skills,
//  Accounts, etc. — via fetch() + JS templating.
//
//  Login required (any role). Date-range filter optional via
//  GET ?from=YYYY-MM-DD&to=YYYY-MM-DD, same as the original.
// ============================================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/iot_config.php';
requireLogin();

$from = $_GET['from'] ?? '';
$to   = $_GET['to']   ?? '';

$where  = '';
$params = [];

if ($from !== '' && $to !== '') {
    $where  = 'WHERE created_at BETWEEN ? AND ?';
    $params = [$from . ' 00:00:00', $to . ' 23:59:59'];
} elseif ($from !== '') {
    $where  = 'WHERE created_at >= ?';
    $params = [$from . ' 00:00:00'];
} elseif ($to !== '') {
    $where  = 'WHERE created_at <= ?';
    $params = [$to . ' 23:59:59'];
}

// Latest 20 rows within filter, newest first
$stmt = $pdo->prepare("SELECT * FROM sensor_data $where ORDER BY id DESC LIMIT 20");
$stmt->execute($params);
$rows = $stmt->fetchAll();
foreach ($rows as &$row) {
    $row['alert'] = (int) $row['alert'];
}
unset($row);

// Latest single reading — always unfiltered, shows current value
$latest = $pdo->query("SELECT * FROM sensor_data ORDER BY id DESC LIMIT 1")->fetch();
if ($latest) {
    $latest['alert'] = (int) $latest['alert'];
}
$currentAlert = $latest ? ($latest['alert'] === 1) : false;

// Sensor status: Online if a reading arrived within the last 15s
// (Arduino/Python send every 5s)
$status = 'Offline';
if ($latest) {
    $lastTime = strtotime($latest['created_at']);
    if ((time() - $lastTime) <= 15) {
        $status = 'Online';
    }
}

// Chart data: oldest -> newest for a left-to-right timeline
$chartRows = array_reverse($rows);
$labels = [];
$temp   = [];
$hum    = [];
$alerts = [];
foreach ($chartRows as $r) {
    $labels[] = $r['created_at'];
    $temp[]   = (float) $r['temperature'];
    $hum[]    = (float) $r['humidity'];
    $alerts[] = (int) $r['alert'];
}

// Summary stats (min / avg / max) over the same filtered range
$statsSql = "SELECT
                MIN(temperature) AS temp_min,
                MAX(temperature) AS temp_max,
                AVG(temperature) AS temp_avg,
                MIN(humidity)    AS hum_min,
                MAX(humidity)    AS hum_max,
                AVG(humidity)    AS hum_avg,
                SUM(alert)       AS alert_count,
                COUNT(*)         AS reading_count
             FROM sensor_data $where";
$statsStmt = $pdo->prepare($statsSql);
$statsStmt->execute($params);
$stats = $statsStmt->fetch();

// Daily averages — long-range trend instead of just last 20 readings
$dailySql = "SELECT
                DATE(created_at) AS day,
                AVG(temperature) AS avg_temp,
                AVG(humidity)    AS avg_hum
             FROM sensor_data $where
             GROUP BY DATE(created_at)
             ORDER BY day ASC";
$dailyStmt = $pdo->prepare($dailySql);
$dailyStmt->execute($params);
$dailyRows = $dailyStmt->fetchAll();

$dailyLabels  = [];
$dailyAvgTemp = [];
$dailyAvgHum  = [];
foreach ($dailyRows as $d) {
    $dailyLabels[]  = $d['day'];
    $dailyAvgTemp[] = round((float) $d['avg_temp'], 1);
    $dailyAvgHum[]  = round((float) $d['avg_hum'], 1);
}

echo json_encode([
    'success'        => true,
    'status'         => $status,
    'latest'         => $latest ?: null,
    'rows'           => $rows,
    'threshold_temp' => (float) HIGH_TEMP_THRESHOLD_C,
    'current_alert'  => $currentAlert,
    'stats'   => [
        'temp_min'      => $stats['temp_min']      !== null ? round((float)$stats['temp_min'], 1)      : null,
        'temp_max'      => $stats['temp_max']      !== null ? round((float)$stats['temp_max'], 1)      : null,
        'temp_avg'      => $stats['temp_avg']      !== null ? round((float)$stats['temp_avg'], 1)      : null,
        'hum_min'       => $stats['hum_min']       !== null ? round((float)$stats['hum_min'], 1)       : null,
        'hum_max'       => $stats['hum_max']       !== null ? round((float)$stats['hum_max'], 1)       : null,
        'hum_avg'       => $stats['hum_avg']       !== null ? round((float)$stats['hum_avg'], 1)       : null,
        'alert_count'   => (int) ($stats['alert_count'] ?? 0),
        'reading_count' => (int) ($stats['reading_count'] ?? 0),
    ],
    'chart' => [
        'labels' => $labels,
        'temp'   => $temp,
        'hum'    => $hum,
        'alert'  => $alerts,
    ],
    'daily' => [
        'labels'   => $dailyLabels,
        'avg_temp' => $dailyAvgTemp,
        'avg_hum'  => $dailyAvgHum,
    ],
]);