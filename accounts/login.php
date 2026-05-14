<?php
session_start();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/audit.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Get user
    $stmt = $pdo->prepare("SELECT * FROM sdopang1_user WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify
    if ($user && ($user['status'] ?? 'active') === 'inactive') {
        $error = "Your account is inactive";
        audit_log($pdo, $user['user_id'] ?? null, trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')), 'FAILED_LOGIN', 'Authentication', $email, 'Inactive account login attempt.');
    } elseif ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['first_name'];
        $_SESSION['fullname'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['can_validate_201'] = (int) ($user['can_validate_201'] ?? 0);
        $_SESSION['can_validate_opcrf'] = (int) ($user['can_validate_opcrf'] ?? 0);
        $_SESSION['can_validate_ipcrf'] = (int) ($user['can_validate_ipcrf'] ?? 0);
        $_SESSION['can_validate_leave'] = (int) ($user['can_validate_leave'] ?? 0);

        // Regenerate after successful login to prevent session fixation.
        session_regenerate_id(true);

        audit_log($pdo, $user['user_id'], $_SESSION['fullname'], 'LOGIN', 'Authentication', $user['user_id'], 'User signed in successfully.');

        header("Location: /PRIMEHR/accounts/index.php");
        exit;
    } else {
        $error = "Invalid email or password";
        audit_log($pdo, null, $email !== '' ? $email : 'Guest', 'FAILED_LOGIN', 'Authentication', $email, 'Failed login attempt.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | PRIMEHR</title>
    <link rel="icon" type="image/png" href="../assets_pang1/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/custom-ui.css">
    <style>
        :root {
            --login-primary: #155eef;
            --login-primary-dark: #1849a9;
            --login-border: #d0d5dd;
            --login-text: #101828;
            --login-muted: #667085;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: Inter, Arial, sans-serif;
            color: var(--login-text);
            background:
                linear-gradient(110deg, rgba(21, 94, 239, 0.92), rgba(24, 73, 169, 0.86)),
                url("../assets_pang1/SDO1 Pang building.png") center/cover no-repeat;
        }

        .login-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 18px;
        }

        .login-panel {
            width: min(100%, 1040px);
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            overflow: hidden;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.97);
            box-shadow: 0 24px 70px rgba(16, 24, 40, 0.28);
        }

        .login-brand {
            position: relative;
            min-height: 560px;
            padding: 42px;
            color: #ffffff;
            background:
                linear-gradient(180deg, rgba(16, 72, 184, 0.78), rgba(16, 24, 40, 0.86)),
                url("../assets_pang1/SDO1 Pang building - Copy.png") center/cover no-repeat;
        }

        .login-brand-content {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .login-logo-row {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .login-logo {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            object-fit: cover;
            background: #ffffff;
            padding: 5px;
            box-shadow: 0 12px 28px rgba(16, 24, 40, 0.28);
        }

        .login-agency {
            margin: 0;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .login-division {
            margin: 2px 0 0;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.84);
        }

        .login-brand-main {
            margin-top: auto;
            padding-bottom: 12px;
        }

        .login-brand-main h1 {
            margin: 0 0 12px;
            color: #ffffff;
            font-size: 42px;
            line-height: 1.08;
            font-weight: 800;
            letter-spacing: 0;
        }

        .login-brand-main p {
            max-width: 460px;
            margin: 0;
            font-size: 15px;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.9);
        }

        .login-form-wrap {
            display: flex;
            align-items: center;
            padding: 48px;
            background: #ffffff;
        }

        .login-form-card {
            width: 100%;
        }

        .login-eyebrow {
            margin: 0 0 8px;
            color: var(--login-primary);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .login-title {
            margin: 0 0 8px;
            color: var(--login-text);
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .login-subtitle {
            margin: 0 0 28px;
            color: var(--login-muted);
            line-height: 1.55;
        }

        .login-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid #fecdca;
            border-radius: 10px;
            background: #fffbfa;
            color: #b42318;
            font-size: 14px;
            font-weight: 700;
        }

        .login-field {
            margin-bottom: 18px;
        }

        .login-label {
            display: block;
            margin-bottom: 8px;
            color: #344054;
            font-size: 14px;
            font-weight: 700;
        }

        .login-input-wrap {
            position: relative;
        }

        .login-input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #98a2b3;
            font-size: 18px;
        }

        .login-input {
            width: 100%;
            min-height: 48px;
            padding: 11px 14px 11px 44px;
            border: 1px solid var(--login-border);
            border-radius: 10px;
            background: #ffffff;
            color: var(--login-text);
            font-size: 15px;
            outline: none;
            transition: border-color 0.16s ease, box-shadow 0.16s ease;
        }

        .login-input:focus {
            border-color: var(--login-primary);
            box-shadow: 0 0 0 4px rgba(21, 94, 239, 0.12);
        }

        .login-button {
            width: 100%;
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 10px;
            background: var(--login-primary);
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: background 0.16s ease, transform 0.16s ease, box-shadow 0.16s ease;
            box-shadow: 0 12px 24px rgba(21, 94, 239, 0.25);
        }

        .login-button:hover {
            background: var(--login-primary-dark);
            transform: translateY(-1px);
        }

        .login-footer-note {
            margin: 22px 0 0;
            color: var(--login-muted);
            font-size: 12px;
            line-height: 1.5;
            text-align: center;
        }

        @media (max-width: 860px) {
            .login-panel {
                grid-template-columns: 1fr;
                max-width: 520px;
            }

            .login-brand {
                min-height: 260px;
                padding: 28px;
            }

            .login-brand-main h1 {
                font-size: 30px;
            }

            .login-form-wrap {
                padding: 32px 24px;
            }
        }

        @media (max-width: 420px) {
            .login-shell {
                padding: 18px 12px;
            }

            .login-brand {
                min-height: 230px;
                padding: 22px;
            }

            .login-logo {
                width: 56px;
                height: 56px;
            }

            .login-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
<main class="login-shell">
    <section class="login-panel">
        <div class="login-brand">
            <div class="login-brand-content">
                <div class="login-logo-row">
                    <img src="../assets_pang1/logo.png" alt="SDO 1 Pangasinan Logo" class="login-logo">
                    <div>
                        <p class="login-agency">Department of Education</p>
                        <p class="login-division">Schools Division Office 1 Pangasinan</p>
                    </div>
                </div>

                <div class="login-brand-main">
                    <h1>PRIMEHR</h1>
                    <p>Human resource management system for employee records, leave credits, 201 files, OPCRF, and IPCRF submissions.</p>
                </div>
            </div>
        </div>

        <div class="login-form-wrap">
            <div class="login-form-card">
                <p class="login-eyebrow">Secure Access</p>
                <h2 class="login-title">Sign in to your account</h2>
                <p class="login-subtitle">Use your registered email and password to continue.</p>

                <?php if ($error): ?>
                    <div class="login-alert">
                        <i class="bi bi-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="on">
                    <div class="login-field">
                        <label for="email" class="login-label">Email address</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-envelope"></i>
                            <input id="email" class="login-input" type="email" name="email" placeholder="name@deped.gov.ph" value="<?= htmlspecialchars($email) ?>" required autofocus>
                        </div>
                    </div>

                    <div class="login-field">
                        <label for="password" class="login-label">Password</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-lock"></i>
                            <input id="password" class="login-input" type="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <button type="submit" class="login-button">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Sign In
                    </button>
                </form>

                <p class="login-footer-note">For account concerns, contact the system administrator or HR personnel.</p>
            </div>
        </div>
    </section>
</main>
</body>
</html>
