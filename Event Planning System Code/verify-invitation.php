<?php
session_start();
include 'database/config.php';

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid invitation link. Please contact the event organizer.");
}

$token = mysqli_real_escape_string($con, $_GET['token']);

// For now, since we don't have a token field in the database,
// we'll show a generic verification page
// In a real implementation, you'd verify the token against the database

// You could implement token verification by:
// 1. Storing tokens in a new table, or
// 2. Using a hash-based verification system

// For demonstration, we'll show a verification page
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Event Invitation</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verification-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .verification-icon {
            font-size: 4rem;
            color: #10b981;
            margin-bottom: 20px;
        }

        .verification-title {
            font-size: 2rem;
            color: #1f2937;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .verification-subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .invitation-details {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            color: #374151;
        }

        .detail-value {
            color: #6b7280;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-valid {
            background: #d1fae5;
            color: #065f46;
        }

        .action-buttons {
            margin-top: 30px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 10px;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .error-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .error-icon {
            font-size: 4rem;
            color: #ef4444;
            margin-bottom: 20px;
        }

        .qr-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }

        .qr-info i {
            color: #3b82f6;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-icon">
            <i class="fas fa-qrcode"></i>
        </div>
        <h1 class="verification-title">Invitation Verified!</h1>
        <p class="verification-subtitle">Your QR code has been successfully scanned</p>

        <div class="qr-info">
            <i class="fas fa-info-circle"></i>
            <strong>QR Code Scanned:</strong> Token <?php echo htmlspecialchars(substr($token, 0, 8)) . '...'; ?>
        </div>

        <div class="invitation-details">
            <h3 style="margin-bottom: 15px; color: #1f2937; font-size: 1.2rem;">Invitation Details</h3>

            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="status-badge status-valid">Valid Invitation</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Verification Time:</span>
                <span class="detail-value"><?php echo date('M d, Y H:i:s'); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Token ID:</span>
                <span class="detail-value"><?php echo htmlspecialchars(substr($token, 0, 16)) . '...'; ?></span>
            </div>
        </div>

        <div class="action-buttons">
            <button class="btn btn-primary" onclick="confirmAttendance()">
                <i class="fas fa-check"></i> Confirm Attendance
            </button>
            <a href="https://khodorhoteit.eu" class="btn btn-secondary">
                <i class="fas fa-home"></i> Visit Website
            </a>
        </div>
    </div>

    <script>
        function confirmAttendance() {
            if (confirm('Are you sure you want to confirm your attendance for this event?')) {
                alert('Thank you! Your attendance has been confirmed.');
                // Here you would typically send an AJAX request to update the database
                // For now, we'll just show a success message
            }
        }
    </script>
</body>
</html>