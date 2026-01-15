<?php 
session_start();
include '../database/config.php';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE user_id = '$user_id'";
    $result = mysqli_query($con, $sql);
    $user = mysqli_fetch_assoc($result);
    $user_role = $user ? $user['role'] : null;
    if ($user_role === 'admin') {
        // User is an admin, redirect to admin dashboard
        header("Location: ../admin/admin_dashboard.php");
        exit();
    } else if($user_role === 'advisor') {
        // User is a regular user, redirect to user dashboard
        header("Location: ../advisor/advisor_dashboard.php");
        exit();
    }
    else if($user_role === 'student') {
        // User is a regular user, redirect to user dashboard
        header("Location: ../users/student_dashboard.php");
        exit();
    }
    else {
        // Unknown role, log out the user
        header("Location: ../users/logout.php");
        exit();
    }
}
// take email and password from login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate email and password
    if(empty($email) || empty($password)) {
        header("Location: ../users/login.php?message=Please fill in all fields");
        exit();
    }

    // Check credentials in the database
    $sql = "SELECT * FROM users WHERE email_address = '$email' AND password = '$password'";
    $result = mysqli_query($con, $sql);
    $user = mysqli_fetch_assoc($result);

    if($user) {
        // User found, set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_role'] = $user['role'];
        if($user['role'] === 'admin') {
            header("Location: ../admin/admin_dashboard.php");
            exit();
        } else if($user['role'] === 'advisor') {
            header("Location: ../advisor/advisor_dashboard.php");
            exit();
        } else if($user['role'] === 'student') {
            header("Location: ../users/student_dashboard.php");
            exit();
        } else {
            header("Location: ../users/logout.php");
            exit();
        }
    } else {
        header("Location: ../users/login.php?message=Invalid email or password");
        exit();
    }
}

//Alert Messages
if(isset($_GET['message'])) {
    $message = $_GET['message'];
    echo "<script>alert('$message');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Event Management System Login">
    <meta name="keywords" content="event, management, system, php, mysql, login">
    <meta name="author" content="Mahdi Saleh">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <title>Login - EventHub</title>
    <link rel="shortcut icon" href="../assets/images/event_system.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200;0,400;0,600;0,700;1,200;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>
    <!-- ======================== HEADER ======================== -->
    <header>
        <div class="header-content">
            <a href="#" class="header-logo">
                <i class="fas fa-calendar-alt"></i>
                EventHub
            </a>
            <ul class="nav-menu">
                <li><a href="../index.php#hero">Home</a></li>
                <li><a href="../index.php#about">About</a></li>
                <li><a href="../index.php#features">Features</a></li>
                <li><a href="../index.php#contact">Contact</a></li>
                <li><a href="../index.php#login">Login</a></li>
            </ul>
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-right">
                <a href="./login.php" class="nav-btn nav-btn-primary">Get Started</a>
            </div>
        </div>
    </header>

    <!-- ======================== LOGIN CONTAINER ======================== -->
    <div class="login-wrapper">
        <!-- LEFT SIDE - BRANDING -->
        <div class="login-branding">
            <div class="branding-content">
                <div class="branding-logo">
                    <i class="fas fa-calendar-alt"></i>
                    EventHub
                </div>
                <h1 class="branding-title">Welcome Back</h1>
                <p class="branding-description">Manage your events with our powerful and intuitive platform. Create memorable experiences with just a few clicks.</p>
                <ul class="branding-features">
                    <li><i class="fas fa-check-circle"></i> Real-time event tracking</li>
                    <li><i class="fas fa-check-circle"></i> Team collaboration tools</li>
                    <li><i class="fas fa-check-circle"></i> Advanced analytics</li>
                    <li><i class="fas fa-check-circle"></i> 24/7 customer support</li>
                </ul>
            </div>
        </div>

        <!-- RIGHT SIDE - LOGIN FORM -->
        <div class="login-form-section">
            <div class="login-header">
                <h2>Sign In</h2>
                <p>Enter your credentials to access your account</p>
            </div>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <form class="login-form" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="you@example.com"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="••••••••"
                        required
                    >
                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <div class="forgot-password">
                        <a href="#">Forgot password?</a>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="divider">or</div>

            <div class="social-login">
                <button type="button" class="social-btn" title="Login with Google">
                    <i class="fab fa-google"></i>
                </button>
                <button type="button" class="social-btn" title="Login with Facebook">
                    <i class="fab fa-facebook-f"></i>
                </button>
                <button type="button" class="social-btn" title="Login with Microsoft">
                    <i class="fab fa-microsoft"></i>
                </button>
            </div>

            <div class="signup-link">
                Don't have an account? <a href="#">Create one now</a>
            </div>
        </div>
    </div>
    
    <script>
        // Form submission handling - Let PHP handle the login
        const loginForm = document.getElementById('loginForm');
        const alertContainer = document.getElementById('alertContainer');

        loginForm.addEventListener('submit', function(e) {
            // Don't prevent default - let PHP handle the form submission
            const submitBtn = this.querySelector('.login-btn');
            const originalText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
        });

        // Social login buttons
        document.querySelectorAll('.social-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                alert('Social login integration coming soon!');
            });
        });

        // Display alert messages if there are any from PHP
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            
            if(message) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-error';
                alertDiv.innerHTML = `
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="alert-content">
                        <strong>Error:</strong> ${message}
                    </div>
                `;
                alertContainer.appendChild(alertDiv);
                
                // Auto-hide alert after 5 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            }
        });
    </script>

    <!-- ======================== FOOTER ======================== -->
        <footer>
            <div class="footer-content">
                <div class="footer-section">
                    <h4>About EventHub</h4>
                    <p>EventHub is a modern event management platform designed to make your events extraordinary.</p>
                    <div class="social-icons">
                        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#login">Login</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">API Reference</a></li>
                        <li><a href="#">Community</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Us</h4>
                    <p>Email: info@eventhub.com</p>
                    <p>Phone: +961 70 123 456</p>
                    <p>Address: Beirut, Lebanon</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 EventHub. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </footer>
</body>
<script src="../assets/js/script.js"></script>
</html>