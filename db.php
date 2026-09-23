<?php
// ============================================================
//  db.php  —  Database connection + session bootstrap
//  Shared by every API file in the merged site (biodataiot).
//  One database: biodataiot_db. One driver: PDO.
// ============================================================

session_start();   // needed for login state + OTP pending-state tracking

// ------------------------------------------------------------
//  DB credentials — works on BOTH XAMPP (local) and Railway
//  (deployed). Railway's MySQL plugin injects MYSQLHOST,
//  MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, MYSQLPORT as
//  environment variables automatically — no code change needed
//  when you deploy, as long as you attach the MySQL plugin.
//  If those env vars aren't set (i.e. you're on XAMPP), it
//  falls back to the old localhost/root/blank defaults.
// ------------------------------------------------------------
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'biodataiot_db');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Did you run schema_merged.sql in phpMyAdmin? (' . $e->getMessage() . ')'
    ]);
    exit;
}

// ------------------------------------------------------------
//  requireLogin() — shared guard for any endpoint that needs
//  a fully authenticated (OTP-verified) session.
//  Use at the top of an endpoint: requireLogin();
// ------------------------------------------------------------
function requireLogin(): void
{
    if (empty($_SESSION['otp_verified']) || empty($_SESSION['logged_in_user'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please log in to continue.']);
        exit;
    }
}

// ------------------------------------------------------------
//  requireAdmin() — shared guard for admin-only endpoints
//  (e.g. changing roles, deleting users).
// ------------------------------------------------------------
function requireAdmin(): void
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admins only.']);
        exit;
    }
}