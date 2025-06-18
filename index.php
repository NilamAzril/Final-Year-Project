<?php
session_start();
require_once 'config/database.php';

// Initialize variables for statistics
$totalProjects = 0;
$totalUsers = 0;
$processedInvoices = 0;

// Fetch Total Projects
try {
    $projectCount = fetchSingle(
        "SELECT COUNT(*) AS total FROM projects WHERE is_deleted = 0"
    );
    $totalProjects = $projectCount['total'] ?? 0;
} catch (Exception $e) {
    error_log("Error fetching total projects: " . $e->getMessage());
}

// Fetch Active Users (distinct project managers and contractors)
try {
    $userCount = fetchSingle(
        "SELECT COUNT(DISTINCT user_id) AS total 
         FROM (
             SELECT pm_id AS user_id FROM project_manager WHERE is_deleted = 0 AND status = 'active'
             UNION
             SELECT contractor_id AS user_id FROM contractor WHERE is_deleted = 0 AND status = 'active'
         ) AS users"
    );
    $totalUsers = $userCount['total'] ?? 0;
} catch (Exception $e) {
    error_log("Error fetching active users: " . $e->getMessage());
}

// Fetch Number of Processed Invoices
try {
    $invoiceCount = fetchSingle(
        "SELECT COUNT(*) AS total_invoices 
         FROM invoices 
         WHERE status = 'approved'"
    );
    $processedInvoices = $invoiceCount['total_invoices'] ?? 0;
} catch (Exception $e) {
    error_log("Error fetching processed invoices: " . $e->getMessage());
}

// Static testimonials data
$testimonials = [
    [
        'content' => 'This system has revolutionized how we manage our contractor billing. The interface is intuitive and the features are exactly what we needed.',
        'author' => 'John Smith',
        'position' => 'Project Manager at BuildRight'
    ],
    [
        'content' => 'The expense tracking feature has saved us countless hours of manual work. Highly recommended for any construction business.',
        'author' => 'Sarah Johnson',
        'position' => 'Finance Director at ConstructPro'
    ],
    [
        'content' => 'Finally, a billing system that understands the needs of contractors. The invoice management is top-notch.',
        'author' => 'Michael Chen',
        'position' => 'CEO of UrbanBuild'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contractor Billing & Expense Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.2);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            color: var(--dark-color);
        }

        .navbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            box-shadow: var(--box-shadow);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }

        .nav-link {
            color: var(--gray-color);
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: var(--transition);
            position: relative;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: var(--transition);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .hero-section {
            padding: 0;
            background: linear-gradient(135deg, rgba(78, 115, 223, 0.7), rgba(59, 89, 152, 0.7)), url('uploads/NBFI_2021_carousel.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            position: relative;
            overflow: hidden;
            height: auto;
            min-height: 400px;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.1), transparent);
            z-index: 1;
        }

        .hero-section .container {
            position: relative;
            z-index: 2;
        }

        .hero-content {
            background: rgba(0, 0, 0, 0.4);
            padding: 2rem;
            border-radius: var(--border-radius);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transform: translateY(0);
            transition: transform 0.3s ease;
            margin: 1rem;
        }

        .hero-content:hover {
            transform: translateY(-5px);
        }

        .hero-title {
            font-size: clamp(2rem, 5vw, 4rem);
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            color: white;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 3vw, 1.4rem);
            opacity: 0.95;
            margin-bottom: 2.5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-button {
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 500;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .cta-button:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }

        .hero-image {
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            max-width: 100%;
            height: auto;
        }

        .hero-image:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        .features-section {
            padding: 5rem 0;
        }

        .feature-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            backdrop-filter: blur(10px);
            transition: var(--transition);
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }

        .stats-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            backdrop-filter: blur(10px);
            text-align: center;
            transition: var(--transition);
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .stats-card:hover .stats-number {
            transform: scale(1.1);
        }

        .stats-label {
            color: var(--gray-color);
            font-size: 1.1rem;
        }

        .testimonial-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            backdrop-filter: blur(10px);
            position: relative;
            transition: var(--transition);
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid var(--primary-color);
            transition: var(--transition);
        }

        .testimonial-card:hover .testimonial-avatar {
            transform: scale(1.1);
        }

        .testimonial-text {
            font-style: italic;
            color: var(--gray-color);
            margin-bottom: 1rem;
        }

        .testimonial-author {
            font-weight: 600;
            color: var(--primary-color);
        }

        .contact-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            backdrop-filter: blur(10px);
        }

        .form-control {
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.9);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .footer {
            background: linear-gradient(135deg, var(--dark-color), #2d3748);
            color: white;
            padding: 3rem 0;
        }

        .social-links a {
            color: white;
            margin: 0 0.75rem;
            font-size: 1.5rem;
            transition: var(--transition);
        }

        .social-links a:hover {
            color: var(--accent-color);
            transform: translateY(-3px);
        }

        /* Mobile Navigation Styles */
        @media (max-width: 768px) {
            .navbar-collapse {
                background: rgba(255, 255, 255, 0.95);
                padding: 1rem;
                border-radius: var(--border-radius);
                margin-top: 1rem;
                box-shadow: var(--box-shadow);
            }

            .nav-link {
                padding: 0.75rem 1rem;
                border-radius: 8px;
            }

            .nav-link:hover {
                background: rgba(78, 115, 223, 0.1);
            }

            .btn {
                width: 100%;
                margin: 0.5rem 0;
                padding: 0.75rem;
            }

            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        /* Touch-friendly elements */
        .btn, .nav-link, .form-control {
            min-height: 44px;
        }

        /* Responsive spacing */
        @media (max-width: 576px) {
            .container {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .section-padding {
                padding: 2rem 0;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 1s ease-in;
        }

        .slide-up {
            animation: slideUp 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-cash-stack me-2"></i> Contractor Billing and Expense Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#stats">Statistics</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">Testimonials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-10 text-center fade-in">
                    <div class="hero-content">
                        <h1 class="hero-title">Streamline Your<br>Contractor Billing</h1>
                        <p class="hero-subtitle">Manage projects, track expenses, and process invoices effortlessly with our all-in-one platform.</p>
                        <a href="signup.php" class="cta-button">
                            Get Started <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Impact Section -->
    <section id="stats" class="features-section">
        <div class="container">
            <h2 class="text-center mb-5">Our Impact</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo number_format($totalProjects); ?></div>
                        <div class="stats-label">Total Projects</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo number_format($totalUsers); ?></div>
                        <div class="stats-label">Active Users</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo number_format($processedInvoices); ?></div>
                        <div class="stats-label">Processed Invoices</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <h2 class="text-center mb-5">Key Features</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <h3>Invoice Management</h3>
                        <p>Create, track, and manage invoices with ease. Automated reminders and status tracking.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h3>Expense Tracking</h3>
                        <p>Monitor project expenses in real-time. Generate detailed reports and analytics.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h3>Project Management</h3>
                        <p>Track project progress, deadlines, and milestones. Collaborate with team members.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="features-section">
        <div class="container">
            <h2 class="text-center mb-5">What Our Users Say</h2>
            <div class="row g-4">
                <?php foreach ($testimonials as $testimonial): ?>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($testimonial['author']); ?>&background=4e73df&color=fff" 
                             alt="<?php echo htmlspecialchars($testimonial['author']); ?>" 
                             class="testimonial-avatar">
                        <p class="testimonial-text">"<?php echo htmlspecialchars($testimonial['content']); ?>"</p>
                        <div class="testimonial-author">
                            <?php echo htmlspecialchars($testimonial['author']); ?>
                            <br>
                            <small class="text-muted"><?php echo htmlspecialchars($testimonial['position']); ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="features-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="contact-card">
                        <h2 class="text-center mb-4">Contact Us</h2>
                        <div class="contact-info mb-3">
                            <div class="contact-icon">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">NilamAzril</h5>
                                <p class="text-muted mb-0">System Administrator</p>
                            </div>
                        </div>
                        <div class="contact-info mb-3">
                            <div class="contact-icon">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">019-7273725</h5>
                                <p class="text-muted mb-0">Available 9AM - 5PM</p>
                            </div>
                        </div>
                        <div class="contact-info">
                            <div class="contact-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">support@contractorbms.com</h5>
                                <p class="text-muted mb-0">Email Support</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add animation classes on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        });

        document.querySelectorAll('.feature-card, .stats-card, .testimonial-card').forEach((el) => observer.observe(el));

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>