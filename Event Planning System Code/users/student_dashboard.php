<?php
session_start();
include '../database/config.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Password
    if (isset($_POST['updatePassword'])) {
        $current_password = $_POST['currentPassword'];
        $new_password = $_POST['newPassword'];
        $confirm_password = $_POST['confirmPassword'];
        
        // Fetch current password from database
        $user_query = mysqli_query($con, "SELECT password FROM users WHERE user_id = '$user_id'");
        $user = mysqli_fetch_assoc($user_query);
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $error_message = "Current password is incorrect!";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match!";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters!";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update_query = "UPDATE users SET password = '$hashed_password' WHERE user_id = '$user_id'";
            
            if (mysqli_query($con, $update_query)) {
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Error: Could not update password. " . mysqli_error($con);
            }
        }
    }
    
    // Submit Feedback for Event
    if (isset($_POST['submitFeedback'])) {
        $event_id = mysqli_real_escape_string($con, $_POST['feedbackEvent']);
        $rating = mysqli_real_escape_string($con, $_POST['feedbackRating']);
        $comments = mysqli_real_escape_string($con, $_POST['feedbackComments']);
        
        // Check if feedback already exists for this event and student
        $check_query = "SELECT feedback_id FROM feedback WHERE event_id = '$event_id' AND student_id = '$user_id'";
        $existing_feedback = mysqli_query($con, $check_query);
        
        if (mysqli_num_rows($existing_feedback) > 0) {
            $error_message = "You have already submitted feedback for this event!";
        } else {
            $insert_query = "INSERT INTO feedback (event_id, student_id, rating, comments, status) 
                            VALUES ('$event_id', '$user_id', '$rating', '$comments', 'submitted')";
            
            if (mysqli_query($con, $insert_query)) {
                $success_message = "Feedback submitted successfully!";
            } else {
                $error_message = "Error: Could not submit feedback. " . mysqli_error($con);
            }
        }
    }
}

// Fetch student statistics
$invited_events = mysqli_num_rows(mysqli_query($con, "
    SELECT e.event_id FROM events e
    JOIN event_invitations ei ON e.event_id = ei.event_id
    WHERE ei.student_id = '$user_id'
"));

$attended_events = mysqli_num_rows(mysqli_query($con, "
    SELECT e.event_id FROM events e
    JOIN event_invitations ei ON e.event_id = ei.event_id
    WHERE ei.student_id = '$user_id' AND e.event_date < CURDATE()
"));

$pending_feedback = mysqli_num_rows(mysqli_query($con, "
    SELECT e.event_id FROM events e
    JOIN event_invitations ei ON e.event_id = ei.event_id
    LEFT JOIN feedback f ON e.event_id = f.event_id AND f.student_id = '$user_id'
    WHERE ei.student_id = '$user_id' AND e.event_date < CURDATE() AND f.feedback_id IS NULL
"));

$submitted_feedback = mysqli_num_rows(mysqli_query($con, "
    SELECT f.feedback_id FROM feedback f
    JOIN events e ON f.event_id = e.event_id
    JOIN event_invitations ei ON e.event_id = ei.event_id
    WHERE ei.student_id = '$user_id' AND f.student_id = '$user_id'
"));

// Fetch student info
$user_query = mysqli_query($con, "SELECT * FROM users WHERE user_id = '$user_id'");
$student = mysqli_fetch_assoc($user_query);
$student_name = $student ? $student['name'] : 'Student';
$student_email = $student ? $student['email_address'] : '';
$student_phone = $student ? $student['phone'] : '';
$student_department = $student ? $student['department'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Student Dashboard - Event Management System">
    <meta name="author" content="Mahdi Saleh">
    <title>Student Dashboard - EventHub</title>
    <link rel="shortcut icon" href="../assets/images/event_system.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200;0,400;0,600;0,700;1,200;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #ec4899;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
            --bg-white: #ffffff;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            background-color: var(--bg-light);
            line-height: 1.6;
        }

        .dashboard-wrapper {
            display: grid;
            grid-template-columns: 280px 1fr;
            grid-template-rows: auto 1fr auto;
            min-height: 100vh;
            gap: 0;
        }

        /* Sidebar */
        .sidebar {
            grid-column: 1;
            grid-row: 1 / 3;
            background-color: var(--primary-color);
            color: white;
            padding: 30px 20px;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            max-height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header i {
            font-size: 28px;
        }

        .sidebar-header h2 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 12px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            background-color: var(--primary-dark);
            color: white;
        }

        .nav-link.active {
            background-color: white;
            color: var(--primary-color);
        }

        .nav-link i {
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        /* Header */
        .header {
            grid-column: 2;
            grid-row: 1;
            background-color: var(--bg-white);
            border-bottom: 1px solid var(--border-color);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Main Content */
        .main-content {
            grid-column: 2;
            grid-row: 2;
            padding: 30px;
            overflow-y: auto;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-white);
            padding: 24px;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 16px;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card.success {
            border-left-color: var(--success-color);
        }

        .stat-card.warning {
            border-left-color: var(--warning-color);
        }

        .stat-card.info {
            border-left-color: var(--info-color);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--bg-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--primary-color);
        }

        .stat-card.success .stat-icon {
            color: var(--success-color);
        }

        .stat-card.warning .stat-icon {
            color: var(--warning-color);
        }

        .stat-card.info .stat-icon {
            color: var(--info-color);
        }

        .stat-info h3 {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 4px;
            font-weight: 500;
        }

        .stat-info p {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* Tables */
        .table-container {
            background: var(--bg-white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: var(--bg-light);
            border-bottom: 2px solid var(--border-color);
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-dark);
            font-size: 14px;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: var(--bg-light);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #0c4a6e;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Buttons */
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="date"],
        input[type="time"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="date"]:focus,
        input[type="time"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--bg-white);
            padding: 32px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: var(--text-dark);
        }

        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }

        /* Alerts */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background-color: #dcfce7;
            border-left: 4px solid var(--success-color);
            color: #166534;
        }

        .alert-danger {
            background-color: #fee2e2;
            border-left: 4px solid var(--danger-color);
            color: #991b1b;
        }

        .alert i {
            font-size: 18px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 8px;
        }

        /* Footer */
        .footer {
            grid-column: 1 / -1;
            grid-row: 3;
            background-color: var(--bg-white);
            border-top: 1px solid var(--border-color);
            padding: 20px 30px;
            text-align: center;
            color: var(--text-light);
            font-size: 13px;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-light);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-light);
        }

        /* Star Rating */
        .rating-input {
            display: flex;
            gap: 8px;
            font-size: 28px;
        }

        .star {
            cursor: pointer;
            color: #ddd;
            transition: all 0.2s ease;
        }

        .star:hover,
        .star.active {
            color: #fbbf24;
            transform: scale(1.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-wrapper {
                grid-template-columns: 1fr;
            }

            .sidebar {
                grid-column: 1;
                grid-row: auto;
                display: none;
            }

            .sidebar.show {
                display: block;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 999;
            }

            .header {
                grid-column: 1;
            }

            .main-content {
                grid-column: 1;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-graduation-cap"></i>
                <h2>StudentHub</h2>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <div class="nav-link active" onclick="showSection('dashboard', this)">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </div>
                </li>
                <li class="nav-item">
                    <div class="nav-link" onclick="showSection('events', this)">
                        <i class="fas fa-calendar-alt"></i>
                        <span>My Events</span>
                    </div>
                </li>
                <li class="nav-item">
                    <div class="nav-link" onclick="showSection('feedback', this)">
                        <i class="fas fa-star"></i>
                        <span>Feedback</span>
                    </div>
                </li>
                <li class="nav-item">
                    <div class="nav-link" onclick="showSection('profile', this)">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
                    </div>
                </li>
                <li class="nav-item">
                    <div class="nav-link" onclick="showSection('logout', this)" style="margin-top: 40px;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </div>
                </li>
            </ul>
        </aside>

        <!-- Header -->
        <header class="header">
            <h1 class="header-title">Student Dashboard</h1>
            <div class="header-user">
                <div class="user-avatar"><?php echo strtoupper(substr($student_name, 0, 1)); ?></div>
                <span><?php echo $student_name; ?></span>
                <a href="../users/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Section -->
            <section class="content-section active" id="dashboard-section">
                <h1 class="section-title" style="margin-bottom: 30px;">Dashboard Overview</h1>
                
                <div class="stats-grid">
                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Invited Events</h3>
                            <p><?php echo $invited_events; ?></p>
                        </div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Attended Events</h3>
                            <p><?php echo $attended_events; ?></p>
                        </div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Pending Feedback</h3>
                            <p><?php echo $pending_feedback; ?></p>
                        </div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Submitted Feedback</h3>
                            <p><?php echo $submitted_feedback; ?></p>
                        </div>
                    </div>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Events Section -->
            <section class="content-section" id="events-section">
                <div class="section-header">
                    <h1 class="section-title">My Invited Events</h1>
                </div>

                <?php
                $events_query = "
                    SELECT e.event_id, e.event_title, e.event_description, e.event_date, e.start_time, e.end_time, 
                           r.room_name, r.capacity, u.name as advisor_name
                    FROM events e
                    JOIN event_invitations ei ON e.event_id = ei.event_id
                    JOIN rooms r ON e.room_id = r.room_id
                    JOIN event_requests er ON e.request_id = er.request_id
                    JOIN users u ON er.advisor_id = u.user_id
                    WHERE ei.student_id = '$user_id'
                    ORDER BY e.event_date DESC
                ";
                
                $events_result = mysqli_query($con, $events_query);
                $events = mysqli_fetch_all($events_result, MYSQLI_ASSOC);
                ?>

                <?php if (count($events) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Event Title</th>
                                    <th>Date & Time</th>
                                    <th>Location</th>
                                    <th>Advisor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($event['event_title']); ?></strong>
                                            <br>
                                            <small style="color: var(--text-light);"><?php echo htmlspecialchars($event['event_description']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                            <br>
                                            <small><?php echo substr($event['start_time'], 0, 5) . ' - ' . substr($event['end_time'], 0, 5); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($event['room_name']); ?></td>
                                        <td><?php echo htmlspecialchars($event['advisor_name']); ?></td>
                                        <td>
                                            <?php
                                            $event_date = strtotime($event['event_date']);
                                            $today = strtotime('today');
                                            
                                            if ($event_date < $today) {
                                                // Check if feedback exists
                                                $feedback_check = mysqli_query($con, "SELECT feedback_id FROM feedback WHERE event_id = '{$event['event_id']}' AND student_id = '$user_id'");
                                                if (mysqli_num_rows($feedback_check) > 0) {
                                                    echo '<span class="badge badge-success">Feedback Given</span>';
                                                } else {
                                                    echo '<span class="badge badge-warning">Pending Feedback</span>';
                                                }
                                            } else {
                                                echo '<span class="badge badge-info">Upcoming</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No events invited yet</p>
                        <small>You will see events here once advisors invite you</small>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Feedback Section -->
            <section class="content-section" id="feedback-section">
                <div class="section-header">
                    <h1 class="section-title">Event Feedback</h1>
                    <button class="btn btn-primary" onclick="openFeedbackModal()">
                        <i class="fas fa-plus"></i> Add Feedback
                    </button>
                </div>

                <?php
                $feedback_query = "
                    SELECT f.feedback_id, f.event_id, f.rating, f.comments, f.submitted_at,
                           e.event_title, e.event_date, u.name as advisor_name
                    FROM feedback f
                    JOIN events e ON f.event_id = e.event_id
                    JOIN event_requests er ON e.request_id = er.request_id
                    JOIN users u ON er.advisor_id = u.user_id
                    WHERE f.student_id = '$user_id'
                    ORDER BY f.submitted_at DESC
                ";
                
                $feedback_result = mysqli_query($con, $feedback_query);
                $feedbacks = mysqli_fetch_all($feedback_result, MYSQLI_ASSOC);
                ?>

                <?php if (count($feedbacks) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Event Title</th>
                                    <th>Event Date</th>
                                    <th>Advisor</th>
                                    <th>Rating</th>
                                    <th>Comments</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($feedbacks as $feedback): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($feedback['event_title']); ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($feedback['event_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($feedback['advisor_name']); ?></td>
                                        <td>
                                            <div style="color: #fbbf24;">
                                                <?php echo str_repeat('★', $feedback['rating']) . str_repeat('☆', 5 - $feedback['rating']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($feedback['comments']); ?></td>
                                        <td>
                                            <small><?php echo date('M d, Y', strtotime($feedback['submitted_at'])); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-star"></i>
                        <p>No feedback submitted yet</p>
                        <small>Your feedback will appear here after you submit it</small>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Profile Section -->
            <section class="content-section" id="profile-section">
                <div class="section-header">
                    <h1 class="section-title">My Profile</h1>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Profile Information (Read-only) -->
                    <div class="table-container" style="padding: 0;">
                        <div style="padding: 24px; background-color: var(--bg-white);">
                            <h3 style="margin-bottom: 20px; font-weight: 700;">Profile Information</h3>
                            
                            <div class="form-group">
                                <label>Name</label>
                                <div style="padding: 12px 14px; background-color: var(--bg-light); border-radius: 6px; border: 1px solid var(--border-color);">
                                    <?php echo htmlspecialchars($student_name); ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <div style="padding: 12px 14px; background-color: var(--bg-light); border-radius: 6px; border: 1px solid var(--border-color);">
                                    <?php echo htmlspecialchars($student_email); ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Phone</label>
                                <div style="padding: 12px 14px; background-color: var(--bg-light); border-radius: 6px; border: 1px solid var(--border-color);">
                                    <?php echo htmlspecialchars($student_phone); ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Department</label>
                                <div style="padding: 12px 14px; background-color: var(--bg-light); border-radius: 6px; border: 1px solid var(--border-color);">
                                    <?php echo htmlspecialchars($student_department); ?>
                                </div>
                            </div>

                            <p style="font-size: 13px; color: var(--text-light); margin-top: 20px;">
                                <i class="fas fa-info-circle"></i> Profile information can only be updated by administrators
                            </p>
                        </div>
                    </div>

                    <!-- Change Password Form -->
                    <div class="table-container" style="padding: 0;">
                        <div style="padding: 24px; background-color: var(--bg-white);">
                            <h3 style="margin-bottom: 20px; font-weight: 700;">Change Password</h3>
                            
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="currentPassword">Current Password</label>
                                    <input type="password" id="currentPassword" name="currentPassword" required>
                                </div>

                                <div class="form-group">
                                    <label for="newPassword">New Password</label>
                                    <input type="password" id="newPassword" name="newPassword" required>
                                </div>

                                <div class="form-group">
                                    <label for="confirmPassword">Confirm Password</label>
                                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                                </div>

                                <div style="display: flex; gap: 12px;">
                                    <button type="submit" name="updatePassword" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Password
                                    </button>
                                    <button type="reset" class="btn" style="background-color: var(--bg-light); color: var(--text-dark);">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Logout Handler -->
        <script>
            // Check for logout action
            document.addEventListener('DOMContentLoaded', function() {
                const logoutSection = document.getElementById('logout-section');
                if (logoutSection && logoutSection.classList.contains('active')) {
                    window.location.href = '../users/logout.php';
                }
            });
        </script>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2025 EventHub - Student Dashboard. All rights reserved. | khodor hotait  </p>
        </footer>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add Event Feedback</h2>
                <button type="button" class="close-btn" onclick="closeFeedbackModal()">&times;</button>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="feedbackEvent">Select Event *</label>
                    <select id="feedbackEvent" name="feedbackEvent" required onchange="checkExistingFeedback()">
                        <option value="">-- Choose an event --</option>
                        <?php
                        $past_events_query = "
                            SELECT e.event_id, e.event_title, e.event_date
                            FROM events e
                            JOIN event_invitations ei ON e.event_id = ei.event_id
                            LEFT JOIN feedback f ON e.event_id = f.event_id AND f.student_id = '$user_id'
                            WHERE ei.student_id = '$user_id' AND e.event_date < CURDATE()
                            ORDER BY e.event_date DESC
                        ";
                        
                        $past_events_result = mysqli_query($con, $past_events_query);
                        while ($event = mysqli_fetch_assoc($past_events_result)):
                        ?>
                            <option value="<?php echo $event['event_id']; ?>">
                                <?php echo htmlspecialchars($event['event_title']) . ' (' . date('M d, Y', strtotime($event['event_date'])) . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Rating *</label>
                    <div class="rating-input" id="ratingInput">
                        <span class="star" onclick="setRating(1)">★</span>
                        <span class="star" onclick="setRating(2)">★</span>
                        <span class="star" onclick="setRating(3)">★</span>
                        <span class="star" onclick="setRating(4)">★</span>
                        <span class="star" onclick="setRating(5)">★</span>
                    </div>
                    <input type="hidden" id="feedbackRating" name="feedbackRating" value="0">
                </div>

                <div class="form-group">
                    <label for="feedbackComments">Comments *</label>
                    <textarea id="feedbackComments" name="feedbackComments" required placeholder="Share your feedback about the event..."></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn" style="background-color: var(--bg-light); color: var(--text-dark);" onclick="closeFeedbackModal()">Cancel</button>
                    <button type="submit" name="submitFeedback" class="btn btn-primary">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedRating = 0;

        // Show/Hide Sections
        function showSection(sectionName, element) {
            if (sectionName === 'logout') {
                window.location.href = '../users/logout.php';
                return;
            }

            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });

            // Show selected section
            document.getElementById(sectionName + '-section').classList.add('active');

            // Update active nav link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            element.classList.add('active');

            // Scroll to top
            document.querySelector('.main-content').scrollTop = 0;
        }

        // Feedback Modal Functions
        function openFeedbackModal() {
            document.getElementById('feedbackModal').classList.add('show');
        }

        function closeFeedbackModal() {
            document.getElementById('feedbackModal').classList.remove('show');
            resetFeedbackForm();
        }

        function resetFeedbackForm() {
            document.getElementById('feedbackComments').value = '';
            selectedRating = 0;
            document.getElementById('feedbackRating').value = '0';
            document.querySelectorAll('.star').forEach(star => {
                star.classList.remove('active');
            });
        }

        function setRating(rating) {
            selectedRating = rating;
            document.getElementById('feedbackRating').value = rating;
            
            document.querySelectorAll('.star').forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        function checkExistingFeedback() {
            const eventId = document.getElementById('feedbackEvent').value;
            if (eventId) {
                // This could be enhanced with AJAX to check if feedback exists
                console.log('Event selected: ' + eventId);
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('feedbackModal');
            if (event.target === modal) {
                closeFeedbackModal();
            }
        }

        // Close alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);
            });
        });
    </script>
</body>
</html>
