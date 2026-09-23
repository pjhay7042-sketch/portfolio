<?php
// ============================================================
//  logout.php  —  Server-side session teardown
//
//  Called by the front-end via fetch() when the user clicks
//  "⏏ Logout". Destroys the PHP session so logged_in_user,
//  otp_verified, role, and pending_user_id are all cleared on
//  the server. No redirect here — this is a single-page site,
//  the front-end (handleLogout in script.js) handles showing
//  the login gate again.
//
//  Returns: { success: true }
// ============================================================

session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

echo json_encode(['success' => true]);