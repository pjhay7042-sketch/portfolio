<?php
// ============================================================
//  verify_otp.php  —  Step 2 of 2 in the OTP login flow
//
//  Expected POST body:
//    action=verify  &  otp_code=123456
//
//  On success returns:
//    { success: true, account: { id, first_name, ... } }
//
//  On failure returns:
//    { success: false, message: "..." }
// ============================================================

require_once __DIR__ . '/db.php';

if (empty($_POST)) {
    parse_str(file_get_contents('php://input'), $_POST);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'verify':  verifyOtp($pdo);  break;
    case 'resend':  resendOtp($pdo);  break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown or missing action.']);
}

function respond(bool $success, array $payload = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => $success], $payload));
}

// ------------------------------------------------------------
//  Verify  —  check the submitted code against otp_tokens
// ------------------------------------------------------------
function verifyOtp(PDO $pdo): void
{
    // Guard: a pending_user_id must already be in the session
    $pendingId = $_SESSION['pending_user_id'] ?? null;
    if (!$pendingId) {
        respond(false, ['message' => 'No login in progress. Please start from the login form.'], 403);
        return;
    }

    $submitted = trim($_POST['otp_code'] ?? '');
    if ($submitted === '' || !ctype_digit($submitted) || strlen($submitted) !== 6) {
        respond(false, ['message' => 'Please enter the 6-digit code from your email.'], 422);
        return;
    }

    // Fetch the stored token for this user
    $stmt = $pdo->prepare(
        "SELECT id, otp_code, expires_at
           FROM otp_tokens
          WHERE user_id = ?
          ORDER BY created_at DESC
          LIMIT 1"
    );
    $stmt->execute([$pendingId]);
    $token = $stmt->fetch();

    if (!$token) {
        respond(false, ['message' => 'No OTP found. Please request a new code.'], 404);
        return;
    }

    // Check expiry
    if (new DateTime() > new DateTime($token['expires_at'])) {
        $pdo->prepare("DELETE FROM otp_tokens WHERE user_id = ?")->execute([$pendingId]);
        respond(false, ['message' => 'OTP has expired. Please log in again to receive a new code.'], 401);
        return;
    }

    // Check code match  (constant-time comparison prevents timing attacks)
    if (!hash_equals($token['otp_code'], $submitted)) {
        respond(false, ['message' => 'Incorrect code. Please check your email and try again.'], 401);
        return;
    }

    // ✅ All checks passed
    // Delete the used token (one-time use)
    $pdo->prepare("DELETE FROM otp_tokens WHERE id = ?")->execute([$token['id']]);

    // Fetch account details first so we can promote the session
    // with the role the rest of the site (incl. the IoT Monitor
    // section) depends on for admin-only features.
    $accStmt = $pdo->prepare(
        "SELECT id, first_name, middle_name, last_name, username, email, role
           FROM accounts WHERE id = ?"
    );
    $accStmt->execute([$pendingId]);
    $account = $accStmt->fetch();

    // Promote session to fully authenticated
    $_SESSION['otp_verified']    = true;
    $_SESSION['logged_in_user']  = $pendingId;
    $_SESSION['role']            = $account['role'] ?? 'viewer';
    unset($_SESSION['pending_user_id']);

    respond(true, ['account' => [
        'id'          => (int)$account['id'],
        'first_name'  => $account['first_name'],
        'middle_name' => $account['middle_name'],
        'last_name'   => $account['last_name'],
        'username'    => $account['username'],
        'email'       => $account['email'],
        'role'        => $account['role'],
    ]]);
}

// ------------------------------------------------------------
//  Resend  —  generate and email a fresh OTP
// ------------------------------------------------------------
function resendOtp(PDO $pdo): void
{
    $pendingId = $_SESSION['pending_user_id'] ?? null;
    if (!$pendingId) {
        respond(false, ['message' => 'No login in progress.'], 403);
        return;
    }

    $accStmt = $pdo->prepare(
        "SELECT id, first_name, email FROM accounts WHERE id = ?"
    );
    $accStmt->execute([$pendingId]);
    $account = $accStmt->fetch();

    if (!$account) {
        respond(false, ['message' => 'Account not found.'], 404);
        return;
    }

    require_once __DIR__ . '/send_otp.php';
    $sent = sendOtp($pdo, (int)$account['id'], $account['email'], $account['first_name']);

    if (!$sent['success']) {
        respond(false, ['message' => 'Failed to resend OTP: ' . $sent['message']]);
        return;
    }

    $masked = maskEmail($account['email']);
    respond(true, ['masked_email' => $masked, 'message' => 'A new OTP has been sent to ' . $masked]);
}

// Re-use the same masking helper defined in accounts.php when called standalone
if (!function_exists('maskEmail')) {
    function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, 1);
        $masked  = $visible . str_repeat('*', max(1, strlen($local) - 1));
        return $masked . '@' . $domain;
    }
}