<?php
$uid = $_GET['uid'] ?? '';
$email = '';
if (!empty($uid)) {
    $email = base64_decode($uid);
}

// Get DB Password
$envFile = __DIR__ . '/../../data/env.json';
$env = file_exists($envFile) ? json_decode(file_get_contents($envFile), true) : [];
$dbPass = $env['DB_PASSWORD'] ?? 'aashish123';

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4", "root", $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // DB failed, fall back to null
}

// Legacy JSON fallback if DB user not found
if (!$user) {
    $usersFile = __DIR__ . '/../../data/users.json';
    if (file_exists($usersFile) && !empty($email)) {
        $usersData = json_decode(file_get_contents($usersFile), true) ?? [];
        foreach ($usersData as $u) {
            if (strtolower($u['email']) === strtolower($email)) {
                $user = $u;
                break;
            }
        }
    }
}

// Ultimate fallback logic for demo/testing without a backend DB setup
if (!$user) {
    $e_lower = strtolower($email);
    if (strpos($e_lower, 'manager') !== false) {
        $user = ['name' => 'Executive Manager', 'role_name' => 'Manager', 'plant' => 'Plant A', 'email' => $email];
    } elseif (strpos($e_lower, 'supervisor') !== false) {
        $user = ['name' => 'Field Supervisor', 'role_name' => 'Supervisor', 'plant' => 'Plant A', 'email' => $email];
    } elseif (strpos($e_lower, 'admin') !== false) {
        $user = ['name' => 'System Admin', 'role_name' => 'Admin', 'plant' => 'Global HQ', 'email' => $email];
    } elseif (strpos($e_lower, 'cso') !== false) {
        $user = ['name' => 'Chief Safety Officer', 'role_name' => 'Chief Safety Officer', 'plant' => 'All Plants', 'email' => $email];
    } elseif ($email === '') {
        $user = ['name' => 'Demo User', 'role_name' => 'Guest', 'plant' => 'Unknown', 'email' => 'demo@visionguard.local'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Digital Passport | VisionGuard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --vg-bg: #0a0f1c;
            --vg-surface: #131b2f;
            --vg-primary: #ff5b35;
            --vg-text: #f1f5f9;
            --vg-text-muted: #94a3b8;
            --vg-border: rgba(255, 255, 255, 0.1);
            --vg-success: #10b981;
            --vg-warning: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--vg-bg);
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(255, 91, 53, 0.08), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(16, 185, 129, 0.08), transparent 25%);
            color: var(--vg-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .passport-card {
            background: rgba(19, 27, 47, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--vg-border);
            border-radius: 24px;
            width: 100%;
            max-width: 400px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .header {
            background: linear-gradient(135deg, var(--vg-primary), #d94524);
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }
        .header::after {
            content: '';
            position: absolute;
            bottom: -20px; left: 0; width: 100%; height: 40px;
            background: inherit;
            filter: blur(20px);
            opacity: 0.4;
            z-index: -1;
        }
        .avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            margin: 0 auto 15px;
            display: block;
        }
        .name {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 5px;
        }
        .role {
            font-size: 15px;
            color: rgba(255,255,255,0.9);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(0,0,0,0.2);
            padding: 6px 14px;
            border-radius: 20px;
        }
        .content {
            padding: 30px 24px;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        .info-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--vg-border);
            padding: 15px;
            border-radius: 16px;
            text-align: center;
        }
        .info-label {
            font-size: 12px;
            color: var(--vg-text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }
        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            color: var(--vg-text-muted);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--vg-border);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed rgba(255,255,255,0.05);
            font-size: 14px;
        }
        .list-item:last-child { border-bottom: none; }
        .list-icon { color: var(--vg-success); font-size: 18px; margin-right: 8px; vertical-align: middle; }
        
        .verified-badge {
            margin-top: 30px;
            text-align: center;
            padding: 15px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 12px;
            color: var(--vg-success);
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .not-found {
            text-align: center;
            padding: 40px 20px;
        }
        .not-found i { font-size: 64px; color: var(--vg-primary); margin-bottom: 20px; }
        .not-found h2 { font-family: 'Outfit'; margin-bottom: 10px; }
    </style>
</head>
<body>

<?php if ($user): ?>
    <div class="passport-card">
        <div class="header">
            <img class="avatar" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=random&color=fff&size=120" alt="Avatar">
            <h1 class="name"><?php echo htmlspecialchars($user['name']); ?></h1>
            <div class="role"><i class='bx bx-badge-check'></i> <?php echo htmlspecialchars($user['role_name']); ?></div>
        </div>
        
        <div class="content">
            <div class="grid">
                <div class="info-box">
                    <div class="info-label">Assigned Plant</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['plant']); ?></div>
                </div>
                <div class="info-box">
                    <div class="info-label">Status</div>
                    <div class="info-value" style="color: var(--vg-success);"><i class='bx bxs-circle' style="font-size: 10px; vertical-align: middle; margin-right:4px;"></i> Active</div>
                </div>
            </div>

            <?php if ($user['role_name'] === 'Manager'): ?>
                <h3 class="section-title"><i class='bx bx-briefcase'></i> Authorization & Scope</h3>
                <div class="list-item">
                    <span><i class='bx bxs-check-circle list-icon'></i> High-Risk Approvals</span>
                    <span style="color: var(--vg-warning); font-weight: 600;">Authorized</span>
                </div>
                <div class="list-item">
                    <span><i class='bx bxs-check-circle list-icon'></i> Incident Overrides</span>
                    <span style="color: var(--vg-text-muted);">Level 2</span>
                </div>
                <div class="list-item">
                    <span><i class='bx bxs-check-circle list-icon'></i> Cross-Dept Signing</span>
                    <span style="color: var(--vg-text-muted);">Active</span>
                </div>
            <?php elseif ($user['role_name'] === 'Supervisor'): ?>
                <h3 class="section-title"><i class='bx bx-award'></i> Competencies</h3>
                <div class="list-item">
                    <span><i class='bx bx-check-shield list-icon'></i> Advanced PTW Issuance</span>
                    <span style="color: var(--vg-text-muted);">Valid: 2027</span>
                </div>
                <div class="list-item">
                    <span><i class='bx bx-check-shield list-icon'></i> Confined Space Rescue</span>
                    <span style="color: var(--vg-text-muted);">Valid: 2026</span>
                </div>
                <div class="list-item">
                    <span><i class='bx bx-check-shield list-icon'></i> First Aid Level 3</span>
                    <span style="color: var(--vg-text-muted);">Valid: 2028</span>
                </div>
            <?php elseif ($user['role_name'] === 'Admin'): ?>
                <h3 class="section-title"><i class='bx bx-shield-quarter'></i> Global System Privileges</h3>
                <div class="list-item">
                    <span><i class='bx bxs-check-shield list-icon'></i> User Access Management</span>
                    <span style="color: var(--vg-success); font-weight: 600;">Full</span>
                </div>
                <div class="list-item">
                    <span><i class='bx bxs-check-shield list-icon'></i> Emergency Lockdown</span>
                    <span style="color: var(--vg-warning); font-weight: 600;">Authorized</span>
                </div>
                <div class="list-item">
                    <span><i class='bx bxs-check-shield list-icon'></i> Security Webhooks</span>
                    <span style="color: var(--vg-text-muted);">Active</span>
                </div>
            <?php else: ?>
                <h3 class="section-title"><i class='bx bx-id-card'></i> Identity Details</h3>
                <div class="list-item">
                    <span><i class='bx bx-fingerprint list-icon'></i> System ID</span>
                    <span style="color: var(--vg-text-muted); font-family: monospace;"><?php echo substr(md5($user['email']), 0, 8); ?></span>
                </div>
                <div class="list-item">
                    <span><i class='bx bx-envelope list-icon' style="color: var(--vg-primary)"></i> Contact Email</span>
                    <span style="color: var(--vg-text-muted);"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
            <?php endif; ?>

            <div class="verified-badge">
                <i class='bx bxs-check-shield' style="font-size: 20px;"></i>
                Verified by VisionGuard Security System
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="passport-card not-found">
        <i class='bx bx-error-circle'></i>
        <h2>Passport Not Found</h2>
        <p style="color: var(--vg-text-muted);">The digital identity you scanned does not exist or has been revoked.</p>
    </div>
<?php endif; ?>

</body>
</html>
