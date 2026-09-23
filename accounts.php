<?php
// ============================================================
//  accounts.php  —  CRUD + login for accounts table
//  OTP change: login now only verifies password and stores
//  pending_user_id in session. Full access is granted only
//  after verify_otp.php confirms the emailed code.
// ============================================================

require_once __DIR__ . '/db.php';

if (empty($_POST)) {
    parse_str(file_get_contents('php://input'), $_POST);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':        listAccounts($pdo);    break;
    case 'create':      createAccount($pdo);   break;
    case 'update':       updateAccount($pdo);   break;
    case 'delete':       deleteAccount($pdo);    break;
    case 'login':        loginAccount($pdo);    break;
    case 'change_role':  changeRole($pdo);       break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown or missing action.']);
}

// ------------------------------------------------------------
//  Helpers
// ------------------------------------------------------------

function respond(bool $success, array $payload = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => $success], $payload));
}

function requirePost(string ...$fields): array
{
    $out = [];
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        if ($val === '') {
            respond(false, ['message' => "Field '$f' is required."], 422);
            exit;
        }
        $out[$f] = $val;
    }
    return $out;
}

// ------------------------------------------------------------
//  List
// ------------------------------------------------------------

function listAccounts(PDO $pdo): void
{
    requireLogin();
    $rows = $pdo->query(
        "SELECT id, first_name, middle_name, last_name, username, email, role, created_at
           FROM accounts
          ORDER BY id ASC"
    )->fetchAll();
    respond(true, ['accounts' => $rows]);
}

// ------------------------------------------------------------
//  Create  (now includes email)
// ------------------------------------------------------------

function createAccount(PDO $pdo): void
{
    $d          = requirePost('first_name', 'last_name', 'username', 'email', 'password');
    $middleName = trim($_POST['middle_name'] ?? '');

    foreach (['First name' => $d['first_name'], 'Middle name' => $middleName,
              'Surname' => $d['last_name']] as $label => $val) {
        if (strlen($val) > 50) {
            respond(false, ['message' => "$label must be 50 characters or fewer."], 422);
            return;
        }
    }
    if (strlen($d['username']) > 50) {
        respond(false, ['message' => 'Username must be 50 characters or fewer.'], 422);
        return;
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        respond(false, ['message' => 'Please enter a valid email address.'], 422);
        return;
    }

    $check = $pdo->prepare("SELECT id FROM accounts WHERE LOWER(username) = LOWER(?)");
    $check->execute([$d['username']]);
    if ($check->fetch()) {
        respond(false, ['message' => 'Username already taken.'], 409);
        return;
    }

    $hash = password_hash($d['password'], PASSWORD_DEFAULT);

    // Bootstrap rule: if this is the very first account on a fresh
    // install, there's no admin yet to promote anyone — so the first
    // account in becomes admin automatically. Every account after
    // that defaults to 'viewer' and must be promoted by an admin
    // from the Manage Accounts page.
    $isFirstAccount = (int) $pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn() === 0;
    $role = $isFirstAccount ? 'admin' : 'viewer';

    $stmt = $pdo->prepare(
        "INSERT INTO accounts (first_name, middle_name, last_name, username, email, password, role)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$d['first_name'], $middleName, $d['last_name'],
                    $d['username'], $d['email'], $hash, $role]);

    $newId = (int)$pdo->lastInsertId();

    // Store pending state — same two-step OTP flow as login
    $_SESSION['pending_user_id'] = $newId;
    unset($_SESSION['otp_verified']);

    // Send OTP to the newly registered email
    require_once __DIR__ . '/send_otp.php';
    $sent = sendOtp($pdo, $newId, $d['email'], $d['first_name']);

    if (!$sent['success']) {
        respond(false, ['message' => 'Account created but OTP email failed: ' . $sent['message']], 500);
        return;
    }

    $masked = maskEmail($d['email']);
    respond(true, [
        'otp_pending'  => true,
        'masked_email' => $masked,
    ]);
}

// ------------------------------------------------------------
//  Update  (now includes email)
// ------------------------------------------------------------

function updateAccount(PDO $pdo): void
{
    $id         = (int)($_POST['id'] ?? 0);
    $firstName  = trim($_POST['first_name']  ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName   = trim($_POST['last_name']   ?? '');
    $username   = trim($_POST['username']    ?? '');
    $email      = trim($_POST['email']       ?? '');
    $password   = $_POST['password']          ?? '';

    if ($id <= 0 || $firstName === '' || $lastName === '' || $username === '') {
        respond(false, ['message' => 'Account id, first name, surname, and username are required.'], 422);
        return;
    }
    foreach (['First name' => $firstName, 'Middle name' => $middleName,
              'Surname' => $lastName, 'Username' => $username] as $label => $val) {
        if (strlen($val) > 50) {
            respond(false, ['message' => "$label must be 50 characters or fewer."], 422);
            return;
        }
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, ['message' => 'Please enter a valid email address.'], 422);
        return;
    }

    $check = $pdo->prepare("SELECT id FROM accounts WHERE LOWER(username) = LOWER(?) AND id != ?");
    $check->execute([$username, $id]);
    if ($check->fetch()) {
        respond(false, ['message' => 'Username already taken.'], 409);
        return;
    }

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare(
            "UPDATE accounts
                SET first_name = ?, middle_name = ?, last_name = ?,
                    username = ?, email = ?, password = ?
              WHERE id = ?"
        )->execute([$firstName, $middleName, $lastName, $username, $email, $hash, $id]);
    } else {
        $pdo->prepare(
            "UPDATE accounts
                SET first_name = ?, middle_name = ?, last_name = ?,
                    username = ?, email = ?
              WHERE id = ?"
        )->execute([$firstName, $middleName, $lastName, $username, $email, $id]);
    }

    respond(true, ['account' => [
        'id'          => $id,
        'first_name'  => $firstName,
        'middle_name' => $middleName,
        'last_name'   => $lastName,
        'username'    => $username,
        'email'       => $email,
    ]]);
}

// ------------------------------------------------------------
//  Delete  (admin only — same safety rules as the old IoT
//  Monitor's delete_user.php: can't delete yourself, can't
//  delete the last remaining admin)
// ------------------------------------------------------------

function deleteAccount(PDO $pdo): void
{
    requireAdmin();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        respond(false, ['message' => 'Account id is required.'], 422);
        return;
    }
    if ($id === (int)$_SESSION['logged_in_user']) {
        respond(false, ['message' => 'You cannot delete your own account while logged in.'], 403);
        return;
    }

    $lookup = $pdo->prepare("SELECT role FROM accounts WHERE id = ?");
    $lookup->execute([$id]);
    $target = $lookup->fetch();

    if ($target && $target['role'] === 'admin') {
        $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM accounts WHERE role = 'admin'")->fetchColumn();
        if ($adminCount <= 1) {
            respond(false, ['message' => 'Cannot delete the last remaining admin.'], 403);
            return;
        }
    }

    $pdo->prepare("DELETE FROM accounts WHERE id = ?")->execute([$id]);
    respond(true);
}

// ------------------------------------------------------------
//  Change Role  (admin only — promote/demote a viewer/admin)
//  Mirrors the protections from the old IoT Monitor's
//  users.php "change_role" action: can't demote the last admin,
//  can't remove your own admin role.
// ------------------------------------------------------------

function changeRole(PDO $pdo): void
{
    requireAdmin();

    $targetId = (int)($_POST['id'] ?? 0);
    $newRole  = trim($_POST['role'] ?? '');

    if ($targetId <= 0 || !in_array($newRole, ['admin', 'viewer'], true)) {
        respond(false, ['message' => 'Invalid request.'], 422);
        return;
    }
    if ($targetId === (int)$_SESSION['logged_in_user'] && $newRole !== 'admin') {
        respond(false, ['message' => 'You cannot remove your own admin role.'], 403);
        return;
    }

    $lookup = $pdo->prepare("SELECT role FROM accounts WHERE id = ?");
    $lookup->execute([$targetId]);
    $target = $lookup->fetch();

    if (!$target) {
        respond(false, ['message' => 'Account not found.'], 404);
        return;
    }

    if ($target['role'] === 'admin' && $newRole === 'viewer') {
        $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM accounts WHERE role = 'admin'")->fetchColumn();
        if ($adminCount <= 1) {
            respond(false, ['message' => 'Cannot demote the last remaining admin.'], 403);
            return;
        }
    }

    $pdo->prepare("UPDATE accounts SET role = ? WHERE id = ?")->execute([$newRole, $targetId]);
    respond(true, ['id' => $targetId, 'role' => $newRole]);
}

// ------------------------------------------------------------
//  Login  —  OTP-aware
//  Step 1 of 2: verify password, then trigger OTP email.
//  Returns { success, otp_pending: true } so the front-end
//  knows to show the OTP panel instead of unlocking the site.
// ------------------------------------------------------------

function loginAccount(PDO $pdo): void
{
    $d = requirePost('username', 'password');

    $stmt = $pdo->prepare(
        "SELECT id, first_name, middle_name, last_name, username, email, password
           FROM accounts WHERE LOWER(username) = LOWER(?)"
    );
    $stmt->execute([$d['username']]);
    $account = $stmt->fetch();

    if (!$account || !password_verify($d['password'], $account['password'])) {
        respond(false, ['message' => 'Invalid username or password.'], 401);
        return;
    }

    // Password is correct — store pending state in session
    $_SESSION['pending_user_id'] = (int)$account['id'];
    unset($_SESSION['otp_verified']);

    // Trigger OTP generation and email via verify_otp.php helper
    // (we call the shared helper directly here so accounts.php stays thin)
    require_once __DIR__ . '/send_otp.php';
    $sent = sendOtp($pdo, (int)$account['id'], $account['email'], $account['first_name']);

    if (!$sent['success']) {
        respond(false, ['message' => 'Password accepted but OTP email failed: ' . $sent['message']], 500);
        return;
    }

    // Return masked email so the front-end can display "Code sent to g***@gmail.com"
    $masked = maskEmail($account['email']);
    respond(true, [
        'otp_pending'   => true,
        'masked_email'  => $masked,
    ]);
}

// Mask email: gaylord@gmail.com → g*****@gmail.com
function maskEmail(string $email): string
{
    [$local, $domain] = explode('@', $email, 2);
    $visible = substr($local, 0, 1);
    $masked  = $visible . str_repeat('*', max(1, strlen($local) - 1));
    return $masked . '@' . $domain;
}