<?php
session_start();
include '../database/config.php';
include '../includes/qr-generator.php';

// Check if user is logged in and is advisor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'advisor') {
    header("Location: ../users/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create Event Request
    if (isset($_POST['createEvent'])) {
        $event_title = mysqli_real_escape_string($con, $_POST['eventName']);
        $event_description = mysqli_real_escape_string($con, $_POST['eventDescription']);
        $event_date = mysqli_real_escape_string($con, $_POST['eventDate']);
        $start_time = mysqli_real_escape_string($con, $_POST['startTime']);
        $end_time = mysqli_real_escape_string($con, $_POST['endTime']);
        $expected_guests = mysqli_real_escape_string($con, $_POST['eventCapacity']);
        $room_id = mysqli_real_escape_string($con, $_POST['roomId']);
        
        $insert_query = "INSERT INTO event_requests (advisor_id, room_id, event_title, event_description, expected_guests, event_date, start_time, end_time, status) 
                        VALUES ('$user_id', '$room_id', '$event_title', '$event_description', '$expected_guests', '$event_date', '$start_time', '$end_time', 'pending')";
        
        if (mysqli_query($con, $insert_query)) {
            echo "<script>alert('Event request created successfully!'); window.location.href = 'advisor_dashboard.php';</script>";
        } else {
            echo "<script>alert('Error: Could not create event request. " . mysqli_error($con) . "');</script>";
        }
    }
    
    // Edit Profile
    if (isset($_POST['editProfile'])) {
        $profile_name = mysqli_real_escape_string($con, $_POST['profileName']);
        $profile_email = mysqli_real_escape_string($con, $_POST['profileEmail']);
        $profile_phone = mysqli_real_escape_string($con, $_POST['profilePhone']);
        $profile_department = mysqli_real_escape_string($con, $_POST['profileDepartment']);
        
        $update_query = "UPDATE users SET name = '$profile_name', email_address = '$profile_email', phone = '$profile_phone', department = '$profile_department' WHERE user_id = '$user_id'";
        
        if (!empty($_POST['profilePassword'])) {
            $new_password = password_hash($_POST['profilePassword'], PASSWORD_BCRYPT);
            $update_query = "UPDATE users SET name = '$profile_name', email_address = '$profile_email', phone = '$profile_phone', department = '$profile_department', password = '$new_password' WHERE user_id = '$user_id'";
        }
        
        if (mysqli_query($con, $update_query)) {
            echo "<script>alert('Profile updated successfully!'); window.location.href = 'advisor_dashboard.php';</script>";
        } else {
            echo "<script>alert('Error: Could not update profile. " . mysqli_error($con) . "');</script>";
        }
    }
    
    // Invite Students to Event
    if (isset($_POST['inviteStudents'])) {
        $event_id = mysqli_real_escape_string($con, $_POST['inviteEvent']);
        $student_ids = $_POST['inviteStudent'];
        $sent_by = $user_id;
        
        if (!empty($student_ids) && is_array($student_ids)) {
            $success = true;
            foreach ($student_ids as $student_id) {
                $student_id = mysqli_real_escape_string($con, $student_id);
                $invite_query = "INSERT INTO event_invitations (event_id, student_id, sent_by) VALUES ('$event_id', '$student_id', '$sent_by')";
                
                if (!mysqli_query($con, $invite_query)) {
                    $success = false;
                }
            }
            
            if ($success) {
                echo "<script>alert('Invitations sent successfully!'); window.location.href = 'advisor_dashboard.php';</script>";
            } else {
                echo "<script>alert('Error: Some invitations could not be sent.');</script>";
            }
        }
    }

    // Add Guest
    if (isset($_POST['addGuest'])) {
        $guest_name = mysqli_real_escape_string($con, $_POST['guest_name']);
        $guest_email = mysqli_real_escape_string($con, $_POST['guest_email']);
        $guest_phone = mysqli_real_escape_string($con, $_POST['guest_phone']);
        $notes = mysqli_real_escape_string($con, $_POST['notes']);

        $insert_query = "INSERT INTO invited_guests (guest_name, guest_email, guest_phone, invitation_status, created_by, created_at)
                        VALUES ('$guest_name', '$guest_email', '$guest_phone', 'pending', '$user_id', NOW())";

        if (mysqli_query($con, $insert_query)) {
            echo "success";
        } else {
            echo "Error: " . mysqli_error($con);
        }
        exit();
    }

    // Update Guest
    if (isset($_POST['updateGuest'])) {
        $guest_id = mysqli_real_escape_string($con, $_POST['guest_id']);
        $guest_name = mysqli_real_escape_string($con, $_POST['guest_name']);
        $guest_email = mysqli_real_escape_string($con, $_POST['guest_email']);
        $guest_phone = mysqli_real_escape_string($con, $_POST['guest_phone']);
        $notes = mysqli_real_escape_string($con, $_POST['notes']);

        $update_query = "UPDATE invited_guests SET
                        guest_name = '$guest_name',
                        guest_email = '$guest_email',
                        guest_phone = '$guest_phone'
                        WHERE guest_id = '$guest_id' AND created_by = '$user_id'";

        if (mysqli_query($con, $update_query)) {
            echo "success";
        } else {
            echo "Error: " . mysqli_error($con);
        }
        exit();
    }

    // Delete Guest
    if (isset($_POST['deleteGuest'])) {
        $guest_id = mysqli_real_escape_string($con, $_POST['guest_id']);

        $delete_query = "DELETE FROM invited_guests WHERE guest_id = '$guest_id' AND created_by = '$user_id'";

        if (mysqli_query($con, $delete_query)) {
            echo "success";
        } else {
            echo "Error: " . mysqli_error($con);
        }
        exit();
    }

    // Send Invitations
    if (isset($_POST['sendInvitations'])) {
        try {
            $event_id = mysqli_real_escape_string($con, $_POST['event_id']);
            $method = mysqli_real_escape_string($con, $_POST['method']);
            $selected_guests = isset($_POST['selected_guests']) ? explode(',', $_POST['selected_guests']) : [];
            $custom_message = mysqli_real_escape_string($con, $_POST['custom_message']);

            if (empty($event_id)) {
                throw new Exception('Event ID is required');
            }

            if (empty($selected_guests)) {
                throw new Exception('No guests selected');
            }

            // Get event details
            $event_query = "SELECT e.event_title, e.event_description, e.event_date, e.start_time, e.end_time,
                                   r.room_name, r.location, u.name as advisor_name
                            FROM events e
                            JOIN event_requests er ON e.request_id = er.request_id
                            JOIN rooms r ON e.room_id = r.room_id
                            JOIN users u ON er.advisor_id = u.user_id
                            WHERE e.event_id = '$event_id' AND er.advisor_id = '$user_id'";

            $event_result = mysqli_query($con, $event_query);
            if (!$event_result) {
                throw new Exception('Database error: ' . mysqli_error($con));
            }

            $event = mysqli_fetch_assoc($event_result);
            if (!$event) {
                throw new Exception('Event not found');
            }

            $sent_count = 0;
            $errors = [];

            foreach ($selected_guests as $guest_id) {
                $guest_id = mysqli_real_escape_string($con, trim($guest_id));

                if (empty($guest_id)) continue;

                // Get guest details
                $guest_query = "SELECT * FROM invited_guests WHERE guest_id = '$guest_id' AND created_by = '$user_id'";
                $guest_result = mysqli_query($con, $guest_query);
                if (!$guest_result) {
                    $errors[] = "Database error for guest ID $guest_id";
                    continue;
                }

                $guest = mysqli_fetch_assoc($guest_result);
                if (!$guest) {
                    $errors[] = "Guest not found: $guest_id";
                    continue;
                }

                // Check if invitation already exists
                $check_query = "SELECT * FROM event_invitations WHERE event_id = '$event_id' AND guest_id = '$guest_id'";
                $check_result = mysqli_query($con, $check_query);
                if (!$check_result) {
                    $errors[] = "Database error checking invitation for guest: " . $guest['guest_name'];
                    continue;
                }

                if (mysqli_num_rows($check_result) == 0) {
                    // Create invitation record
                    $invite_query = "INSERT INTO event_invitations (event_id, guest_id, sent_by, sent_at)
                                   VALUES ('$event_id', '$guest_id', '$user_id', NOW())";

                    if (mysqli_query($con, $invite_query)) {
                        // Generate unique verification token
                        $verification_token = md5($guest_id . $event_id . $user_id . time() . rand());

                        // Create verification URL
                        $verification_url = "https://khodorhoteit.eu/verify-invitation.php?token=" . $verification_token;

                        // Generate direct QR code API URL (simplest and most reliable for email)
                        $qr_code_url = getQRCodeURL($verification_url);

                        // Send email invitation
                        $to = $guest['guest_email'];
                        $subject = "Event Invitation: " . $event['event_title'];

                        $message = "
Dear " . $guest['guest_name'] . ",

You have been invited to attend the following event:

Event: " . $event['event_title'] . "
Date: " . date('l, F j, Y', strtotime($event['event_date'])) . "
Time: " . date('g:i A', strtotime($event['start_time'])) . " - " . date('g:i A', strtotime($event['end_time'])) . "
Location: " . $event['room_name'] . " (" . $event['location'] . ")
Advisor: " . $event['advisor_name'] . "

";

                        if (!empty($event['event_description'])) {
                            $message .= "Description: " . $event['event_description'] . "\n\n";
                        }

                        if (!empty($custom_message)) {
                            $message .= "Additional Message: " . $custom_message . "\n\n";
                        }

                        $message .= "Please scan the QR code below to verify your invitation and confirm attendance:

Verification URL: " . $verification_url . "

After attending the event, please share your feedback:
Feedback Link: https://khodorhoteit.eu/guest-feedback.php?event=" . $event_id . "&guest=" . $guest_id . "

Best regards,
Event Management Team
Khodor Hoteit
Email: info@khodorhoteit.eu
Website: https://khodorhoteit.eu
";

                        // Email headers (HTML email for QR code)
                        $headers = "From: Event Management <info@khodorhoteit.eu>\r\n";
                        $headers .= "Reply-To: info@khodorhoteit.eu\r\n";
                        $headers .= "MIME-Version: 1.0\r\n";
                        $headers .= "Content-Type: multipart/alternative; boundary=\"----WebKitFormBoundary123456789\"\r\n";
                        $headers .= "X-Mailer: PHP/" . phpversion();

                        // Plain text version
                        $plain_message = $message;

                        // HTML version with QR code
                        $html_message = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Event Invitation</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <h2 style='color: #2c3e50;'>Event Invitation</h2>
        <p>Dear " . htmlspecialchars($guest['guest_name']) . ",</p>

        <p>You have been invited to attend the following event:</p>

        <div style='background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #667eea;'>
            <strong style='color: #667eea;'>Event:</strong> " . htmlspecialchars($event['event_title']) . "<br>
            <strong style='color: #667eea;'>Date:</strong> " . date('l, F j, Y', strtotime($event['event_date'])) . "<br>
            <strong style='color: #667eea;'>Time:</strong> " . date('g:i A', strtotime($event['start_time'])) . " - " . date('g:i A', strtotime($event['end_time'])) . "<br>
            <strong style='color: #667eea;'>Location:</strong> " . htmlspecialchars($event['room_name']) . " (" . htmlspecialchars($event['location']) . ")<br>
            <strong style='color: #667eea;'>Advisor:</strong> " . htmlspecialchars($event['advisor_name']) . "
        </div>
";

                        if (!empty($event['event_description'])) {
                            $html_message .= "<p><strong>Description:</strong> " . nl2br(htmlspecialchars($event['event_description'])) . "</p>";
                        }

                        if (!empty($custom_message)) {
                            $html_message .= "<p><strong>Additional Message:</strong> " . nl2br(htmlspecialchars($custom_message)) . "</p>";
                        }

                        $html_message .= "
        <p style='margin-top: 30px; margin-bottom: 15px;'><strong>📱 Verify Your Invitation:</strong></p>

        <div style='text-align: center; background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px dashed #667eea;'>
            <p style='margin: 0 0 15px 0; color: #555;'>Scan this QR code with your phone:</p>
            <img src=\"" . $qr_code_url . "\" alt=\"QR Code for Invitation Verification\" style=\"width: 250px; height: 250px; display: block; margin: 0 auto; border: 1px solid #ddd; padding: 5px; background: white;\">
            <p style='margin: 15px 0 0 0; color: #666; font-size: 12px;'><strong>Can't scan?</strong> <a href=\"" . $verification_url . "\" style='color: #667eea; text-decoration: none;'>Click here instead</a></p>
        </div>

        <div style='background: #e8f5e8; border: 2px solid #4caf50; border-radius: 8px; padding: 15px; margin: 20px 0;'>
            <p style='margin: 0; color: #2e7d32;'><strong>📝 Share Your Feedback:</strong></p>
            <p style='margin: 10px 0 0 0; color: #555;'>After attending the event, please take a moment to share your feedback:</p>
            <p style='margin: 10px 0 0 0;'><a href='https://khodorhoteit.eu/guest-feedback.php?event=" . $event_id . "&guest=" . $guest_id . "' style='display: inline-block; background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Submit Your Feedback</a></p>
        </div>

        <div style='border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 30px; color: #666; font-size: 0.9em;'>
            <p style='margin: 5px 0;'>Best regards,<br>
            <strong>Event Management Team</strong><br>
            Khodor Hoteit<br>
            Email: <a href='mailto:info@khodorhoteit.eu' style='color: #667eea; text-decoration: none;'>info@khodorhoteit.eu</a><br>
            Website: <a href='https://khodorhoteit.eu' style='color: #667eea; text-decoration: none;'>https://khodorhoteit.eu</a></p>
        </div>
    </div>
</body>
</html>";

                        // Create multipart message with proper formatting
                        $boundary = "----WebKitFormBoundary123456789";
                        $full_message = "";
                        
                        // Plain text part
                        $full_message .= "--" . $boundary . "\r\n";
                        $full_message .= "Content-Type: text/plain; charset=UTF-8\r\n";
                        $full_message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                        $full_message .= $plain_message . "\r\n\r\n";
                        
                        // HTML part
                        $full_message .= "--" . $boundary . "\r\n";
                        $full_message .= "Content-Type: text/html; charset=UTF-8\r\n";
                        $full_message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                        $full_message .= $html_message . "\r\n\r\n";
                        
                        // End boundary
                        $full_message .= "--" . $boundary . "--\r\n";

                        // Send email (suppress warnings)
                        if (@mail($to, $subject, $full_message, $headers)) {
                            // Update guest status
                            $update_guest = "UPDATE invited_guests SET invitation_status = 'sent' WHERE guest_id = '$guest_id'";
                            mysqli_query($con, $update_guest);
                            $sent_count++;
                        } else {
                            $errors[] = "Failed to send email to: " . $guest['guest_email'];
                            // Remove the invitation record since email failed
                            mysqli_query($con, "DELETE FROM event_invitations WHERE event_id = '$event_id' AND guest_id = '$guest_id'");
                        }
                    } else {
                        $errors[] = "Failed to create invitation for guest: " . $guest['guest_name'] . " - " . mysqli_error($con);
                    }
                } else {
                    $errors[] = "Guest already invited: " . $guest['guest_name'];
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => count($errors) == 0,
                'sent_count' => $sent_count,
                'errors' => $errors,
                'message' => count($errors) == 0 ? 'All invitations sent successfully' : 'Some invitations failed'
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'sent_count' => 0,
                'errors' => [$e->getMessage()]
            ]);
        }
        exit();
    }
}

// Handle AJAX GET Requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get Guest Details
    if (isset($_GET['getGuestDetails'])) {
        $guest_id = mysqli_real_escape_string($con, $_GET['getGuestDetails']);

        $query = "SELECT * FROM invited_guests WHERE guest_id = '$guest_id' AND created_by = '$user_id'";
        $result = mysqli_query($con, $query);

        if ($guest = mysqli_fetch_assoc($result)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'guest' => $guest]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Guest not found']);
        }
        exit();
    }

    // Get Available Guests for Event
    if (isset($_GET['getAvailableGuests'])) {
        $event_id = mysqli_real_escape_string($con, $_GET['getAvailableGuests']);

        $query = "SELECT ig.* FROM invited_guests ig
                 WHERE ig.created_by = '$user_id'
                 ORDER BY ig.guest_name";

        $result = mysqli_query($con, $query);
        $guests = [];

        while ($guest = mysqli_fetch_assoc($result)) {
            $guests[] = $guest;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'guests' => $guests]);
        exit();
    }

    // Get Invitation History
    if (isset($_GET['getInvitationHistory'])) {
        $query = "SELECT ei.*, ig.guest_name, ig.guest_email, e.event_title, ei.sent_at
                 FROM event_invitations ei
                 JOIN invited_guests ig ON ei.guest_id = ig.guest_id
                 JOIN events e ON ei.event_id = e.event_id
                 WHERE ei.sent_by = '$user_id'
                 ORDER BY ei.sent_at DESC LIMIT 10";

        $result = mysqli_query($con, $query);
        $history = [];

        while ($item = mysqli_fetch_assoc($result)) {
            $history[] = $item;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'history' => $history]);
        exit();
    }

    // Get Event Details
    if (isset($_GET['getEventDetails'])) {
        $event_id = mysqli_real_escape_string($con, $_GET['getEventDetails']);

        $query = "SELECT e.*, er.event_description, er.expected_guests, r.room_name, r.location, r.capacity
                 FROM events e
                 JOIN event_requests er ON e.request_id = er.request_id
                 JOIN rooms r ON e.room_id = r.room_id
                 WHERE e.event_id = '$event_id' AND er.advisor_id = '$user_id'";

        $result = mysqli_query($con, $query);

        if ($event = mysqli_fetch_assoc($result)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'event' => $event]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Event not found']);
        }
        exit();
    }
}

// Fetch advisor statistics
$total_students = mysqli_num_rows(mysqli_query($con, "SELECT * FROM users WHERE role = 'student'"));
$my_events = mysqli_num_rows(mysqli_query($con, "SELECT er.request_id FROM event_requests er WHERE er.advisor_id = '$user_id' AND er.status = 'approved'"));
$pending_events = mysqli_num_rows(mysqli_query($con, "SELECT * FROM event_requests WHERE advisor_id = '$user_id' AND status = 'pending'"));
$accepted_events = mysqli_num_rows(mysqli_query($con, "SELECT * FROM events WHERE request_id IN (SELECT request_id FROM event_requests WHERE advisor_id = '$user_id' AND status = 'approved')"));

// Fetch advisor info
$user_query = mysqli_query($con, "SELECT * FROM users WHERE user_id = '$user_id'");
$advisor = mysqli_fetch_assoc($user_query);
$advisor_name = $advisor ? $advisor['name'] : 'Advisor';
$advisor_email = $advisor ? $advisor['email_address'] : '';
$advisor_phone = $advisor ? $advisor['phone'] : '';
$advisor_department = $advisor ? $advisor['department'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Advisor Dashboard - Event Management System">
    <meta name="author" content="Mahdi Saleh">
    <title>Advisor Dashboard - EventHub</title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="shortcut icon" href="../assets/images/event_system.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200;0,400;0,600;0,700;1,200;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- ======================== SIDEBAR ======================== -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-calendar-alt"></i>
                <span>EventHub</span>
            </div>

            <ul class="sidebar-menu">
                <li><a onclick="showSection('dashboard', this)" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a onclick="showSection('my-events', this)"><i class="fas fa-calendar-check"></i> My Events</a></li>
                <li><a onclick="showSection('create-event', this)"><i class="fas fa-plus-circle"></i> Create Event</a></li>
                <li><a onclick="showSection('requests', this)"><i class="fas fa-inbox"></i> Requests</a></li>
                <li><a onclick="showSection('students', this)"><i class="fas fa-graduation-cap"></i> Students</a></li>
                <li><a onclick="showSection('outside-guests', this)"><i class="fas fa-users"></i> Outside Guests</a></li>
                <li><a onclick="showSection('invitations', this)"><i class="fas fa-envelope"></i> Guests Invitations</a></li>
                <li><a onclick="showSection('feedbacks', this)"><i class="fas fa-star"></i> Feedbacks</a></li>
                <li><a onclick="showSection('profile', this)"><i class="fas fa-user-circle"></i> Profile</a></li>
            </ul>

            <div class="sidebar-footer">
                <a href="../users/logout.php" style="display: flex; align-items: center; gap: 12px; color: rgba(255, 255, 255, 0.8); text-decoration: none; border-radius: 10px; padding: 12px 16px; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.color='white';" onmouseout="this.style.background=''; this.style.color='rgba(255, 255, 255, 0.8)';">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- ======================== HEADER ======================== -->
        <header class="header">
            <button class="hamburger-menu" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header-title">Dashboard</div>
            <div class="header-right">
                <button class="notifications-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">0</span>
                </button>
                
                <div class="user-profile">
                    <div class="user-avatar"><?php echo substr($advisor_name, 0, 1); ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo ucfirst($advisor_name); ?></div>
                        <div style="font-size: 12px; opacity: 0.8;">Advisor</div>
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
                    <h1 class="content-title">Welcome, <?php echo ucfirst($advisor_name); ?>! 👋</h1>
                    <p class="content-subtitle">Here's an overview of your event management activities</p>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card students">
                        <div class="stat-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value"><?php echo $total_students; ?></div>
                    </div>

                    <div class="stat-card events">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-label">My Events</div>
                        <div class="stat-value"><?php echo $my_events; ?></div>
                    </div>

                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="stat-label">Pending Requests</div>
                        <div class="stat-value"><?php echo $pending_events; ?></div>
                    </div>

                    <div class="stat-card accepted">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-label">Accepted Events</div>
                        <div class="stat-value"><?php echo $accepted_events; ?></div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="margin-bottom: 30px;">
                    <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px; color: var(--text-dark);">Quick Actions</h2>
                </div>

                <div class="actions-grid">
                    <div class="action-card" onclick="showSection('create-event')">
                        <div class="action-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="action-title">Create Event</div>
                        <div class="action-description">Request a new event</div>
                    </div>

                    <div class="action-card" onclick="showSection('requests')">
                        <div class="action-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="action-title">My Requests</div>
                        <div class="action-description">Track event requests</div>
                    </div>

                    <div class="action-card" onclick="showSection('my-events')">
                        <div class="action-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="action-title">Accepted Events</div>
                        <div class="action-description">View accepted events</div>
                    </div>

                    <div class="action-card" onclick="showSection('students')">
                        <div class="action-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="action-title">Students List</div>
                        <div class="action-description">View all students</div>
                    </div>

                    <div class="action-card" onclick="showSection('feedbacks')">
                        <div class="action-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="action-title">Feedbacks</div>
                        <div class="action-description">Check event feedbacks</div>
                    </div>

                    <div class="action-card" onclick="showSection('profile')">
                        <div class="action-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="action-title">Profile</div>
                        <div class="action-description">Manage your profile</div>
                    </div>
                </div>
            </section>

            <!-- Outside Guests Section -->
            <section id="outside-guests-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">Outside Guests Management</h1>
                    <p class="content-subtitle">Manage guests from outside the university</p>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="openModal('addGuestModal')">
                        <i class="fas fa-user-plus"></i> Add New Guest
                    </button>
                </div>

                <div class="table-container" style="margin-top: 20px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Guest Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="guestsTableBody">
                            <?php
                            $guests_query = "SELECT * FROM invited_guests WHERE created_by = '$user_id' ORDER BY created_at DESC";
                            $guests_result = mysqli_query($con, $guests_query);

                            if (mysqli_num_rows($guests_result) > 0) {
                                while ($guest = mysqli_fetch_assoc($guests_result)) {
                                    $status_class = '';
                                    $status_text = '';

                                    switch ($guest['invitation_status']) {
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            $status_text = 'Pending';
                                            break;
                                        case 'sent':
                                            $status_class = 'status-sent';
                                            $status_text = 'Invited';
                                            break;
                                        case 'verified':
                                            $status_class = 'status-verified';
                                            $status_text = 'Verified';
                                            break;
                                        case 'attended':
                                            $status_class = 'status-attended';
                                            $status_text = 'Attended';
                                            break;
                                    }

                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($guest['guest_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($guest['guest_email']) . '</td>';
                                    echo '<td>' . htmlspecialchars($guest['guest_phone']) . '</td>';
                                    echo '<td><span class="status-badge ' . $status_class . '">' . $status_text . '</span></td>';
                                    echo '<td>' . date('M d, Y', strtotime($guest['created_at'])) . '</td>';
                                    echo '<td>';
                                    echo '<button class="btn btn-sm btn-info" onclick="viewGuestDetails(' . $guest['guest_id'] . ')" title="View Details"><i class="fas fa-eye"></i></button>';
                                    echo '<button class="btn btn-sm btn-warning" onclick="editGuest(' . $guest['guest_id'] . ')" title="Edit"><i class="fas fa-edit"></i></button>';
                                    echo '<button class="btn btn-sm btn-danger" onclick="deleteGuest(' . $guest['guest_id'] . ')" title="Delete"><i class="fas fa-trash"></i></button>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6" style="text-align: center; color: #666;">No guests added yet. Click "Add New Guest" to get started.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Invitations Section -->
            <section id="invitations-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">Email Invitations</h1>
                    <p class="content-subtitle">Send email invitations to outside guests for your events</p>
                </div>

                <div class="invitations-container">
                    <div class="invitation-form">
                        <h3>Select Event & Guests</h3>
                        <form id="invitationForm">
                            <div class="form-group">
                                <label for="eventSelect">Select Event:</label>
                                <select id="eventSelect" name="event_id" required onchange="loadAvailableGuests()">
                                    <option value="">Choose an event...</option>
                                    <?php
                                    $events_query = "SELECT e.event_id, e.event_title, e.event_date, r.room_name
                                                   FROM events e
                                                   JOIN event_requests er ON e.request_id = er.request_id
                                                   JOIN rooms r ON e.room_id = r.room_id
                                                   WHERE er.advisor_id = '$user_id' AND e.event_date >= CURDATE()
                                                   ORDER BY e.event_date ASC";
                                    $events_result = mysqli_query($con, $events_query);

                                    while ($event = mysqli_fetch_assoc($events_result)) {
                                        echo '<option value="' . $event['event_id'] . '">' . htmlspecialchars($event['event_title']) . ' - ' . date('M d, Y', strtotime($event['event_date'])) . ' (' . htmlspecialchars($event['room_name']) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Available Guests:</label>
                                <div id="guestsList" class="guests-checkbox-list">
                                    <p style="color: #666; font-style: italic;">Please select an event first to see available guests.</p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="customMessage">Custom Message (Optional):</label>
                                <textarea id="customMessage" name="custom_message" rows="3" placeholder="Add a personal message to the email invitation..."></textarea>
                                <small style="color: var(--text-light); display: block; margin-top: 4px;">This message will be included in the email sent to guests.</small>
                            </div>

                            <button type="button" class="btn btn-primary" onclick="sendInvitations()">
                                <i class="fas fa-paper-plane"></i> Send Email Invitations
                            </button>
                        </form>
                    </div>

                    <div class="invitation-history">
                        <h3>Recent Invitations</h3>
                        <div id="invitationsHistory" class="history-list">
                            <p style="color: #666; font-style: italic;">No recent invitations sent.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Students Section -->
            <section id="students-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">Students List</h1>
                    <p class="content-subtitle">View all registered students</p>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $students_query = mysqli_query($con, "SELECT * FROM users WHERE role = 'student'");
                            if (mysqli_num_rows($students_query) > 0) {
                                while ($student = mysqli_fetch_assoc($students_query)) {
                                    echo "
                                    <tr>
                                        <td>" . ucfirst($student['name']) . "</td>
                                        <td>" . $student['email_address'] . "</td>
                                        <td>" . $student['phone'] . "</td>
                                        <td>" . $student['department'] . "</td>
                                        <td><button class='btn-small btn-view' onclick=\"openModal('inviteStudentModal')\"><i class='fas fa-envelope'></i> Invite</button></td>
                                    </tr>
                                    ";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align: center; color: var(--text-light);'>No students found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- My Events Section -->
            <section id="my-events-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">My Accepted Events</h1>
                    <p class="content-subtitle">Events that have been approved</p>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Date & Time</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $my_events_query = mysqli_query($con, "SELECT e.* FROM events e JOIN event_requests er ON e.request_id = er.request_id WHERE er.advisor_id = '$user_id' AND er.status = 'approved' ORDER BY e.event_date DESC");
                            if (mysqli_num_rows($my_events_query) > 0) {
                                while ($evt = mysqli_fetch_assoc($my_events_query)) {
                                    $room_info = '';
                                    if (!empty($evt['room_id'])) {
                                        $room_query = mysqli_query($con, "SELECT room_name FROM rooms WHERE room_id = '" . $evt['room_id'] . "'");
                                        $room = mysqli_fetch_assoc($room_query);
                                        $room_info = $room ? $room['room_name'] : 'N/A';
                                    }
                                    echo "
                                    <tr>
                                        <td>" . $evt['event_title'] . "</td>
                                        <td>" . date('M d, Y', strtotime($evt['event_date'])) . "<br>" . date('H:i', strtotime($evt['start_time'])) . " - " . date('H:i', strtotime($evt['end_time'])) . "</td>
                                        <td>" . $room_info . "</td>
                                        <td><span class='status-badge status-accepted'>Approved</span></td>
                                        <td>
                                            <div class='action-buttons'>
                                                <button class='btn-small btn-view' onclick=\"openModal('inviteStudentModal')\"><i class='fas fa-envelope'></i> Invite</button>
                                                <button class='btn-small btn-edit' onclick=\"viewEventDetails('" . $evt['event_id'] . "')\"><i class='fas fa-eye'></i> View</button>
                                            </div>
                                        </td>
                                    </tr>
                                    ";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align: center; color: var(--text-light);'>No accepted events yet</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Create Event Section -->
            <section id="create-event-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">Create Event Request</h1>
                    <p class="content-subtitle">Submit a new event request for approval</p>
                </div>
                <button class="btn btn-primary" onclick="openModal('createEventModal')">
                    <i class="fas fa-plus-circle"></i> Create New Event
                </button>
                <div class="table-container" style="margin-top: 30px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $events_query = mysqli_query($con, "SELECT * FROM event_requests WHERE advisor_id = '$user_id' ORDER BY created_at DESC");
                            if (mysqli_num_rows($events_query) > 0) {
                                while ($event = mysqli_fetch_assoc($events_query)) {
                                    $status_class = 'status-' . $event['status'];
                                    echo "
                                    <tr>
                                        <td>" . $event['event_title'] . "</td>
                                        <td>" . date('M d, Y', strtotime($event['event_date'])) . "</td>
                                        <td><span class='status-badge $status_class'>" . ucfirst($event['status']) . "</span></td>
                                        <td>
                                            <div class='action-buttons'>
                                                <button class='btn-small btn-view' onclick=\"editEventRequest('" . $event['request_id'] . "')\"><i class='fas fa-edit'></i> Edit</button>
                                            </div>
                                        </td>
                                    </tr>
                                    ";
                                }
                            } else {
                                echo "<tr><td colspan='4' style='text-align: center; color: var(--text-light);'>No event requests yet</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Event Requests Section -->
            <section id="requests-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">Track Event Requests</h1>
                    <p class="content-subtitle">Check the status of your event requests (Accepted, Declined, Pending)</p>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Date & Time</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Admin Comment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $requests_query = mysqli_query($con, "SELECT er.* FROM event_requests er WHERE er.advisor_id = '$user_id' ORDER BY er.created_at DESC");
                            if (mysqli_num_rows($requests_query) > 0) {
                                while ($req = mysqli_fetch_assoc($requests_query)) {
                                    $status_class = 'status-' . $req['status'];
                                    $room_info = '';
                                    if (!empty($req['room_id'])) {
                                        $room_query = mysqli_query($con, "SELECT room_name FROM rooms WHERE room_id = '" . $req['room_id'] . "'");
                                        $room = mysqli_fetch_assoc($room_query);
                                        $room_info = $room ? $room['room_name'] : 'N/A';
                                    }
                                    $admin_comment = $req['admin_comment'] ? $req['admin_comment'] : '-';
                                    echo "
                                    <tr>
                                        <td>" . $req['event_title'] . "</td>
                                        <td>" . date('M d, Y', strtotime($req['event_date'])) . "<br>" . date('H:i', strtotime($req['start_time'])) . " - " . date('H:i', strtotime($req['end_time'])) . "</td>
                                        <td>" . $room_info . "</td>
                                        <td><span class='status-badge $status_class'>" . ucfirst($req['status']) . "</span></td>
                                        <td>" . $admin_comment . "</td>
                                        <td>
                                            <div class='action-buttons'>
                                                <button class='btn-small btn-view' onclick=\"viewRequestDetails('" . $req['request_id'] . "')\"><i class='fas fa-eye'></i> View</button>
                                                " . ($req['status'] == 'pending' ? "<button class='btn-small btn-edit' onclick=\"editEventRequest('" . $req['request_id'] . "')\"><i class='fas fa-edit'></i> Edit</button>" : "") . "
                                            </div>
                                        </td>
                                    </tr>
                                    ";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align: center; color: var(--text-light);'>No requests found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Feedbacks Section -->
            <section id="feedbacks-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">Event Feedbacks</h1>
                    <p class="content-subtitle">Check feedbacks from students after events</p>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Event Title</th>
                                <th>Rating</th>
                                <th>Comments</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $feedbacks_query = mysqli_query($con, "
                                SELECT f.*, u.name as student_name, e.event_title 
                                FROM feedback f
                                JOIN users u ON f.student_id = u.user_id
                                JOIN events e ON f.event_id = e.event_id
                                WHERE e.request_id IN (SELECT request_id FROM event_requests WHERE advisor_id = '$user_id')
                                ORDER BY f.submitted_at DESC
                            ");
                            if (mysqli_num_rows($feedbacks_query) > 0) {
                                while ($fb = mysqli_fetch_assoc($feedbacks_query)) {
                                    $status_badge = $fb['status'] ? "<span class='status-badge status-accepted'>" . ucfirst($fb['status']) . "</span>" : "<span class='status-badge status-pending'>Pending</span>";
                                    echo "
                                    <tr>
                                        <td>" . ucfirst($fb['student_name']) . "</td>
                                        <td>" . $fb['event_title'] . "</td>
                                        <td>
                                            <div style='color: #f59e0b;'>
                                                " . $fb['rating'] / 5 * 100 . "%
                                            </div>
                                        </td>
                                        <td>" . substr($fb['comments'], 0, 50) . (strlen($fb['comments']) > 50 ? "..." : "") . "</td>
                                        <td>" . $status_badge . "</td>
                                        <td>" . date('M d, Y', strtotime($fb['submitted_at'])) . "</td>
                                    </tr>
                                    ";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align: center; color: var(--text-light);'>No feedbacks yet</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Profile Section -->
            <section id="profile-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">My Profile</h1>
                    <p class="content-subtitle">Manage your profile information</p>
                </div>

                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar"><?php echo substr($advisor_name, 0, 1); ?></div>
                        <div class="profile-info">
                            <h2><?php echo ucfirst($advisor_name); ?></h2>
                            <p><i class="fas fa-envelope"></i> <?php echo $advisor_email; ?></p>
                            <p><i class="fas fa-phone"></i> <?php echo $advisor_phone; ?></p>
                            <p><i class="fas fa-building"></i> <?php echo $advisor_department; ?></p>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="openModal('editProfileModal')">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                </div>
            </section>
        </main>

        <!-- ======================== FOOTER ======================== -->
        <footer class="footer">
            <p>&copy; 2025 EventHub Advisor Dashboard. All rights reserved.</p>
        </footer>
    </div>

    <!-- ======================== MODALS ======================== -->
    <!-- Create Event Modal -->
    <div id="createEventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create Event Request</h2>
                <button class="close-btn" onclick="closeModal('createEventModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="advisor_dashboard.php" method="POST" id="createEventForm">
                    <div class="form-group">
                        <label for="eventName">Event Title</label>
                        <input type="text" id="eventName" name="eventName" placeholder="Enter event title" required>
                    </div>
                    <div class="form-group">
                        <label for="eventDescription">Event Description</label>
                        <textarea id="eventDescription" name="eventDescription" placeholder="Enter event description" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="eventDate">Event Date</label>
                        <input type="date" id="eventDate" name="eventDate" required>
                    </div>
                    <div class="form-group">
                        <label for="startTime">Start Time</label>
                        <input type="time" id="startTime" name="startTime" required>
                    </div>
                    <div class="form-group">
                        <label for="endTime">End Time</label>
                        <input type="time" id="endTime" name="endTime" required>
                    </div>
                    <div class="form-group">
                        <label for="roomId">Select Room</label>
                        <select id="roomId" name="roomId" required>
                            <option value="">-- Select Room --</option>
                            <?php
                            $rooms_query = mysqli_query($con, "SELECT * FROM rooms");
                            if ($rooms_query) {
                                while ($room = mysqli_fetch_assoc($rooms_query)) {
                                    echo "<option value='" . $room['room_id'] . "'>" . $room['room_name'] . " (Capacity: " . $room['capacity'] . ")</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="eventCapacity">Expected Guests</label>
                        <input type="number" id="eventCapacity" name="eventCapacity" placeholder="Enter expected number of guests" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createEventModal')">Cancel</button>
                <button type="submit" form="createEventForm" class="btn btn-primary" name="createEvent">Create Request</button>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button class="close-btn" onclick="closeModal('editProfileModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="advisor_dashboard.php" method="POST" id="editProfileForm">
                    <div class="form-group">
                        <label for="profileName">Full Name</label>
                        <input type="text" id="profileName" name="profileName" value="<?php echo $advisor_name; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="profileEmail">Email</label>
                        <input type="email" id="profileEmail" name="profileEmail" value="<?php echo $advisor_email; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="profilePhone">Phone Number</label>
                        <input type="text" id="profilePhone" name="profilePhone" value="<?php echo $advisor_phone; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="profileDepartment">Department</label>
                        <input type="text" id="profileDepartment" name="profileDepartment" value="<?php echo $advisor_department; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="profilePassword">New Password (leave empty to keep current)</label>
                        <input type="password" id="profilePassword" name="profilePassword" placeholder="Enter new password">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editProfileModal')">Cancel</button>
                <button type="submit" form="editProfileForm" class="btn btn-primary" name="editProfile">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Invite Student Modal -->
    <div id="inviteStudentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Invite Students</h2>
                <button class="close-btn" onclick="closeModal('inviteStudentModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="advisor_dashboard.php" method="POST" id="inviteStudentForm">
                    <div class="form-group">
                        <label for="inviteEvent">Select Event</label>
                        <select id="inviteEvent" name="inviteEvent" required>
                            <option value="">-- Select Event --</option>
                            <?php
                            $invite_events_query = mysqli_query($con, "SELECT e.event_id, e.event_title, e.event_date FROM events e JOIN event_requests er ON e.request_id = er.request_id WHERE er.advisor_id = '$user_id' AND er.status = 'approved' ORDER BY e.event_date DESC");
                            if ($invite_events_query) {
                                while ($evt = mysqli_fetch_assoc($invite_events_query)) {
                                    echo "<option value='" . $evt['event_id'] . "'>" . $evt['event_title'] . " - " . date('M d, Y', strtotime($evt['event_date'])) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inviteStudent">Select Student(s)</label>
                        <select id="inviteStudent" name="inviteStudent[]" multiple required style="min-height: 150px;">
                            <?php
                            $invite_students_query = mysqli_query($con, "SELECT * FROM users WHERE role = 'student'");
                            while ($std = mysqli_fetch_assoc($invite_students_query)) {
                                echo "<option value='" . $std['user_id'] . "'>" . ucfirst($std['name']) . " (" . $std['email_address'] . ")</option>";
                            }
                            ?>
                        </select>
                        <small style="color: var(--text-light); display: block; margin-top: 8px;">Hold Ctrl/Cmd to select multiple students</small>
                    </div>
                    <div class="form-group">
                        <label for="inviteMessage">Invitation Message</label>
                        <textarea id="inviteMessage" name="inviteMessage" placeholder="Enter invitation message" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('inviteStudentModal')">Cancel</button>
                <button type="submit" form="inviteStudentForm" class="btn btn-primary" name="inviteStudents">Send Invitations</button>
            </div>
        </div>
    </div>

    <!-- Add Guest Modal -->
    <div id="addGuestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Guest</h2>
                <button class="close-btn" onclick="closeModal('addGuestModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addGuestForm">
                    <div class="form-group">
                        <label for="guestName">Guest Name *</label>
                        <input type="text" id="guestName" name="guest_name" placeholder="Enter guest full name" required>
                    </div>
                    <div class="form-group">
                        <label for="guestEmail">Email Address *</label>
                        <input type="email" id="guestEmail" name="guest_email" placeholder="Enter email address" required>
                    </div>
                    <div class="form-group">
                        <label for="guestPhone">Phone Number *</label>
                        <input type="tel" id="guestPhone" name="guest_phone" placeholder="Enter phone number (with country code)" required>
                        <small style="color: var(--text-light); display: block; margin-top: 4px;">Example: +971501234567 or +1234567890</small>
                    </div>
                    <div class="form-group">
                        <label for="guestNotes">Notes (Optional)</label>
                        <textarea id="guestNotes" name="notes" placeholder="Any additional notes about the guest" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addGuestModal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addGuest()">Add Guest</button>
            </div>
        </div>
    </div>

    <!-- Edit Guest Modal -->
    <div id="editGuestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Guest</h2>
                <button class="close-btn" onclick="closeModal('editGuestModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editGuestForm">
                    <input type="hidden" id="editGuestId" name="guest_id">
                    <div class="form-group">
                        <label for="editGuestName">Guest Name *</label>
                        <input type="text" id="editGuestName" name="guest_name" placeholder="Enter guest full name" required>
                    </div>
                    <div class="form-group">
                        <label for="editGuestEmail">Email Address *</label>
                        <input type="email" id="editGuestEmail" name="guest_email" placeholder="Enter email address" required>
                    </div>
                    <div class="form-group">
                        <label for="editGuestPhone">Phone Number *</label>
                        <input type="tel" id="editGuestPhone" name="guest_phone" placeholder="Enter phone number (with country code)" required>
                        <small style="color: var(--text-light); display: block; margin-top: 4px;">Example: +971501234567 or +1234567890</small>
                    </div>
                    <div class="form-group">
                        <label for="editGuestNotes">Notes (Optional)</label>
                        <textarea id="editGuestNotes" name="notes" placeholder="Any additional notes about the guest" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editGuestModal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateGuest()">Update Guest</button>
            </div>
        </div>
    </div>

    <!-- Guest Details Modal -->
    <div id="guestDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Guest Details</h2>
                <button class="close-btn" onclick="closeModal('guestDetailsModal')">&times;</button>
            </div>
            <div class="modal-body" id="guestDetailsContent">
                <!-- Guest details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('guestDetailsModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- View Event Modal -->
    <div id="viewEventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Event Details</h2>
                <button class="close-btn" onclick="closeModal('viewEventModal')">&times;</button>
            </div>
            <div class="modal-body" id="eventDetailsContent">
                <!-- Event details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewEventModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Show/Hide Sections
        function showSection(sectionName, element) {
            const sections = document.querySelectorAll('main > section');
            sections.forEach(section => section.style.display = 'none');

            const selectedSection = document.getElementById(sectionName + '-section');
            if (selectedSection) {
                selectedSection.style.display = 'block';
            }

            const headerTitle = document.querySelector('.header-title');
            const titles = {
                dashboard: 'Dashboard',
                students: 'Students List',
                'create-event': 'Create Event',
                requests: 'Track Requests',
                'my-events': 'My Events',
                feedbacks: 'Feedbacks',
                profile: 'My Profile'
            };
            headerTitle.textContent = titles[sectionName] || 'Dashboard';

            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.classList.remove('active');
            });
            
            if (element) {
                element.classList.add('active');
            }
        }

        // Toggle Sidebar on Mobile
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('active');
            }
        }

        // Close sidebar when a menu item is clicked
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar && window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                }
            });
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const hamburger = document.querySelector('.hamburger-menu');
            if (sidebar && hamburger && !sidebar.contains(event.target) && !hamburger.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });

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

        // Placeholder functions for future implementation
        function editEventRequest(requestId) {
            console.log('Edit request:', requestId);
            // TODO: Implement edit functionality
        }

        function viewRequestDetails(requestId) {
            console.log('View request:', requestId);
            // TODO: Implement view functionality
        }

        function viewEventDetails(eventId) {
            // Fetch event details and populate view modal
            fetch(`advisor_dashboard.php?getEventDetails=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const event = data.event;
                        const content = `
                            <div class="event-details">
                                <div class="detail-row">
                                    <strong>Event Title:</strong> ${event.event_title}
                                </div>
                                <div class="detail-row">
                                    <strong>Description:</strong> ${event.event_description || 'N/A'}
                                </div>
                                <div class="detail-row">
                                    <strong>Date:</strong> ${new Date(event.event_date).toLocaleDateString()}
                                </div>
                                <div class="detail-row">
                                    <strong>Time:</strong> ${event.start_time} - ${event.end_time}
                                </div>
                                <div class="detail-row">
                                    <strong>Room:</strong> ${event.room_name} (${event.location})
                                </div>
                                <div class="detail-row">
                                    <strong>Capacity:</strong> ${event.capacity}
                                </div>
                                <div class="detail-row">
                                    <strong>Expected Guests:</strong> ${event.expected_guests}
                                </div>
                            </div>
                        `;
                        document.getElementById('eventDetailsContent').innerHTML = content;
                        openModal('viewEventModal');
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching event details:', error);
                    alert('Error loading event details. Please try again.');
                });
        }

        // Guest Management Functions
        function addGuest() {
            const form = document.getElementById('addGuestForm');
            const formData = new FormData(form);

            // Basic validation
            const name = formData.get('guest_name').trim();
            const email = formData.get('guest_email').trim();
            const phone = formData.get('guest_phone').trim();

            if (!name || !email || !phone) {
                alert('Please fill in all required fields.');
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address.');
                return;
            }

            // Phone validation (basic)
            if (!phone.match(/^\+\d{10,15}$/)) {
                alert('Please enter a valid phone number with country code (e.g., +1234567890).');
                return;
            }

            // Show loading
            const submitBtn = document.querySelector('#addGuestModal .btn-primary');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Adding...';
            submitBtn.disabled = true;

            // Send AJAX request
            fetch('advisor_dashboard.php', {
                method: 'POST',
                body: new URLSearchParams({
                    addGuest: '1',
                    guest_name: name,
                    guest_email: email,
                    guest_phone: phone,
                    notes: formData.get('notes')
                }),
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('success')) {
                    alert('Guest added successfully!');
                    closeModal('addGuestModal');
                    form.reset();
                    location.reload(); // Refresh to show new guest
                } else {
                    alert('Error adding guest: ' + data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding guest. Please try again.');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }

        function editGuest(guestId) {
            // Fetch guest details and populate edit modal
            fetch(`advisor_dashboard.php?getGuestDetails=${guestId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editGuestId').value = data.guest.guest_id;
                        document.getElementById('editGuestName').value = data.guest.guest_name;
                        document.getElementById('editGuestEmail').value = data.guest.guest_email;
                        document.getElementById('editGuestPhone').value = data.guest.guest_phone;
                        document.getElementById('editGuestNotes').value = data.guest.notes || '';
                        openModal('editGuestModal');
                    } else {
                        alert('Error loading guest details.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading guest details.');
                });
        }

        function updateGuest() {
            const form = document.getElementById('editGuestForm');
            const formData = new FormData(form);
            const guestId = formData.get('guest_id');

            // Basic validation
            const name = formData.get('guest_name').trim();
            const email = formData.get('guest_email').trim();
            const phone = formData.get('guest_phone').trim();

            if (!name || !email || !phone) {
                alert('Please fill in all required fields.');
                return;
            }

            // Show loading
            const submitBtn = document.querySelector('#editGuestModal .btn-primary');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Updating...';
            submitBtn.disabled = true;

            // Send AJAX request
            fetch('advisor_dashboard.php', {
                method: 'POST',
                body: new URLSearchParams({
                    updateGuest: '1',
                    guest_id: guestId,
                    guest_name: name,
                    guest_email: email,
                    guest_phone: phone,
                    notes: formData.get('notes')
                }),
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('success')) {
                    alert('Guest updated successfully!');
                    closeModal('editGuestModal');
                    location.reload(); // Refresh to show updated guest
                } else {
                    alert('Error updating guest: ' + data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating guest. Please try again.');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }

        function deleteGuest(guestId) {
            if (!confirm('Are you sure you want to delete this guest? This action cannot be undone.')) {
                return;
            }

            fetch('advisor_dashboard.php', {
                method: 'POST',
                body: new URLSearchParams({
                    deleteGuest: '1',
                    guest_id: guestId
                }),
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('success')) {
                    alert('Guest deleted successfully!');
                    location.reload(); // Refresh to remove deleted guest
                } else {
                    alert('Error deleting guest: ' + data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting guest. Please try again.');
            });
        }

        function viewGuestDetails(guestId) {
            fetch(`advisor_dashboard.php?getGuestDetails=${guestId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const guest = data.guest;
                        const content = `
                            <div class="guest-detail-item">
                                <strong>Name:</strong> ${guest.guest_name}
                            </div>
                            <div class="guest-detail-item">
                                <strong>Email:</strong> ${guest.guest_email}
                            </div>
                            <div class="guest-detail-item">
                                <strong>Phone:</strong> ${guest.guest_phone}
                            </div>
                            <div class="guest-detail-item">
                                <strong>Status:</strong> <span class="status-badge status-${guest.invitation_status}">${guest.invitation_status}</span>
                            </div>
                            <div class="guest-detail-item">
                                <strong>Created:</strong> ${new Date(guest.created_at).toLocaleDateString()}
                            </div>
                            ${guest.notes ? `<div class="guest-detail-item"><strong>Notes:</strong> ${guest.notes}</div>` : ''}
                            ${guest.qr_code_image ? `<div class="guest-detail-item"><strong>QR Code:</strong><br><img src="${guest.qr_code_image}" alt="QR Code" style="max-width: 200px;"></div>` : ''}
                        `;
                        document.getElementById('guestDetailsContent').innerHTML = content;
                        openModal('guestDetailsModal');
                    } else {
                        alert('Error loading guest details.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading guest details.');
                });
        }

        // Invitation Functions
        function loadAvailableGuests() {
            const eventId = document.getElementById('eventSelect').value;
            const guestsList = document.getElementById('guestsList');

            if (!eventId) {
                guestsList.innerHTML = '<p style="color: #666; font-style: italic;">Please select an event first to see available guests.</p>';
                return;
            }

            guestsList.innerHTML = '<p>Loading guests...</p>';

            fetch(`advisor_dashboard.php?getAvailableGuests=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.guests.length > 0) {
                        let html = '<div class="checkbox-group">';
                        data.guests.forEach(guest => {
                            html += `
                                <label class="checkbox-label">
                                    <input type="checkbox" name="selected_guests[]" value="${guest.guest_id}">
                                    <span class="checkmark"></span>
                                    ${guest.guest_name} (${guest.guest_email})
                                </label>
                            `;
                        });
                        html += '</div>';
                        guestsList.innerHTML = html;
                    } else {
                        guestsList.innerHTML = '<p style="color: #666; font-style: italic;">No guests available. Please add guests first in the "Outside Guests" section.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    guestsList.innerHTML = '<p style="color: red;">Error loading guests. Please try again.</p>';
                });
        }

        function sendInvitations() {
            const form = document.getElementById('invitationForm');
            const formData = new FormData(form);

            const eventId = formData.get('event_id');
            // Get selected guests from checkboxes (since they are dynamically added)
            const selectedGuestsCheckboxes = document.querySelectorAll('input[name="selected_guests[]"]:checked');
            const selectedGuests = Array.from(selectedGuestsCheckboxes).map(cb => cb.value);
            const customMessage = formData.get('custom_message');

            if (!eventId) {
                alert('Please select an event.');
                return;
            }

            if (selectedGuests.length === 0) {
                alert('Please select at least one guest.');
                return;
            }

            // Show loading
            const submitBtn = document.querySelector('#invitations-section .btn-primary');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;

            // Send AJAX request
            fetch('advisor_dashboard.php', {
                method: 'POST',
                body: new URLSearchParams({
                    sendInvitations: '1',
                    event_id: eventId,
                    method: 'email', // Always email
                    selected_guests: selectedGuests.join(','),
                    custom_message: customMessage
                }),
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text(); // Get text first to check for HTML errors
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        alert(`Email invitations sent successfully! ${data.sent_count} emails sent.`);
                        form.reset();
                        loadAvailableGuests(); // Refresh the guests list
                        loadInvitationHistory(); // Refresh history
                    } else {
                        let errorMsg = 'Error sending invitations:\n';
                        if (data.errors && data.errors.length > 0) {
                            errorMsg += data.errors.join('\n');
                        } else if (data.error) {
                            errorMsg += data.error;
                        }
                        alert(errorMsg);
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    alert('Error: Invalid response from server. Please check the console for details.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending invitations. Please try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }

        function loadInvitationHistory() {
            const historyDiv = document.getElementById('invitationsHistory');
            historyDiv.innerHTML = '<p>Loading history...</p>';

            fetch('advisor_dashboard.php?getInvitationHistory=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.history.length > 0) {
                        let html = '<div class="history-items">';
                        data.history.forEach(item => {
                            html += `
                                <div class="history-item">
                                    <div class="history-header">
                                        <strong>${item.event_title}</strong>
                                        <span class="history-date">${new Date(item.sent_at).toLocaleDateString()}</span>
                                    </div>
                                    <div class="history-details">
                                        Guest: ${item.guest_name} (${item.guest_email}) | Sent via Email
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        historyDiv.innerHTML = html;
                    } else {
                        historyDiv.innerHTML = '<p style="color: #666; font-style: italic;">No recent invitations sent.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    historyDiv.innerHTML = '<p style="color: red;">Error loading history.</p>';
                });
        }

        // Load invitation history when invitations section is shown
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we're on the invitations section
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('section') === 'invitations') {
                loadInvitationHistory();
            }
        });
    </script>
</body>
</html>
