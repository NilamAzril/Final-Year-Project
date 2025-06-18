<?php
session_start();
require_once 'config/database.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($username)) $errors[] = "Username is required";
    if (empty($role)) $errors[] = "Role is required";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";

    if (empty($errors)) {
        // Check if user exists
        $user = fetchSingle("SELECT * FROM $role WHERE username = :username", ['username' => $username]);
        
        if ($user) {
            // Update password
            updateData($role, 
                ['password' => password_hash($password, PASSWORD_DEFAULT)],
                $role . '_id = :id',
                ['id' => $user[$role . '_id']]
            );

            logAudit($role, $user[$role . '_id'], 'password_reset', 'Password reset completed');
            $success = "Password has been reset successfully. You can now login with your new password.";
        } else {
            $errors[] = "Username not found for the selected role";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Contractor Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #2e59d9;
            --accent-color: #f6c23e;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --gray-color: #64748b;
            --border-radius: 16px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(78, 115, 223, 0.7), rgba(59, 89, 152, 0.7)), url('uploads/NBFI_2021_carousel.jpg');
            background-size: cover;
            background-position: center;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .reset-container {
            width: 100%;
            max-width: 450px;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            color: white;
        }

        .auth-logo i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
        }

        .auth-title {
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.5rem;
            color: white;
        }

        .auth-subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-control {
            height: calc(3.5rem + 2px);
            padding: 1rem 0.75rem;
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
            background: rgba(255, 255, 255, 0.15);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-control option {
            background-color: #1e293b;
            color: white;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
            color: var(--gray-color);
        }

        .btn-reset {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            width: 100%;
            padding: 1rem;
            border-radius: var(--border-radius);
            border: none;
            font-weight: 600;
            font-size: 1rem;
            transition: var(--transition);
            margin-bottom: 1rem;
        }

        .btn-reset:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.4);
        }

        .auth-links {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .auth-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .auth-link:hover {
            color: white;
            transform: translateX(-5px);
        }

        .alert {
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            padding: 1rem 1.25rem;
            border: none;
        }

        .alert-danger {
            background: rgba(231, 74, 59, 0.1);
            border-left: 4px solid var(--danger-color);
            color: var(--danger-color);
        }

        .alert-success {
            background: rgba(28, 200, 138, 0.1);
            border-left: 4px solid var(--success-color);
            color: var(--success-color);
        }

        /* Particle and Wave Effects from manageclient.php */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 5px;
            height: 5px;
            background: rgba(30, 58, 138, 0.5);
            border-radius: 50%;
            animation: float 20s linear infinite;
            opacity: 0.2;
            will-change: transform, opacity;
        }

        @keyframes float {
            0% { transform: translateY(100vh) scale(1); opacity: 0.2; }
            50% { opacity: 0.4; }
            100% { transform: translateY(-100vh) scale(0.7); opacity: 0; }
        }

        .wave-background {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 40vh;
            z-index: -1;
            overflow: hidden;
        }

        .wave {
            position: absolute;
            bottom: 0;
            width: 200%;
            height: 100%;
            animation: wave 15s linear infinite;
            will-change: transform;
        }

        .wave:nth-child(1) {
            fill: rgba(30, 58, 138, 0.4);
            animation-duration: 15s;
        }

        .wave:nth-child(2) {
            fill: rgba(51, 65, 85, 0.2);
            animation-duration: 18s;
            animation-delay: -4s;
            transform: translateX(80px);
        }

        .wave:nth-child(3) {
            fill: rgba(4, 120, 87, 0.15);
            animation-duration: 20s;
            animation-delay: -6s;
            transform: translateX(-80px);
        }

        @keyframes wave {
            0% { transform: translateX(0); }
            50% { transform: translateX(-20%); }
            100% { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <!-- Particles -->
    <div class="particles">
        <?php for ($i = 0; $i < 20; $i++): ?>
            <div class="particle" style="left: <?php echo rand(0, 100); ?>%; animation-delay: <?php echo rand(0, 20); ?>s;"></div>
        <?php endfor; ?>
    </div>

    <!-- Wave background -->
    <div class="wave-background">
        <svg class="wave" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <path d="M0,192L48,176C96,160,192,128,288,138.7C384,149,480,202,576,213.3C672,224,768,192,864,176C960,160,1056,160,1152,186.7C1248,213,1344,267,1392,293.3L1440,320V320H0Z"/>
        </svg>
        <svg class="wave" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <path d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96V320H0Z"/>
        </svg>
        <svg class="wave" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <path d="M0,160L48,144C96,128,192,96,288,106.7C384,117,480,171,576,192C672,213,768,202,864,186.7C960,171,1056,149,1152,144C1248,139,1344,149,1392,154.7L1440,160V320H0Z"/>
        </svg>
    </div>

    <div class="reset-container">
        <div class="auth-logo">
            <i class="bi bi-key"></i>
        </div>
        <h1 class="auth-title">Reset Password</h1>
        <p class="auth-subtitle">Enter your username and new password</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-floating mb-3">
                <select class="form-control" id="role" name="role" required>
                    <option value="" selected disabled>Select your role</option>
                    <option value="project_manager">Project Manager</option>
                    <option value="contractor">Contractor</option>
                    <option value="admin">Admin</option>
                </select>
                <label for="role">Your Role</label>
            </div>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                <label for="username">Username</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="New Password" required>
                <label for="password">New Password</label>
            </div>

            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                <label for="confirm_password">Confirm Password</label>
            </div>

            <button type="submit" class="btn btn-reset">
                <i class="bi bi-check-circle me-2"></i>
                Reset Password
            </button>

            <div class="auth-links">
                <a href="login.php" class="auth-link">
                    <i class="bi bi-arrow-left"></i>
                    Back to Login
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>