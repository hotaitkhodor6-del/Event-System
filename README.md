
<?php 
include './database/config.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Event Management System">
    <meta name="keywords" content="event, management, system, php, mysql, bootstrap, responsive">
    <meta name="author" content="khodor hotait">
    <title>Event Management System</title>
    <link rel="shortcut icon" href="./assets/images/event_system.png" type="image/x-icon"> <!--ICON-->
    <link href="./assets/css/style.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200;0,400;0,600;0,700;1,200;1,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/v4-shims.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"
        integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"
        integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
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
                <li><a href="#hero">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="#login">Login</a></li>
            </ul>
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-right">
                <a href="./users/login.php" class="nav-btn nav-btn-primary">Get Started</a>
            </div>
        </div>
    </header>

    <!-- ======================== MAIN CONTENT ======================== -->
    <main>

        <!-- ======================== HERO SECTION ======================== -->
        <section class="hero" id="hero">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Manage Your Events Like Never Before</h1>
                    <p>Create, organize, and execute stunning events with our powerful event management system. From planning to execution, we've got you covered.</p>
                    <div class="hero-cta">
                        <button class="btn-cta-primary" data-toggle="modal" data-target="#loginModal">Start Free Trial</button>
                        <a href="#features" class="btn-cta-secondary">Learn More</a>
                    </div>
                </div>
                <div class="hero-image">
                    <svg viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg">
                        <rect x="50" y="50" width="400" height="400" fill="rgba(255,255,255,0.1)" rx="20"/>
                        <circle cx="250" cy="150" r="40" fill="white" opacity="0.2"/>
                        <rect x="100" y="220" width="300" height="150" fill="white" opacity="0.1" rx="10"/>
                        <path d="M 100 250 Q 250 200 400 280" stroke="white" stroke-width="2" fill="none" opacity="0.3"/>
                    </svg>
                </div>
            </div>
        </section>

        <!-- ======================== ABOUT SECTION ======================== -->
        <section class="about" id="about">
            <div class="section-container">
                <div class="section-header">
                    <h2>Why Choose EventHub?</h2>
                    <p>We provide comprehensive solutions for event management with cutting-edge technology and user-friendly interface.</p>
                </div>
                <div class="about-content">
                    <div class="about-text">
                        <h3>Streamline Your Event Planning</h3>
                        <p>EventHub is designed to make event management effortless. Whether you're organizing corporate conferences, weddings, or community gatherings, our platform provides all the tools you need.</p>
                        <ul class="about-list">
                            <li><i class="fas fa-check-circle"></i> Real-time event tracking</li>
                            <li><i class="fas fa-check-circle"></i> Team collaboration tools</li>
                            <li><i class="fas fa-check-circle"></i> Automated notifications</li>
                            <li><i class="fas fa-check-circle"></i> Advanced analytics</li>
                            <li><i class="fas fa-check-circle"></i> Secure data management</li>
                        </ul>
                        <a href="./users/login.php" class="nav-btn nav-btn-primary" style="display: inline-block; margin-top: 20px;">Get Started Now</a>
                    </div>
                    <div class="about-image">
                        <svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:rgb(99,102,241);stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:rgb(79,70,229);stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <rect x="30" y="30" width="340" height="340" fill="url(#grad1)" rx="20" opacity="0.2"/>
                            <circle cx="200" cy="200" r="80" fill="url(#grad1)" opacity="0.3"/>
                            <circle cx="200" cy="200" r="50" fill="url(#grad1)" opacity="0.5"/>
                        </svg>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================== FEATURES SECTION ======================== -->
        <section class="features" id="features">
            <div class="section-container">
                <div class="section-header">
                    <h2>Powerful Features</h2>
                    <p>Everything you need to manage events successfully, all in one place.</p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3>Event Planning</h3>
                        <p>Comprehensive tools for planning every aspect of your event, from date selection to vendor management.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Team Collaboration</h3>
                        <p>Work seamlessly with your team members. Assign tasks, set deadlines, and track progress in real-time.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3>Smart Notifications</h3>
                        <p>Automated reminders and alerts keep everyone informed. Never miss an important milestone again.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Advanced Analytics</h3>
                        <p>Get detailed insights into your event with comprehensive analytics and reporting tools.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3>Security First</h3>
                        <p>Your data is protected with enterprise-grade security. We take privacy seriously.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Mobile Ready</h3>
                        <p>Manage your events on the go with our fully responsive mobile-friendly platform.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================== LOGIN SECTION ======================== -->
        <section class="login-section" id="login">
            <div class="section-container">
                <div class="login-cta-content">
                    <div class="login-cta-text">
                        <h2>Ready to Get Started?</h2>
                        <p>Join thousands of event organizers who are already using EventHub to create amazing experiences. Access your account or create a new one in seconds.</p>
                        <ul class="login-benefits">
                            <li><i class="fas fa-check-circle"></i> Instant access to all features</li>
                            <li><i class="fas fa-check-circle"></i> No credit card required</li>
                            <li><i class="fas fa-check-circle"></i> 24/7 customer support</li>
                        </ul>
                    </div>
                    <div class="login-cta-buttons">
                        <a href="./users/login.php" class="btn-login-primary">
                            <i class="fas fa-sign-in-alt"></i>
                            Sign In / Register
                        </a>
                        <p class="login-cta-subtext">Secure login with industry-standard encryption</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================== CONTACT SECTION ======================== -->
        <section class="contact-section" id="contact">
            <div class="section-container">
                <div class="section-header">
                    <h2>Get in Touch</h2>
                    <p>Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
                </div>
                <div class="contact-content">
                    <div class="contact-info">
                        <div class="contact-info-item">
                            <div class="contact-info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h4>Address</h4>
                            <p>123 Hamra Street<br>Beirut, Lebanon</p>
                        </div>
                        <div class="contact-info-item">
                            <div class="contact-info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h4>Phone</h4>
                            <p><a href="tel:+96170123456">+961 70 123 456</a></p>
                        </div>
                        <div class="contact-info-item">
                            <div class="contact-info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h4>Email</h4>
                            <p><a href="mailto:info@eventhub.com">info@eventhub.com</a></p>
                        </div>
                        <div class="contact-info-item">
                            <div class="contact-info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4>Business Hours</h4>
                            <p>Mon - Fri: 9:00 AM - 6:00 PM<br>Sat - Sun: Closed</p>
                        </div>
                    </div>
                    <div class="contact-form-wrapper">
                        <form class="contact-form" id="contactForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" required placeholder="Khodor hoteit">
                                </div>
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" required placeholder="khodor@example.com">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" required placeholder="How can we help?">
                            </div>
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" rows="5" required placeholder="Tell us more..."></textarea>
                            </div>
                            <button type="submit" class="contact-submit-btn">Send Message</button>
                            <p class="form-note">We'll get back to you within 24 hours.</p>
                        </form>
                    </div>
                </div>
            </div>
        </section>

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
    </main>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title" id="loginModalLabel">Quick Login</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 30px;">
                    <form action="./users/login.php" method="POST">
                        <div class="form-group">
                            <label for="modal-email">Email Address</label>
                            <input type="email" id="modal-email" name="email" class="form-control" required placeholder="your@email.com">
                        </div>
                        <div class="form-group">
                            <label for="modal-password">Password</label>
                            <input type="password" id="modal-password" name="password" class="form-control" required placeholder="••••••••">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; background: var(--primary-color); border: none; padding: 10px;">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
<script src="./assets/js/script.js"></script>
</html>
