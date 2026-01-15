<?php
session_start();
include '../database/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../users/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Event Request Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accept Event Request
    if (isset($_POST['acceptRequest'])) {
        $request_id = mysqli_real_escape_string($con, $_POST['requestId']);
        $admin_comment = mysqli_real_escape_string($con, $_POST['adminComment']);
        
        // Get event request details
        $request_query = mysqli_query($con, "SELECT * FROM event_requests WHERE request_id = '$request_id'");
        $request = mysqli_fetch_assoc($request_query);
        
        if ($request) {
            // Update request status to accepted
            $update_query = "UPDATE event_requests SET status = 'approved', admin_comment = '$admin_comment' WHERE request_id = '$request_id'";
            
            if (mysqli_query($con, $update_query)) {
                // Create new event record
                $event_title = mysqli_real_escape_string($con, $request['event_title']);
                $event_description = mysqli_real_escape_string($con, $request['event_description']);
                $event_date = $request['event_date'];
                $start_time = $request['start_time'];
                $end_time = $request['end_time'];
                $room_id = $request['room_id'];
                $approved_by = $user_id;
                
                $create_event_query = "INSERT INTO events (request_id, room_id, event_title, event_description, event_date, start_time, end_time, approved_by) 
                                      VALUES ('$request_id', '$room_id', '$event_title', '$event_description', '$event_date', '$start_time', '$end_time', '$approved_by')";
                
                if (mysqli_query($con, $create_event_query)) {
                    echo "<script>alert('Event request accepted successfully!'); window.location.href = 'admin_dashboard.php';</script>";
                } else {
                    echo "<script>alert('Request accepted but error creating event: " . mysqli_error($con) . "');</script>";
                }
            } else {
                echo "<script>alert('Error: Could not accept request. " . mysqli_error($con) . "');</script>";
            }
        }
    }
    
    // Decline Event Request
    if (isset($_POST['declineRequest'])) {
        $request_id = mysqli_real_escape_string($con, $_POST['requestId']);
        $admin_comment = mysqli_real_escape_string($con, $_POST['adminComment']);
        
        $update_query = "UPDATE event_requests SET status = 'declined', admin_comment = '$admin_comment' WHERE request_id = '$request_id'";
        
        if (mysqli_query($con, $update_query)) {
            echo "<script>alert('Event request declined!'); window.location.href = 'admin_dashboard.php';</script>";
        } else {
            echo "<script>alert('Error: Could not decline request. " . mysqli_error($con) . "');</script>";
        }
    }
    
    // Update Feedback Status
    if (isset($_POST['updateFeedbackStatus'])) {
        $feedback_id = mysqli_real_escape_string($con, $_POST['feedbackId']);
        $feedback_status = mysqli_real_escape_string($con, $_POST['feedbackStatus']);
        
        $update_query = "UPDATE feedback SET status = '$feedback_status' WHERE feedback_id = '$feedback_id'";
        
        if (mysqli_query($con, $update_query)) {
            echo "<script>alert('Feedback status updated successfully!'); window.location.href = 'admin_dashboard.php';</script>";
        } else {
            echo "<script>alert('Error: Could not update feedback status. " . mysqli_error($con) . "');</script>";
        }
    }
}

// Handle AJAX Request for Request Details
if (isset($_GET['getRequestDetails'])) {
    header('Content-Type: application/json');
    $request_id = mysqli_real_escape_string($con, $_GET['getRequestDetails']);
    
    $query = mysqli_query($con, "
        SELECT er.*, u.name as advisor_name, r.room_name
        FROM event_requests er
        JOIN users u ON er.advisor_id = u.user_id
        LEFT JOIN rooms r ON er.room_id = r.room_id
        WHERE er.request_id = '$request_id'
    ");
    
    if (mysqli_num_rows($query) > 0) {
        $request = mysqli_fetch_assoc($query);
        echo json_encode([
            'success' => true,
            'request' => $request
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Request not found'
        ]);
    }
    exit();
}

// Handle AJAX Request for Event Feedbacks
if (isset($_GET['getEventFeedbacks'])) {
    header('Content-Type: application/json');
    $event_id = mysqli_real_escape_string($con, $_GET['getEventFeedbacks']);
    
    $query = mysqli_query($con, "
        SELECT f.*, u.name as student_name
        FROM feedback f
        JOIN users u ON f.student_id = u.user_id
        WHERE f.event_id = '$event_id'
        ORDER BY f.submitted_at DESC
    ");
    
    $feedbacks = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $feedbacks[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'feedbacks' => $feedbacks
    ]);
    exit();
}

// Fetch admin statistics
$total_users = mysqli_num_rows(mysqli_query($con, "SELECT * FROM users"));
$total_events = mysqli_num_rows(mysqli_query($con, "SELECT * FROM events"));
$pending_requests = mysqli_num_rows(mysqli_query($con, "SELECT * FROM event_requests WHERE status = 'pending'"));
$total_feedbacks = mysqli_num_rows(mysqli_query($con, "SELECT * FROM feedback"));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Admin Dashboard - Event Management System">
    <meta name="author" content="Mahdi Saleh">
    <title>Admin Dashboard - EventHub</title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="shortcut icon" href="../assets/images/event_system.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200;0,400;0,600;0,700;1,200;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body>
    <?php
    $user_id = $_SESSION['user_id'];
    $user_query = mysqli_query($con, "SELECT * FROM users WHERE user_id = '$user_id'");
    $user = mysqli_fetch_assoc($user_query);
    $user_name = $user ? $user['name'] : 'Admin';
    ?>
    <div class="dashboard-wrapper">
        <!-- ======================== SIDEBAR ======================== -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-calendar-alt"></i>
                <span>EventHub</span>
            </div>

            <ul class="sidebar-menu">
                <li><a href="#" class="active" onclick="showSection('dashboard')"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#" onclick="showSection('users')"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="#" onclick="showSection('requests')"><i class="fas fa-inbox"></i> Requests</a></li>
                <li><a href="#" onclick="showSection('events')"><i class="fas fa-calendar-check"></i> Events</a></li>
                <li><a href="#" onclick="showSection('feedbacks')"><i class="fas fa-comments"></i> Feedbacks</a></li>
                <li><a href="#" onclick="showSection('rooms')"><i class="fas fa-door-open"></i> Rooms</a></li>
                <!--<li><a href="#" onclick="showSection('notifications')"><i class="fas fa-bell"></i> Notifications</a></li>-->
            </ul>

            <div class="sidebar-footer">
                <a href="../users/logout.php" style="display: flex; align-items: center; gap: 12px; color: rgba(255, 255, 255, 0.8); text-decoration: none; border-radius: 10px; padding: 12px 16px; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.color='white';" onmouseout="this.style.background=''; this.style.color='rgba(255, 255, 255, 0.8)';">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- ======================== HEADER ======================== -->
        <header class="header">
            <div class="header-title">Dashboard</div>
            <div class="header-right">
                <button class="notifications-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">0</span>
                </button>
                <div class="user-profile">
                    <div class="user-avatar"><?php echo substr($user_name, 0, 1); ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo ucfirst($user_name); ?></div>
                        <div style="font-size: 12px; opacity: 0.8;">Administrator</div>
                    </div>
                </div>
                <a href="../users/logout.php" class="logout-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </header>

        <!-- ======================== MAIN CONTENT ======================== -->
        <main class="main-content">
            <!-- Dashboard Section -->
            <section id="dashboard-section">
                <div class="content-header">
                    <h1 class="content-title">Welcome, Admin! 👋</h1>
                    <p class="content-subtitle">Here's an overview of your event management system</p>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card users">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                    </div>

                    <div class="stat-card events">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-label">Total Events</div>
                        <div class="stat-value"><?php echo $total_events; ?></div>
                    </div>

                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="stat-label">Pending Requests</div>
                        <div class="stat-value"><?php echo $pending_requests; ?></div>
                    </div>

                    <div class="stat-card feedbacks">
                        <div class="stat-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-label">Total Feedbacks</div>
                        <div class="stat-value"><?php echo $total_feedbacks; ?></div>
                    </div>
                </div>

                <!-- Action Cards -->
                <div style="margin-bottom: 30px;">
                    <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px; color: var(--text-dark);">Quick Actions</h2>
                </div>

                <div class="actions-grid">
                    <div class="action-card" onclick="openModal('addUserModal')">
                        <div class="action-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="action-title">Add User</div>
                        <div class="action-description">Create new user account</div>
                    </div>

                    <div class="action-card" onclick="showSection('users')">
                        <div class="action-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="action-title">View Users</div>
                        <div class="action-description">Check all user accounts</div>
                    </div>

                    <div class="action-card" onclick="showSection('requests')">
                        <div class="action-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="action-title">Event Requests</div>
                        <div class="action-description">Review pending requests</div>
                    </div>

                    <div class="action-card" onclick="showSection('events')">
                        <div class="action-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="action-title">Events List</div>
                        <div class="action-description">View accepted/declined events</div>
                    </div>

                    <div class="action-card" onclick="showSection('feedbacks')">
                        <div class="action-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="action-title">Feedbacks</div>
                        <div class="action-description">Review user feedbacks</div>
                    </div>

                    <div class="action-card" onclick="showSection('rooms')">
                        <div class="action-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="action-title">Manage Rooms</div>
                        <div class="action-description">Room information & settings</div>
                    </div>
                </div>
            </section>

            <!-- Users Section -->
            <style>
                .users-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 14px;
                }
                .room-table{
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 14px;
                }
                .room-table thead {
                    background-color: var(--bg-light);
                    border-bottom: 2px solid var(--border-color);
                }
                .room-table th {
                    padding: 12px 16px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--text-dark);
                }
                .room-table td {
                    padding: 12px 16px;
                    border-bottom: 1px solid var(--border-color);
                    color: var(--text-dark);
                }
                .room-table tbody tr:hover {
                    background-color: rgba(0, 0, 0, 0.02);
                }
                .users-table thead {
                    background-color: var(--bg-light);
                    border-bottom: 2px solid var(--border-color);
                }

                .users-table th {
                    padding: 12px 16px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--text-dark);
                }

                .users-table td {
                    padding: 12px 16px;
                    border-bottom: 1px solid var(--border-color);
                    color: var(--text-dark);
                }

                .users-table tbody tr:hover {
                    background-color: rgba(0, 0, 0, 0.02);
                }

                .users-table .btn {
                    margin-right: 8px;
                }

                .btn-sm {
                    padding: 6px 12px;
                    font-size: 12px;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .btn-secondary {
                    background-color: green;
                    color: white;
                }

                .btn-secondary:hover {
                    opacity: 0.8;
                }

                .btn-danger {
                    background-color: #dc3545;
                    color: white;
                }

                .btn-danger:hover {
                    opacity: 0.8;
                }
            </style>
            <section id="users-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">Manage Users</h1>
                    <p class="content-subtitle">Check user accounts and add new users</p>
                </div>
                <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 30px;">
                    <button class="btn btn-primary" onclick="openModal('addUserModal')">
                        <i class="fas fa-user-plus"></i> Add New User
                    </button>
                    <div style="margin-left: auto; display: flex; align-items: center; background: var(--bg-white); padding: 8px 12px; border-radius: 10px; box-shadow: var(--shadow-sm);">
                        <label for="roleFilter" style="margin-right: 10px;">Filter by Role:</label>
                        <select id="roleFilter" onchange="filterUsersByRole()" style="padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border-color);">
                            <option value="">All</option>
                            <option value="admin">Admin</option>
                            <option value="advisor">Advisor</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 30px; background: var(--bg-white); padding: 24px; border-radius: 15px; box-shadow: var(--shadow-md);">
                    <?php
                    // Fetch users from database
                    $users_query = mysqli_query($con, "SELECT * FROM users");
                    if (mysqli_num_rows($users_query) > 0) {
                        echo '<table class="users-table">
                                    <thead>
                                        <tr>
                                            <th>User ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Department</th>
                                            <th>Role</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                        while ($user = mysqli_fetch_assoc($users_query)) {
                            echo '<tr>
                                        <td>' . htmlspecialchars($user['user_id']) . '</td>
                                        <td>' . htmlspecialchars($user['name']) . '</td>
                                        <td>' . htmlspecialchars($user['email_address']) . '</td>
                                        <td>' . htmlspecialchars($user['phone']) . '</td>
                                        <td>' . htmlspecialchars(ucfirst($user['department'])) . '</td>
                                        <td>' . htmlspecialchars(ucfirst($user['role'])) . '</td>
                                      </tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p style="text-align: center; color: var(--text-light);">No users found.</p>';
                    }
                    ?>
                </div>
            </section>

            <!-- Requests Section -->
            <section id="requests-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">Event Requests</h1>
                    <p class="content-subtitle">Review and manage event requests from advisors</p>
                </div>

                <!-- Pending Requests Section -->
                <div style="margin-bottom: 40px;">
                    <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px; color: var(--text-dark);">
                        <i class="fas fa-hourglass-half" style="color: var(--warning-color); margin-right: 10px;"></i>
                        Pending Requests
                    </h2>
                    <div style="background: var(--bg-white); border-radius: 15px; box-shadow: var(--shadow-md); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--bg-light); border-bottom: 2px solid var(--border-color);">
                                    <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-dark);">Event Title</th>
                                    <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-dark);">Advisor</th>
                                    <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-dark);">Date & Time</th>
                                    <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-dark);">Room</th>
                                    <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-dark);">Guests</th>
                                    <th style="padding: 15px; text-align: center; font-weight: 600; color: var(--text-dark);">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pending_query = mysqli_query($con, "
                                    SELECT er.*, u.name as advisor_name, u.email_address, r.room_name 
                                    FROM event_requests er
                                    JOIN users u ON er.advisor_id = u.user_id
                                    LEFT JOIN rooms r ON er.room_id = r.room_id
                                    WHERE er.status = 'pending'
                                    ORDER BY er.created_at DESC
                                ");
                                
                                if (mysqli_num_rows($pending_query) > 0) {
                                    while ($req = mysqli_fetch_assoc($pending_query)) {
                                        echo "
                                        <tr style=\"border-bottom: 1px solid var(--border-color);\">
                                            <td style=\"padding: 15px;\">
                                                <strong>" . $req['event_title'] . "</strong><br>
                                                <small style=\"color: var(--text-light);\">" . substr($req['event_description'], 0, 50) . "...</small>
                                            </td>
                                            <td style=\"padding: 15px;\">
                                                " . ucfirst($req['advisor_name']) . "<br>
                                                <small style=\"color: var(--text-light);\">" . $req['email_address'] . "</small>
                                            </td>
                                            <td style=\"padding: 15px;\">
                                                " . date('M d, Y', strtotime($req['event_date'])) . "<br>
                                                <small style=\"color: var(--text-light);\">" . date('H:i', strtotime($req['start_time'])) . " - " . date('H:i', strtotime($req['end_time'])) . "</small>
                                            </td>
                                            <td style=\"padding: 15px;\">" . ($req['room_name'] ? $req['room_name'] : 'N/A') . "</td>
                                            <td style=\"padding: 15px;\"><strong>" . $req['expected_guests'] . "</strong></td>
                                            <td style=\"padding: 15px; text-align: center;\">
                                                <button class='btn-small btn-view' onclick=\"openReviewModal('" . $req['request_id'] . "')\"><i class='fas fa-eye'></i> Review</button>
                                            </td>
                                        </tr>
                                        ";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='padding: 30px; text-align: center; color: var(--text-light);'>No pending requests</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Declined Requests Section -->
                <div>
                    <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px; color: var(--text-dark);">
                        <i class="fas fa-times-circle" style="color: var(--danger-color); margin-right: 10px;"></i>
                        Declined Requests
                    </h2>
                    <div style="background: var(--bg-white); border-radius: 15px; box-shadow: var(--shadow-md); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--bg-light); border-bottom: 2px solid var(--border-color);">
                                    <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-dark);">Event Title</th>
                                    <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-dark);">Advisor</th>
                                    <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-dark);">Date</th>
                                    <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-dark);">Admin Comment</th>
                                    <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-dark);">Declined At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $declined_query = mysqli_query($con, "
                                    SELECT er.*, u.name as advisor_name, u.email_address
                                    FROM event_requests er
                                    JOIN users u ON er.advisor_id = u.user_id
                                    WHERE er.status = 'declined'
                                    ORDER BY er.created_at DESC
                                ");
                                
                                if (mysqli_num_rows($declined_query) > 0) {
                                    while ($req = mysqli_fetch_assoc($declined_query)) {
                                        echo "
                                        <tr style=\"border-bottom: 1px solid var(--border-color);\">
                                            <td style=\"padding: 15px;\">
                                                <strong>" . $req['event_title'] . "</strong><br>
                                                <small style=\"color: var(--text-light);\">" . substr($req['event_description'], 0, 50) . "...</small>
                                            </td>
                                            <td style=\"padding: 15px;\">
                                                " . ucfirst($req['advisor_name']) . "<br>
                                                <small style=\"color: var(--text-light);\">" . $req['email_address'] . "</small>
                                            </td>
                                            <td style=\"padding: 15px;\">" . date('M d, Y', strtotime($req['event_date'])) . "</td>
                                            <td style=\"padding: 15px;\">" . ($req['admin_comment'] ? $req['admin_comment'] : '<em style=\"color: var(--text-light);\">No comment</em>') . "</td>
                                            <td style=\"padding: 15px;\">" . date('M d, Y H:i', strtotime($req['created_at'])) . "</td>
                                        </tr>
                                        ";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' style='padding: 30px; text-align: center; color: var(--text-light);'>No declined requests</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Events Section -->
            <section id="events-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">Approved Events</h1>
                    <p class="content-subtitle">View all approved events</p>
                </div>
                <div style="margin-top: 30px; background: var(--bg-white); padding: 24px; border-radius: 15px; box-shadow: var(--shadow-md);">
                    <?php
                    $events_query = "
                        SELECT e.event_id, e.event_title, e.event_description, e.event_date, e.start_time, e.end_time, 
                               r.room_name, r.capacity, u.name as advisor_name,
                               COUNT(ei.invitation_id) as invitation_count
                        FROM events e
                        JOIN rooms r ON e.room_id = r.room_id
                        JOIN event_requests er ON e.request_id = er.request_id
                        JOIN users u ON er.advisor_id = u.user_id
                        LEFT JOIN event_invitations ei ON e.event_id = ei.event_id
                        GROUP BY e.event_id
                        ORDER BY e.event_date DESC
                    ";
                    
                    $events_result = mysqli_query($con, $events_query);
                    
                    if (mysqli_num_rows($events_result) > 0) {
                        echo '<table class="events-table" style="width: 100%; border-collapse: collapse;">';
                        echo '<thead style="background-color: var(--bg-light); border-bottom: 2px solid var(--border-color);">';
                        echo '<tr>';
                        echo '<th style="padding: 12px; text-align: left; font-weight: 600;">Event Title</th>';
                        echo '<th style="padding: 12px; text-align: left; font-weight: 600;">Date & Time</th>';
                        echo '<th style="padding: 12px; text-align: left; font-weight: 600;">Location</th>';
                        echo '<th style="padding: 12px; text-align: left; font-weight: 600;">Capacity</th>';
                        echo '<th style="padding: 12px; text-align: left; font-weight: 600;">Advisor</th>';
                        echo '<th style="padding: 12px; text-align: center; font-weight: 600;">Invitation Status</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        while ($event = mysqli_fetch_assoc($events_result)) {
                            echo '<tr style="border-bottom: 1px solid var(--border-color);">';
                            echo '<td style="padding: 12px;"><strong>' . htmlspecialchars($event['event_title']) . '</strong><br><small style="color: var(--text-light);">' . htmlspecialchars(substr($event['event_description'], 0, 50)) . (strlen($event['event_description']) > 50 ? '...' : '') . '</small></td>';
                            echo '<td style="padding: 12px;">' . date('M d, Y', strtotime($event['event_date'])) . '<br><small>' . substr($event['start_time'], 0, 5) . ' - ' . substr($event['end_time'], 0, 5) . '</small></td>';
                            echo '<td style="padding: 12px;">' . htmlspecialchars($event['room_name']) . '</td>';
                            echo '<td style="padding: 12px;"><strong>' . htmlspecialchars($event['capacity']) . '</strong> seats</td>';
                            echo '<td style="padding: 12px;">' . htmlspecialchars($event['advisor_name']) . '</td>';
                            
                            // Invitation Status Column
                            if ($event['invitation_count'] > 0) {
                                echo '<td style="padding: 12px; text-align: center;"><span style="background-color: #10b981; color: white; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block;">✓ Invited (' . $event['invitation_count'] . ')</span></td>';
                            } else {
                                echo '<td style="padding: 12px; text-align: center;"><span style="background-color: #ef4444; color: white; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block;">✗ Not Invited</span></td>';
                            }
                            
                            echo '</tr>';
                        }
                        
                        echo '</tbody></table>';
                    } else {
                        echo '<p style="text-align: center; color: var(--text-light);">No approved events yet.</p>';
                    }
                    ?>
                </div>
            </section>

            <!-- Feedbacks Section -->
            <section id="feedbacks-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">Event Feedbacks</h1>
                    <p class="content-subtitle">Review feedbacks from passed events</p>
                </div>
                <div style="margin-top: 30px; background: var(--bg-white); padding: 24px; border-radius: 15px; box-shadow: var(--shadow-md);">
                    <?php
                    $passed_events_query = "
                        SELECT DISTINCT e.event_id, e.event_title, e.event_date, u.name as advisor_name,
                               COUNT(f.feedback_id) as feedback_count
                        FROM events e
                        JOIN event_requests er ON e.request_id = er.request_id
                        JOIN users u ON er.advisor_id = u.user_id
                        LEFT JOIN feedback f ON e.event_id = f.event_id
                        WHERE e.event_date < CURDATE()
                        GROUP BY e.event_id
                        ORDER BY e.event_date DESC
                    ";
                    
                    $passed_events_result = mysqli_query($con, $passed_events_query);
                    
                    if (mysqli_num_rows($passed_events_result) > 0) {
                        echo '<table class="feedbacks-table" style="width: 100%; border-collapse: collapse;">';
                        echo '<thead style="background-color: var(--bg-light); border-bottom: 2px solid var(--border-color);">';
                        echo '<tr>';
                        echo '<th style="padding: 12px; text-align: left; font-weight: 600;">Event Title</th>';
                        echo '<th style="padding: 12px; text-align: left; font-weight: 600;">Event Date</th>';
                        echo '<th style="padding: 12px; text-align: left; font-weight: 600;">Advisor</th>';
                        echo '<th style="padding: 12px; text-align: center; font-weight: 600;">Feedback Count</th>';
                        echo '<th style="padding: 12px; text-align: center; font-weight: 600;">Action</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        while ($event = mysqli_fetch_assoc($passed_events_result)) {
                            echo '<tr style="border-bottom: 1px solid var(--border-color);">';
                            echo '<td style="padding: 12px;"><strong>' . htmlspecialchars($event['event_title']) . '</strong></td>';
                            echo '<td style="padding: 12px;">' . date('M d, Y', strtotime($event['event_date'])) . '</td>';
                            echo '<td style="padding: 12px;">' . htmlspecialchars($event['advisor_name']) . '</td>';
                            echo '<td style="padding: 12px; text-align: center;"><span style="background-color: var(--bg-light); padding: 6px 12px; border-radius: 20px; font-weight: 600;">' . $event['feedback_count'] . '</span></td>';
                            echo '<td style="padding: 12px; text-align: center;">';
                            echo '<button class="btn" style="background-color: var(--primary-color); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;" onclick="openFeedbacksModal(' . $event['event_id'] . ', \'' . htmlspecialchars($event['event_title']) . '\')">';
                            echo '<i class="fas fa-eye"></i> View Feedbacks';
                            echo '</button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody></table>';
                    } else {
                        echo '<p style="text-align: center; color: var(--text-light);">No passed events with feedbacks yet.</p>';
                    }
                    ?>
                </div>
            </section>

            <!-- Rooms Section -->
            <section id="rooms-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">Room Management</h1>
                    <p class="content-subtitle">Manage room information and details</p>
                </div>
                <button class="btn btn-primary" onclick="openModal('addRoomModal')">
                    <i class="fas fa-door-open"></i> Add Room
                </button>
                <div style="margin-top: 30px; background: var(--bg-white); padding: 24px; border-radius: 15px; box-shadow: var(--shadow-md);">
                    <!--Displaying room information will be displayed here-->
                    <?php
                    $room_query = "SELECT * FROM rooms";
                    $room_result = mysqli_query($con, $room_query);

                    if (mysqli_num_rows($room_result) > 0) {
                        echo '<table class="room-table">';
                        echo '<thead><tr><th>Room ID</th><th>Room Name</th><th>Capacity</th><th>Location</th><th>Description</th></tr></thead>';
                        echo '<tbody>';
                        while ($row = mysqli_fetch_assoc($room_result)) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['room_id']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['room_name']) . '</td>';
                            echo '<td><strong>' . htmlspecialchars($row['capacity']) . '</strong></td>';
                            echo '<td>' . htmlspecialchars($row['location']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['description']) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p style="text-align: center; color: var(--text-light);">No rooms found.</p>';
                    }
                    ?>
                </div>
            </section>

            <!-- Notifications Section 
            <section id="notifications-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">Notifications</h1>
                    <p class="content-subtitle">System notifications and alerts</p>
                </div>
                <div style="margin-top: 30px; background: var(--bg-white); padding: 24px; border-radius: 15px; box-shadow: var(--shadow-md);">
                    <p style="text-align: center; color: var(--text-light);">Notifications will be displayed here</p>
                </div>
            </section>-->
        </main>

        <!-- ======================== FOOTER ======================== -->
        <footer class="footer">
            <p>&copy; 2025 EventHub Admin Dashboard. All rights reserved.</p>
        </footer>
    </div>

    <!-- ======================== MODALS ======================== -->
    <!-- Add User Modal -->
    <?php
    $add_user_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addUser'])) {
        $name = mysqli_real_escape_string($con, $_POST['userName']);
        $email = mysqli_real_escape_string($con, $_POST['userEmail']);
        $role = mysqli_real_escape_string($con, $_POST['userRole']);
        $phone = mysqli_real_escape_string($con, $_POST['userPhone']);
        $department = mysqli_real_escape_string($con, $_POST['userDepartment']);
        $password = $_POST['userPassword'];

        // Validate inputs
        if (empty($name) || empty($email) || empty($role) || empty($phone) || empty($department) || empty($password)) {
            $add_user_message = "<script>alert('All fields are required.');</script>";
        } else {
            // Check if email already exists
            $check_query = "SELECT * FROM users WHERE email_address = '$email'";
            $check_result = mysqli_query($con, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $add_user_message = "<script>alert('Error: Email address already exists.');</script>";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $insert_query = "INSERT INTO users (name, email_address, phone, role, department, password) VALUES ('$name', '$email', '$phone', '$role', '$department', '$password')";

                if (mysqli_query($con, $insert_query)) {
                    $add_user_message = "<script>alert('New user added successfully!'); window.location.href = 'admin_dashboard.php';</script>";
                } else {
                    $add_user_message = "<script>alert('Error: Could not add user. " . mysqli_error($con) . "');</script>";
                }
            }
        }
    }
    echo $add_user_message;
    ?>
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <form action="admin_dashboard.php" method="POST" id="addUserForm">
                <div class="modal-header">
                    <h2>Add New User</h2>
                    <button class="close-btn" onclick="closeModal('addUserModal')">&times;</button>
                </div>
                <div class="modal-body">

                    <div class="form-group">
                        <label for="userName">Full Name</label>
                        <input type="text" id="userName" name="userName" placeholder="Enter full name" required>
                    </div>
                    <div class="form-group">
                        <label for="userEmail">Email Address</label>
                        <input type="email" id="userEmail" name="userEmail" placeholder="Enter email" required>
                    </div>
                    <div class="form-group">
                        <label for="userPhone">Phone Number</label>
                        <input type="text" id="userPhone" name="userPhone" placeholder="Enter phone number" required>
                    </div>
                    <div class="form-group">
                        <label for="userRole">Role</label>
                        <select id="userRole" name="userRole" required>
                            <option value="">Select Role</option>
                            <option value="student">Student</option>
                            <option value="advisor">Advisor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="userDepartment">Department</label>
                        <input type="text" id="userDepartment" name="userDepartment" placeholder="Enter department" required>
                    </div>
                    <div class="form-group">
                        <label for="userPassword">Password</label>
                        <input type="password" id="userPassword" name="userPassword" placeholder="Enter password" required>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                    <button type="submit" form="addUserForm" class="btn btn-primary" name="addUser">Add User</button>
                </div>
            </form>
        </div>
    </div>
    <?php
    $add_room_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addRoom'])) {
        $room_name = mysqli_real_escape_string($con, $_POST['roomName']);
        $room_capacity = mysqli_real_escape_string($con, $_POST['roomCapacity']);
        $room_location = mysqli_real_escape_string($con, $_POST['roomLocation']);
        $room_description = mysqli_real_escape_string($con, $_POST['roomDescription']);

        // Validate inputs
        if (empty($room_name) || empty($room_capacity) || empty($room_location)) {
            $add_room_message = "<script>alert('Room name, capacity, and location are required.');</script>";
        } else {
            // Insert new room
            $insert_query = "INSERT INTO rooms (room_name, capacity, location, description) VALUES ('$room_name', '$room_capacity', '$room_location', '$room_description')";

            if (mysqli_query($con, $insert_query)) {
                $add_room_message = "<script>alert('New room added successfully!'); window.location.href = 'admin_dashboard.php';</script>";
            } else {
                $add_room_message = "<script>alert('Error: Could not add room. " . mysqli_error($con) . "');</script>";
            }
        }
    }
    echo $add_room_message;
    ?>
    <!-- Add Room Modal -->
    <div id="addRoomModal" class="modal">
        <div class="modal-content">
            <form action="admin_dashboard.php" method="POST" id="addRoomForm">
                <div class="modal-header">
                    <h2>Add New Room</h2>
                    <button class="close-btn" onclick="closeModal('addRoomModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="roomName">Room Name</label>
                        <input type="text" id="roomName" name="roomName" placeholder="Enter room name" required>
                    </div>
                    <div class="form-group">
                        <label for="roomCapacity">Capacity</label>
                        <input type="number" id="roomCapacity" name="roomCapacity" placeholder="Enter capacity" required>
                    </div>
                    <div class="form-group">
                        <label for="roomLocation">Location</label>
                        <input type="text" id="roomLocation" name="roomLocation" placeholder="Enter room location" required>
                    </div>
                    <div class="form-group">
                        <label for="roomDescription">Description</label>
                        <textarea id="roomDescription" name="roomDescription" placeholder="Enter room description" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addRoomModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="addRoom">Add Room</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Review Event Request Modal -->
    <div id="reviewRequestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Review Event Request</h2>
                <button class="close-btn" onclick="closeModal('reviewRequestModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="requestDetailsContainer" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px solid var(--border-color);"></div>
                
                <div style="margin-bottom: 20px;">
                    <h3 style="font-weight: 700; margin-bottom: 15px; color: var(--text-dark);">Decision</h3>
                    
                    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <button class="btn btn-primary" onclick="showAcceptForm()" style="flex: 1;">
                            <i class="fas fa-check"></i> Accept Request
                        </button>
                        <button class="btn btn-delete" onclick="showDeclineForm()" style="flex: 1; background: var(--danger-color);">
                            <i class="fas fa-times"></i> Decline Request
                        </button>
                    </div>
                </div>

                <!-- Accept Form -->
                <form id="acceptForm" action="admin_dashboard.php" method="POST" style="display: none;">
                    <input type="hidden" id="acceptRequestId" name="requestId" value="">
                    <div class="form-group">
                        <label for="acceptComment">Admin Comment</label>
                        <textarea id="acceptComment" name="adminComment" placeholder="Add optional comment for advisor" rows="3"></textarea>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="hideAcceptForm()" style="flex: 1;">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="acceptRequest" style="flex: 1;">Confirm Accept</button>
                    </div>
                </form>

                <!-- Decline Form -->
                <form id="declineForm" action="admin_dashboard.php" method="POST" style="display: none;">
                    <input type="hidden" id="declineRequestId" name="requestId" value="">
                    <div class="form-group">
                        <label for="declineComment">Decline Reason (Required)</label>
                        <textarea id="declineComment" name="adminComment" placeholder="Explain why you are declining this request" rows="3" required></textarea>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="hideDeclineForm()" style="flex: 1;">Cancel</button>
                        <button type="submit" class="btn btn-delete" name="declineRequest" style="flex: 1; background: var(--danger-color);">Confirm Decline</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Feedbacks Modal -->
    <div id="feedbacksModal" class="modal">
        <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h2>Event Feedbacks - <span id="eventTitleInModal"></span></h2>
                <button class="close-btn" onclick="closeModal('feedbacksModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="feedbacksContainer" style="margin-bottom: 20px;">
                    <!-- Feedbacks will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Store current request data
        let currentRequest = null;

        // Open review modal with request details
        function openReviewModal(requestId) {
            // Fetch request details using AJAX or get from data attribute
            // For now, we'll reload and pass via data
            fetch('admin_dashboard.php?getRequestDetails=' + requestId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentRequest = data.request;
                        displayRequestDetails(data.request);
                        document.getElementById('acceptRequestId').value = requestId;
                        document.getElementById('declineRequestId').value = requestId;
                        openModal('reviewRequestModal');
                    }
                });
        }

        // Display request details in modal
        function displayRequestDetails(req) {
            const html = `
                <div>
                    <h3 style="font-weight: 700; margin-bottom: 10px; color: var(--text-dark);">${req.event_title}</h3>
                    <p style="margin: 5px 0; color: var(--text-light);"><strong>Advisor:</strong> ${req.advisor_name}</p>
                    <p style="margin: 5px 0; color: var(--text-light);"><strong>Date:</strong> ${new Date(req.event_date).toLocaleDateString()}</p>
                    <p style="margin: 5px 0; color: var(--text-light);"><strong>Time:</strong> ${req.start_time} - ${req.end_time}</p>
                    <p style="margin: 5px 0; color: var(--text-light);"><strong>Room:</strong> ${req.room_name || 'N/A'}</p>
                    <p style="margin: 5px 0; color: var(--text-light);"><strong>Expected Guests:</strong> ${req.expected_guests}</p>
                    <p style="margin: 5px 0; color: var(--text-light);"><strong>Description:</strong></p>
                    <p style="margin: 5px 0 0 0; color: var(--text-dark);">${req.event_description}</p>
                </div>
            `;
            document.getElementById('requestDetailsContainer').innerHTML = html;
        }

        // Show accept form
        function showAcceptForm() {
            document.getElementById('acceptForm').style.display = 'block';
            document.getElementById('declineForm').style.display = 'none';
        }

        // Hide accept form
        function hideAcceptForm() {
            document.getElementById('acceptForm').style.display = 'none';
        }

        // Show decline form
        function showDeclineForm() {
            document.getElementById('declineForm').style.display = 'block';
            document.getElementById('acceptForm').style.display = 'none';
        }

        // Hide decline form
        function hideDeclineForm() {
            document.getElementById('declineForm').style.display = 'none';
        }

        // Open Feedbacks Modal
        function openFeedbacksModal(eventId, eventTitle) {
            document.getElementById('eventTitleInModal').textContent = eventTitle;
            
            // Fetch feedbacks using AJAX
            fetch('admin_dashboard.php?getEventFeedbacks=' + eventId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayFeedbacks(data.feedbacks);
                        openModal('feedbacksModal');
                    } else {
                        alert('Error loading feedbacks');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Display Feedbacks in Modal
        function displayFeedbacks(feedbacks) {
            const container = document.getElementById('feedbacksContainer');
            
            if (feedbacks.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: var(--text-light); padding: 20px;">No feedbacks for this event yet.</p>';
                return;
            }
            
            let html = '';
            feedbacks.forEach((feedback, index) => {
                const statusBg = feedback.status === 'visible' ? '#10b981' : '#ef4444';
                const statusText = feedback.status === 'visible' ? 'Visible' : 'Invisible';
                const toggleText = feedback.status === 'visible' ? 'Make Invisible' : 'Make Visible';
                const toggleStatus = feedback.status === 'visible' ? 'invisible' : 'visible';
                
                html += `
                    <div style="border: 1px solid var(--border-color); border-radius: 10px; padding: 16px; margin-bottom: 16px; background-color: var(--bg-light);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <div>
                                <h4 style="margin: 0 0 4px 0; color: var(--text-dark); font-weight: 600;">${feedback.student_name}</h4>
                                <small style="color: var(--text-light);">Submitted on ${new Date(feedback.submitted_at).toLocaleDateString()}</small>
                            </div>
                            <span style="background-color: ${statusBg}; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;">${statusText}</span>
                        </div>
                        
                        <div style="margin-bottom: 12px;">
                            <strong>Rating:</strong>
                            <div style="color: #fbbf24; font-size: 16px;">
                                ${'★'.repeat(feedback.rating)}${'☆'.repeat(5 - feedback.rating)} (${feedback.rating}/5)
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 12px;">
                            <strong>Comments:</strong>
                            <p style="margin: 8px 0 0 0; color: var(--text-dark);">${feedback.comments}</p>
                        </div>
                        
                        <form method="POST" action="admin_dashboard.php" style="display: flex; gap: 8px;">
                            <input type="hidden" name="feedbackId" value="${feedback.feedback_id}">
                            <input type="hidden" name="feedbackStatus" value="${toggleStatus}">
                            <button type="submit" name="updateFeedbackStatus" class="btn" style="background-color: ${toggleStatus === 'visible' ? 'var(--success-color)' : 'var(--danger-color)'}; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                ${toggleStatus === 'visible' ? '✓ Make Visible' : '✗ Make Invisible'}
                            </button>
                        </form>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Section Navigation
        function showSection(sectionName) {
            // Hide all sections
            const sections = document.querySelectorAll('main > section');
            sections.forEach(section => section.style.display = 'none');

            // Show selected section
            const selectedSection = document.getElementById(sectionName + '-section');
            if (selectedSection) {
                selectedSection.style.display = 'block';
            }

            // Update header title
            const headerTitle = document.querySelector('.header-title');
            const titles = {
                dashboard: 'Dashboard',
                users: 'Manage Users',
                requests: 'Event Requests',
                events: 'Events List',
                feedbacks: 'Feedbacks',
                rooms: 'Room Management',
                notifications: 'Notifications'
            };
            headerTitle.textContent = titles[sectionName] || 'Dashboard';

            // Update active sidebar link
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.classList.remove('active');
            });
            event?.target?.classList.add('active');
        }

        // Modal Functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });

        // Close modal with Escape key
        window.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });

        // Filter Users by Role
        function filterUsersByRole() {
            const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
            const table = document.querySelector('.users-table tbody');
            const rows = table.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const roleCell = rows[i].getElementsByTagName('td')[5]; // Role is the 6th column
                if (roleCell) {
                    const roleText = roleCell.textContent || roleCell.innerText;
                    if (roleFilter === '' || roleText.toLowerCase() === roleFilter) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>

</html>