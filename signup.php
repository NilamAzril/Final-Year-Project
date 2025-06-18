<?php
session_start();
require_once 'config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug log function
function debugLog($message) {
    $logFile = 'debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Check if a username already exists for the given role
function userExists($username, $role, $conn) {
    $table = $role === 'project_manager' ? 'project_manager' : 'contractor';
    $stmt = $conn->prepare("SELECT 1 FROM $table WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    return $stmt->fetchColumn() !== false;
}

// Handle signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debugLog("Form submitted");

    $role = $_POST['role'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company_name = $role === 'project_manager' ? trim($_POST['company_name_pm'] ?? '') : trim($_POST['company_name_contractor'] ?? '');
    $address = $role === 'project_manager' ? trim($_POST['address_pm'] ?? '') : trim($_POST['address_contractor'] ?? '');
    $tax_id = trim($_POST['ic_number'] ?? '');
    $bank_account = trim($_POST['bank_account'] ?? '');

    $errors = [];
    $profile_picture_path = '';

    // Basic validation
    if (empty($role)) $errors[] = "Role is required";
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($phone)) $errors[] = "Phone number is required";

    // Check if username already exists
    $conn = getDBConnection();
    if (userExists($username, $role, $conn)) {
        $errors[] = "Username already exists";
    }

    // Role-specific validation
    if ($role === 'project_manager' || $role === 'contractor') {
        if (empty($company_name)) $errors[] = "Company name is required";
        if (empty($address)) $errors[] = "Address is required";
    }
    if ($role === 'contractor') {
        if (empty($tax_id)) $errors[] = "IC Number (Tax ID) is required";
        if (empty($bank_account)) $errors[] = "Bank account number is required";
    }

    // Handle profile picture upload
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Profile picture is required";
    } else {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading profile picture: " . $file['error'];
        } elseif (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Profile picture must be a JPEG, PNG, or GIF image";
        } elseif ($file['size'] > $max_size) {
            $errors[] = "Profile picture must be less than 2MB";
        } else {
            $upload_dir = 'Uploads/profile_pictures/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $errors[] = "Failed to create upload directory";
                    debugLog("Failed to create upload directory: $upload_dir");
                }
            }

            if (empty($errors)) {
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = $role . '_' . uniqid() . '.' . $file_extension;
                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $profile_picture_path = $destination;
                    debugLog("Profile picture uploaded successfully: $profile_picture_path");
                } else {
                    $errors[] = "Failed to upload profile picture";
                    debugLog("Failed to move uploaded file to: $destination");
                }
            }
        }
    }

    if (empty($errors)) {
        debugLog("No validation errors, proceeding with user creation");

        // Prepare user data
        $baseData = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email' => $email,
            'full_name' => $full_name,
            'phone_number' => $phone,
            'company_name' => $company_name,
            'address' => $address,
            'profile_picture' => $profile_picture_path,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'status' => 'active',
            'is_deleted' => 0
        ];

        // Add role-specific data
        if ($role === 'contractor') {
            $baseData['tax_id'] = $tax_id;
            $baseData['bank_account'] = $bank_account;
        }

        try {
            $conn->beginTransaction();

            if ($role === 'project_manager') {
                $sql = "INSERT INTO project_manager (pm_id, username, password, email, full_name, company_name, phone_number, address, profile_picture, created_at, updated_at, status, is_deleted)
                        VALUES (NULL, :username, :password, :email, :full_name, :company_name, :phone_number, :address, :profile_picture, :created_at, :updated_at, :status, :is_deleted)";
                $userIdField = 'pm_id';
            } elseif ($role === 'contractor') {
                $sql = "INSERT INTO contractor (contractor_id, username, password, email, full_name, company_name, phone_number, address, tax_id, bank_account, profile_picture, created_at, updated_at, status, is_deleted)
                        VALUES (NULL, :username, :password, :email, :full_name, :company_name, :phone_number, :address, :tax_id, :bank_account, :profile_picture, :created_at, :updated_at, :status, :is_deleted)";
                $userIdField = 'contractor_id';
            } else {
                throw new Exception("Invalid role specified");
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($baseData);
            $userId = $conn->lastInsertId();

            // Log audit trail
            if (function_exists('logAudit')) {
                logAudit($role, $userId, 'signup', "New user registered with username: $username");
            } else {
                debugLog("Warning: logAudit function not found");
            }

            $conn->commit();

            // Set session and redirect
            $_SESSION['user'] = [
                'user_id' => $userId,
                'username' => $username,
                'full_name' => $full_name,
                'email' => $email,
                'profile_picture' => $profile_picture_path,
                'role' => $role
            ];
            $success = "Account created successfully! Redirecting to dashboard...";
            header("Refresh:2;url=../dashboard.php");
        } catch (Exception $e) {
            $conn->rollBack();
            debugLog("Error creating account: " . $e->getMessage());
            $errors[] = "Error creating account: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Contractor Billing System</title>
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
            background-attachment: fixed;
            color: var(--dark-color);
            padding: 2rem;
        }

        .signup-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(0);
            transition: var(--transition);
        }

        .signup-container:hover {
            transform: translateY(-5px);
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
            color: white;
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

        .form-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-section-title i {
            color: white;
            font-size: 1.2rem;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
            color: var(--gray-color);
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

        textarea.form-control {
            height: auto;
            min-height: 100px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .btn-signup {
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
            cursor: pointer;
            position: relative;
            z-index: 10;
        }

        .btn-signup:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.4);
        }

        .btn-signup:active {
            transform: translateY(0);
        }

        .btn-signup:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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

        .role-fields {
            display: none;
        }

        .role-fields.show {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .profile-picture-preview {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            display: none;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .signup-container {
                padding: 2rem;
            }

            .auth-title {
                font-size: 1.5rem;
            }
        }

        /* Particle and Wave Effects */
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

    <div class="signup-container">
        <div class="auth-logo">
            <i class="bi bi-person-plus"></i>
        </div>
        <h1 class="auth-title">Create Account</h1>
        <p class="auth-subtitle">Join our contractor billing system</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
            
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data" id="signupForm">
            <div class="form-floating mb-3">
                <select class="form-control" id="role" name="role" required>
                    <option value="" selected disabled>Select your role</option>
                    <option value="project_manager">Project Manager</option>
                    <option value="contractor">Contractor</option>
                </select>
                <label for="role">Your Role</label>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">
                    <i class="bi bi-person"></i>
                    Basic Information
                </h3>
                
                <div class="mb-3">
                    <label for="profile_picture" class="form-label" style="color: white;">Profile Picture <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif" required>
                    <img id="profile_picture_preview" class="profile-picture-preview" alt="Profile Picture Preview">
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                    <label for="username">Username</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                    <label for="email">Email</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    <label for="confirm_password">Confirm Password</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Full Name" required>
                    <label for="full_name">Full Name</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone Number" required>
                    <label for="phone">Phone Number</label>
                </div>
            </div>

            <!-- Project Manager Fields -->
            <div class="form-section project-manager-fields role-fields">
                <h3 class="form-section-title">
                    <i class="bi bi-building"></i>
                    Project Manager Information
                </h3>
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="company_name_pm" name="company_name_pm" placeholder="Company Name" required>
                    <label for="company_name_pm">Company Name</label>
                </div>

                <div class="form-floating mb-3">
                    <textarea class="form-control" id="address_pm" name="address_pm" placeholder="Address" required></textarea>
                    <label for="address_pm">Address</label>
                </div>
            </div>

            <!-- Contractor Fields -->
            <div class="form-section contractor-fields role-fields">
                <h3 class="form-section-title">
                    <i class="bi bi-briefcase"></i>
                    Contractor Information
                </h3>
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="company_name_contractor" name="company_name_contractor" placeholder="Company Name" required>
                    <label for="company_name_contractor">Company Name</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="ic_number" name="ic_number" placeholder="IC Number (Tax ID)" required>
                    <label for="ic_number">IC Number (Tax ID)</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="bank_account" name="bank_account" placeholder="Bank Account Number" required>
                    <label for="bank_account">Bank Account Number</label>
                </div>

                <div class="form-floating mb-3">
                    <textarea class="form-control" id="address_contractor" name="address_contractor" placeholder="Address" required></textarea>
                    <label for="address_contractor">Address</label>
                </div>
            </div>

            <button type="submit" class="btn btn-signup" id="submitBtn">
                <i class="bi bi-person-plus me-2"></i>
                Create Account
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
    <script>
        // Show/hide role-specific fields
        document.getElementById('role').addEventListener('change', function() {
            const role = this.value;
            const pmFields = document.querySelector('.project-manager-fields');
            const contractorFields = document.querySelector('.contractor-fields');
            
            pmFields.classList.toggle('show', role === 'project_manager');
            contractorFields.classList.toggle('show', role === 'contractor');

            // Toggle required attributes
            const pmInputs = pmFields.querySelectorAll('input, textarea');
            const contractorInputs = contractorFields.querySelectorAll('input, textarea');

            pmInputs.forEach(input => {
                if (role === 'project_manager') {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                    input.value = '';
                }
            });

            contractorInputs.forEach(input => {
                if (role === 'contractor') {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                    input.value = '';
                }
            });
        });

        // Preview profile picture
        document.getElementById('profile_picture').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('profile_picture_preview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                preview.src = '';
            }
        });

        // Form validation and submission
        document.getElementById('signupForm').addEventListener('submit', function(event) {
            let errors = [];
            const role = document.getElementById('role').value;
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const fullName = document.getElementById('full_name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const profilePicture = document.getElementById('profile_picture').files[0];
            const companyName = role === 'project_manager' ? document.getElementById('company_name_pm').value.trim() : document.getElementById('company_name_contractor').value.trim();
            const address = role === 'project_manager' ? document.getElementById('address_pm').value.trim() : document.getElementById('address_contractor').value.trim();
            const taxId = document.getElementById('ic_number')?.value.trim();
            const bankAccount = document.getElementById('bank_account')?.value.trim();

            // Basic field validation
            if (!role) errors.push('Role is required');
            if (!username) errors.push('Username is required');
            if (!email) errors.push('Email is required');
            if (!password) errors.push('Password is required');
            if (password.length < 8) errors.push('Password must be at least 8 characters');
            if (password !== confirmPassword) errors.push('Passwords do not match');
            if (!fullName) errors.push('Full name is required');
            if (!phone) errors.push('Phone number is required');
            if (!profilePicture) errors.push('Profile picture is required');

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                errors.push('Please enter a valid email address');
            }

            // Validate profile picture
            if (profilePicture) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (!allowedTypes.includes(profilePicture.type)) {
                    errors.push('Profile picture must be a JPEG, PNG, or GIF image');
                }
                if (profilePicture.size > maxSize) {
                    errors.push('Profile picture must be less than 2MB');
                }
            }

            // Role-specific validation
            if (role === 'project_manager' || role === 'contractor') {
                if (!companyName) errors.push('Company name is required');
                if (!address) errors.push('Address is required');
            }
            if (role === 'contractor') {
                if (!taxId) errors.push('IC Number (Tax ID) is required');
                if (!bankAccount) errors.push('Bank account number is required');
            }

            // Display errors if any
            if (errors.length > 0) {
                event.preventDefault();
                const existingAlert = this.querySelector('.alert-danger');
                if (existingAlert) existingAlert.remove();
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger';
                const ul = document.createElement('ul');
                ul.className = 'mb-0';
                errors.forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error;
                    ul.appendChild(li);
                });
                alertDiv.appendChild(ul);
                this.insertBefore(alertDiv, this.firstChild);
                console.log('Form validation failed:', errors);
            } else {
                console.log('Form validated successfully, submitting...');
            }
        });
    </script>
</body>
</html>