<?php
session_start();
require_once 'config/database.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING) ?? '';
    $password = $_POST['password'] ?? '';
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING) ?? '';

    error_log("Login attempt - Username: $username, Role: $role");

    if (empty($username) || empty($password) || empty($role)) {
        $error = 'All fields are required';
        error_log("Login validation failed - empty fields");
    } else {
        // Validate credentials
        $user = validateCredentials($username, $password, $role);
        error_log("ValidateCredentials returned: " . ($user ? "success" : "failed"));
        
        if ($user) {
            error_log("User data before session: " . print_r($user, true));
            
            // Set session variables using array format for all roles
            $_SESSION['user'] = [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'profile_picture' => $user['profile_picture'] ?? null,
                'status' => $user['status']
            ];
            $_SESSION['role'] = $role;

            error_log("Session data after set: " . print_r($_SESSION, true));

            // Update last login
            try {
                $tableMap = [
                    'project_manager' => 'project_manager',
                    'contractor' => 'contractor',
                    'client' => 'client',
                    'admin' => 'admin'
                ];
                $table = $tableMap[$role];
                $idColumn = $role === 'project_manager' ? 'pm_id' : $role . '_id';
                
                error_log("Updating last login for $role in table $table");
                updateData(
                    $table,
                    ['last_login' => date('Y-m-d H:i:s')],
                    "$idColumn = :id",
                    ['id' => $user['user_id']]
                );

                $dashboardUrl = getDashboardUrl($role);
                error_log("Redirecting to dashboard: $dashboardUrl");
                
                // Force session write
                session_write_close();
                
                // Add debug output before redirect
                echo "<!--DEBUG: Redirecting to $dashboardUrl -->";
                
                header("Location: $dashboardUrl");
                exit();
            } catch (Exception $e) {
                error_log("Error during login process: " . $e->getMessage());
                $error = "An error occurred during login. Please try again.";
            }
        } else {
            $error = 'Invalid username, password, or role';
            error_log("Login failed - invalid credentials");
        }
    }
}

// Debug current session at page load
error_log("Current session data: " . print_r($_SESSION ?? [], true));

// Add error display for debugging during development
if (isset($error)) {
    error_log("Login error: $error");
}

// Add this function before the HTML
function getDashboardUrl($role) {
    switch ($role) {
        case 'project_manager':
            return 'projectmanager/dashboard.php';
        case 'contractor':
            return 'contractor/dashboard.php';
        case 'client':
            return 'client/dashboard.php';
        case 'admin':
            return 'admin/dashboard.php';
        default:
            return 'login.php';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Contractor Billing System</title>
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
            background: linear-gradient(135deg, rgba(78, 115, 223, 0.7), rgba(59, 89, 152, 0.7)), url('Uploads/NBFI_2021_carousel.jpg');
            background-size: cover;
            background-position: center;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: clamp(1.5rem, 5vw, 3rem);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(0);
            transition: var(--transition);
            margin: 1rem;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .auth-logo {
            text-align: center;
            margin-bottom: clamp(1rem, 3vw, 2rem);
            font-size: clamp(1.5rem, 4vw, 2rem);
            color: var(--primary-color);
        }

        .auth-logo i {
            font-size: clamp(2rem, 5vw, 2.5rem);
            margin-bottom: clamp(0.5rem, 2vw, 1rem);
            display: block;
        }

        .auth-title {
            font-size: clamp(1.25rem, 4vw, 1.75rem);
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.5rem;
            color: white;
        }

        .auth-subtitle {
            text-align: center;
            color: var(--gray-color);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-floating {
            margin-bottom: 1rem;
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
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-control option {
            background-color: #1e293b;
            color: white;
        }

        .form-floating label {
            color: rgba(255, 255, 255, 0.7);
            padding: 1rem 0.75rem;
        }

        .form-floating>.form-control:focus~label,
        .form-floating>.form-control:not(:placeholder-shown)~label {
            color: var(--primary-color);
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }

        .btn {
            height: calc(3.5rem + 2px);
            font-size: 1rem;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            width: 100%;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .auth-links {
            display: flex;
            justify-content: space-between;
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
            transform: translateX(5px);
        }

        .alert {
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            padding: 1rem 1.25rem;
            border: none;
            background: rgba(231, 74, 59, 0.1);
            border-left: 4px solid var(--danger-color);
            color: #fff;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            border-color: rgba(255, 255, 255, 0.2);
            background-color: rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            cursor: pointer;
        }

        /* Mobile-specific styles */
        @media (max-width: 576px) {
            body {
                padding: 0.5rem;
            }

            .login-container {
                padding: 1.5rem;
                margin: 0.5rem;
            }

            .form-control, .btn {
                height: 3.5rem;
            }

            .auth-subtitle {
                font-size: 0.9rem;
                margin-bottom: 1.5rem;
            }
        }

        /* Touch-friendly elements */
        .form-control, .btn, .nav-link {
            min-height: 44px;
        }

        .mb-4 {
            margin-bottom: 1rem !important;
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

    <div class="login-container">
        <div class="auth-logo">
            <i class="bi bi-building-lock"></i>
        </div>
        <h1 class="auth-title">Welcome Back!</h1>
        <p class="auth-subtitle">Sign in to continue to your dashboard</p>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
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

            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>

            <div class="remember-me">
                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>

            <div class="auth-links">
                <div>
                    <a href="index.php" class="auth-link">
                        <i class="bi bi-arrow-left"></i>Back to Home
                    </a>
                </div>
                <div class="d-flex gap-3">
                    <a href="forgot_password.php" class="auth-link">
                        <i class="bi bi-key"></i>Forgot Password?
                    </a>
                    <a href="signup.php" class="auth-link">
                        <i class="bi bi-person-plus"></i>Create Account
                    </a>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>