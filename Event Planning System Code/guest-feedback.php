<?php
include 'database/config.php';

// Check if event and guest parameters are provided
if (!isset($_GET['event']) || !isset($_GET['guest']) || empty($_GET['event']) || empty($_GET['guest'])) {
    die("Invalid feedback link. Please contact the event organizer.");
}

$event_id = mysqli_real_escape_string($con, $_GET['event']);
$guest_id = mysqli_real_escape_string($con, $_GET['guest']);

// Verify that the guest was invited to this event
$verify_query = "SELECT ig.*, e.event_title, e.event_date, e.start_time, e.end_time,
                        r.room_name, r.location, u.name as advisor_name
                 FROM invited_guests ig
                 JOIN event_invitations ei ON ig.guest_id = ei.guest_id
                 JOIN events e ON ei.event_id = e.event_id
                 JOIN event_requests er ON e.request_id = er.request_id
                 JOIN rooms r ON e.room_id = r.room_id
                 JOIN users u ON er.advisor_id = u.user_id
                 WHERE ig.guest_id = '$guest_id' AND ei.event_id = '$event_id'";

$verify_result = mysqli_query($con, $verify_query);

if (!$verify_result || mysqli_num_rows($verify_result) == 0) {
    die("You are not authorized to provide feedback for this event.");
}

$invitation_data = mysqli_fetch_assoc($verify_result);

// Check if feedback already exists
$feedback_check_query = "SELECT * FROM feedback WHERE guest_id = '$guest_id' AND event_id = '$event_id'";
$feedback_check_result = mysqli_query($con, $feedback_check_query);
$feedback_exists = mysqli_num_rows($feedback_check_result) > 0;

$message = '';
$rating = '';
$comments = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comments = mysqli_real_escape_string($con, trim($_POST['comments']));

    if ($rating < 1 || $rating > 5) {
        $message = '<div class="alert alert-error">Please select a rating between 1 and 5 stars.</div>';
    } elseif (empty($comments)) {
        $message = '<div class="alert alert-error">Please provide your comments.</div>';
    } else {
        if ($feedback_exists) {
            // Update existing feedback
            $update_query = "UPDATE feedback SET rating = '$rating', comments = '$comments', submitted_at = NOW(), status = 'pending'
                           WHERE guest_id = '$guest_id' AND event_id = '$event_id'";
            if (mysqli_query($con, $update_query)) {
                $message = '<div class="alert alert-success">Thank you! Your feedback has been updated successfully.</div>';
            } else {
                $message = '<div class="alert alert-error">Error updating feedback. Please try again.</div>';
            }
        } else {
            // Insert new feedback
            $insert_query = "INSERT INTO feedback (guest_id, event_id, rating, comments, submitted_at, status)
                           VALUES ('$guest_id', '$event_id', '$rating', '$comments', NOW(), 'pending')";
            if (mysqli_query($con, $insert_query)) {
                $message = '<div class="alert alert-success">Thank you! Your feedback has been submitted successfully.</div>';
            } else {
                $message = '<div class="alert alert-error">Error submitting feedback. Please try again.</div>';
            }
        }
    }
}

// Get existing feedback if any
if ($feedback_exists) {
    $existing_feedback = mysqli_fetch_assoc($feedback_check_result);
    $rating = $existing_feedback['rating'];
    $comments = $existing_feedback['comments'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Feedback - <?php echo htmlspecialchars($invitation_data['event_title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .event-details {
            background: #f8fafc;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .event-details h2 {
            color: #1f2937;
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #6b7280;
            font-size: 1rem;
        }

        .feedback-form {
            background: #f8fafc;
            border-radius: 10px;
            padding: 30px;
            border: 1px solid #e2e8f0;
        }

        .feedback-form h2 {
            color: #1f2937;
            margin-bottom: 25px;
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
        }

        .rating-section {
            margin-bottom: 30px;
            text-align: center;
        }

        .rating-label {
            display: block;
            margin-bottom: 15px;
            font-weight: 600;
            color: #374151;
            font-size: 1.1rem;
        }

        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .star {
            font-size: 2rem;
            color: #d1d5db;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .star:hover,
        .star.active {
            color: #fbbf24;
            transform: scale(1.1);
        }

        .rating-text {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
            transition: border-color 0.3s ease;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #4f46e5;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 150px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .button-group {
            text-align: center;
            margin-top: 30px;
        }

        .guest-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .guest-info i {
            color: #3b82f6;
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .content {
                padding: 20px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .rating-stars {
                gap: 8px;
            }

            .star {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-star"></i> Event Feedback</h1>
            <p>Share your experience with us</p>
        </div>

        <div class="content">
            <div class="guest-info">
                <i class="fas fa-user"></i>
                <strong>Welcome, <?php echo htmlspecialchars($invitation_data['guest_name']); ?>!</strong>
                Thank you for attending our event. Your feedback helps us improve future events.
            </div>

            <div class="event-details">
                <h2><i class="fas fa-calendar-alt"></i> Event Details</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Event Title</span>
                        <span class="detail-value"><?php echo htmlspecialchars($invitation_data['event_title']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date & Time</span>
                        <span class="detail-value">
                            <?php echo date('l, F j, Y', strtotime($invitation_data['event_date'])); ?><br>
                            <?php echo date('g:i A', strtotime($invitation_data['start_time'])) . ' - ' . date('g:i A', strtotime($invitation_data['end_time'])); ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Location</span>
                        <span class="detail-value"><?php echo htmlspecialchars($invitation_data['room_name'] . ' (' . $invitation_data['location'] . ')'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Advisor</span>
                        <span class="detail-value"><?php echo htmlspecialchars($invitation_data['advisor_name']); ?></span>
                    </div>
                </div>
            </div>

            <?php echo $message; ?>

            <form class="feedback-form" method="POST" action="">
                <h2><i class="fas fa-comments"></i> Your Feedback</h2>

                <div class="rating-section">
                    <label class="rating-label">How would you rate this event?</label>
                    <div class="rating-stars" id="ratingStars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo ($rating >= $i) ? 'active' : ''; ?>" data-rating="<?php echo $i; ?>">
                                <i class="fas fa-star"></i>
                            </span>
                        <?php endfor; ?>
                    </div>
                    <div class="rating-text" id="ratingText">
                        <?php
                        if ($rating == 1) echo "Poor";
                        elseif ($rating == 2) echo "Fair";
                        elseif ($rating == 3) echo "Good";
                        elseif ($rating == 4) echo "Very Good";
                        elseif ($rating == 5) echo "Excellent";
                        else echo "Select a rating";
                        ?>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="<?php echo $rating; ?>">
                </div>

                <div class="form-group">
                    <label for="comments">Comments & Suggestions</label>
                    <textarea name="comments" id="comments" placeholder="Please share your thoughts about the event. What did you like? What could be improved? Any suggestions for future events?" required><?php echo htmlspecialchars($comments); ?></textarea>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> <?php echo $feedback_exists ? 'Update Feedback' : 'Submit Feedback'; ?>
                    </button>
                    <a href="https://khodorhoteit.eu" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Website
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Rating system
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        const ratingText = document.getElementById('ratingText');
        const ratingTexts = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                ratingInput.value = rating;
                ratingText.textContent = ratingTexts[rating];

                // Update star display
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });

            star.addEventListener('mouseover', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                ratingText.textContent = ratingTexts[rating];
            });
        });

        // Reset rating text on mouse leave
        document.getElementById('ratingStars').addEventListener('mouseleave', function() {
            const currentRating = parseInt(ratingInput.value);
            ratingText.textContent = ratingTexts[currentRating] || 'Select a rating';
        });
    </script>
</body>
</html>