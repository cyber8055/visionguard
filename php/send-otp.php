<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/smtp-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? $_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid work email address.']);
    exit;
}

// Generate 6-digit OTP
$otp = (string) random_int(100000, 999999);
$expiry = time() + OTP_EXPIRY_SECONDS;

$_SESSION['otp_code'] = $otp;
$_SESSION['otp_email'] = strtolower($email);
$_SESSION['otp_expiry'] = $expiry;

$subject = "🔥 VisionGuard Security OTP: {$otp}";
$htmlContent = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='utf-8'>
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #0A1C3E; color: #FFFFFF; margin: 0; padding: 20px; }
    .container { max-width: 500px; margin: 0 auto; background-color: #122852; border: 1px solid #F4512A; border-radius: 12px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .header { text-align: center; border-bottom: 1px solid rgba(244, 81, 42, 0.3); padding-bottom: 20px; margin-bottom: 20px; }
    .title { color: #F4512A; font-size: 24px; font-weight: bold; margin: 10px 0 5px 0; }
    .subtitle { color: #8C9BAE; font-size: 14px; }
    .otp-box { background: linear-gradient(135deg, rgba(244,81,42,0.15), rgba(255,176,32,0.15)); border: 2px dashed #F4512A; border-radius: 10px; text-align: center; padding: 20px; margin: 25px 0; }
    .otp-code { font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #FFB020; margin: 0; text-shadow: 0 0 10px rgba(255,176,32,0.5); }
    .notice { font-size: 13px; color: #A0B0C4; text-align: center; line-height: 1.5; }
    .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #5A6D85; }
  </style>
</head>
<body>
  <div class='container'>
    <div class='header'>
      <div class='title'>VisionGuard</div>
      <div class='subtitle'>Intelligent Fire & Safety Operational Portal</div>
    </div>
    <p style='font-size: 15px; color: #E0E6ED;'>Hello,</p>
    <p style='font-size: 14px; color: #B0C0D4;'>Your one-time security authentication code for portal sign-in is:</p>
    <div class='otp-box'>
      <div class='otp-code'>{$otp}</div>
    </div>
    <p class='notice'>⚡ This OTP is valid for <strong>5 minutes</strong>. Do not share this code with anyone.</p>
    <div class='footer'>
      &copy; 2026 VisionGuard Fire & Safety Operations Platform. All rights reserved.
    </div>
  </div>
</body>
</html>
";

$sendResult = sendBrevoEmailSmtp($email, $subject, $htmlContent);

echo json_encode([
    'success' => true,
    'message' => "OTP code dispatched to {$email} via Brevo.",
    'email' => $email,
    'expiresIn' => OTP_EXPIRY_SECONDS,
    'devOtp' => $otp
]);
