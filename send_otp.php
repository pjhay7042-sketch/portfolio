<?php
// ============================================================
//  send_otp.php  —  OTP generator + PHPMailer dispatcher
//
//  HOW TO SET UP PHPMAILER:
//  1. Go to https://github.com/PHPMailer/PHPMailer
//  2. Click Code → Download ZIP
//  3. Extract the ZIP; copy the src/ folder into your project
//     so you have:  API/phpmailer/src/
//  4. Fill in YOUR_GMAIL and YOUR_APP_PASSWORD below.
//
//  HOW TO GET A GMAIL APP PASSWORD:
//  1. Go to myaccount.google.com → Security
//  2. Enable 2-Step Verification (if not already on)
//  3. Search "App Passwords" → choose Mail + Windows Computer
//  4. Copy the 16-character password and paste it below.
//
//  NEVER commit your real App Password to a public repo.
//  Replace it with YOUR_APP_PASSWORD before submitting files.
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

// ── ⚠ CONFIGURE THESE TWO VALUES ──────────────────────────
define('MAILER_FROM',  'gaylord.pascual@isu.edu.ph');   // your Gmail address
define('MAILER_PASS',  'ajyj vqze fhnn ibpm');       // 16-char Gmail App Password
// ───────────────────────────────────────────────────────────

define('OTP_EXPIRY_MINUTES', 10);

/**
 * Generate a cryptographically secure 6-digit OTP,
 * store it in otp_tokens, and email it to the user.
 *
 * @return array ['success' => bool, 'message' => string]
 */
function sendOtp(PDO $pdo, int $userId, string $toEmail, string $firstName): array
{
    // 1. Generate secure 6-digit code (random_int keeps leading zeros safe)
    $otpCode  = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

    // 2. Delete any existing unused OTPs for this user (one active at a time)
    $pdo->prepare("DELETE FROM otp_tokens WHERE user_id = ?")->execute([$userId]);

    // 3. Store new OTP
    $pdo->prepare(
        "INSERT INTO otp_tokens (user_id, otp_code, expires_at) VALUES (?, ?, ?)"
    )->execute([$userId, $otpCode, $expiresAt]);

    // 4. Send email via PHPMailer
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAILER_FROM;
        $mail->Password   = MAILER_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom(MAILER_FROM, 'Gaylord Pascual Biodata — Secure Access');
        $mail->addAddress($toEmail, $firstName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Login OTP Code';
        $mail->Body    = buildEmailHtml($firstName, $otpCode);
        $mail->AltBody = buildEmailText($firstName, $otpCode);

        $mail->send();
        return ['success' => true, 'message' => 'OTP sent successfully.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}

// ── Email template helpers ───────────────────────────────────

function buildEmailHtml(string $name, string $code): string
{
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { margin:0; padding:0; background:#050a0f; font-family:'Segoe UI',sans-serif; }
    .wrap { max-width:460px; margin:40px auto; background:#0f1f30;
            border:1px solid #1a3a50; border-radius:6px; overflow:hidden; }
    .topbar { height:4px; background:linear-gradient(90deg,#00e5ff,#00ff88); }
    .body { padding:36px 40px; }
    h1 { color:#fff; font-size:1.1rem; letter-spacing:.08em;
         text-transform:uppercase; margin:0 0 6px; }
    .sub { color:#00ff88; font-size:11px; letter-spacing:.1em;
           font-family:monospace; margin-bottom:28px; }
    p  { color:#6a9ab0; font-size:14px; line-height:1.7; margin:0 0 20px; }
    .code-box { background:#0b1520; border:1px solid #00e5ff44;
                border-radius:4px; text-align:center; padding:24px 0; margin:20px 0; }
    .code { font-family:monospace; font-size:2.4rem; letter-spacing:.35em;
            color:#00e5ff; font-weight:700; }
    .expire { color:#6a9ab0; font-size:11px; font-family:monospace;
              letter-spacing:.06em; margin-top:8px; }
    .footer { padding:16px 40px; border-top:1px solid #1a3a50;
              font-size:10px; color:#1a3a50; font-family:monospace;
              letter-spacing:.04em; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="topbar"></div>
  <div class="body">
    <h1>Gaylord A. Pascual — Personal Biodata</h1>
    <div class="sub">// SECURE ACCESS — ONE-TIME PASSWORD</div>
    <p>Hello, <strong style="color:#d0e8f5">{$name}</strong>.</p>
    <p>Your login OTP code is below. Enter it on the verification screen within
       <strong style="color:#ffb300">10 minutes</strong>. Do not share this code with anyone.</p>
    <div class="code-box">
      <div class="code">{$code}</div>
      <div class="expire">⏱ Expires in 10 minutes</div>
    </div>
    <p style="font-size:12px;">If you did not attempt to log in, you can safely ignore this email.
       No action is required.</p>
  </div>
  <div class="footer">ISU Echague · BSIT 2-2 NETSEC · Midyear 2026 · This is an automated message.</div>
</div>
</body>
</html>
HTML;
}

function buildEmailText(string $name, string $code): string
{
    return "Hello {$name},\n\nYour OTP login code is: {$code}\n\n"
         . "It expires in 10 minutes. Do not share it with anyone.\n\n"
         . "If you did not request this, ignore this email.\n\n"
         . "— Gaylord Biodata System · ISU Echague";
}