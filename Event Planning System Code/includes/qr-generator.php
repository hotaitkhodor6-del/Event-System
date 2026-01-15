<?php
// QR Code Generator with multiple fallback methods

// Helper function to download content via cURL or file_get_contents
function downloadContent($url) {
    // Try cURL first (more reliable)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
    // Fallback to file_get_contents if cURL not available
    if (ini_get('allow_url_fopen')) {
        return @file_get_contents($url);
    }
    
    return false;
}

function generateQRCode($text, $filename = null) {
    // Use Google Charts API to generate QR code
    $encoded = urlencode($text);
    $qr_url = "https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=" . $encoded;
    
    if ($filename) {
        // Download and save the QR code image
        $qr_image = downloadContent($qr_url);
        
        if ($qr_image !== false && !empty($qr_image)) {
            try {
                // Create directory if it doesn't exist
                $directory = dirname($filename);
                if (!is_dir($directory)) {
                    @mkdir($directory, 0755, true);
                }
                
                // Save the image
                if (file_put_contents($filename, $qr_image)) {
                    return $filename;
                }
            } catch (Exception $e) {
                error_log("QR Code save error: " . $e->getMessage());
            }
        }
    }
    
    return $qr_url; // Return URL if file saving fails
}

// Generate QR code as base64 data URI (BEST FOR EMAIL)
function generateQRCodeDataURI($text) {
    $encoded = urlencode($text);
    $qr_url = "https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=" . $encoded;
    
    // Download and convert to base64
    $qr_image = downloadContent($qr_url);
    
    if ($qr_image !== false && !empty($qr_image)) {
        return 'data:image/png;base64,' . base64_encode($qr_image);
    }
    
    return null;
}

// Get QR code URL with error logging
function getQRCodeURL($text) {
    $encoded = urlencode($text);
    return "https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=" . $encoded;
}

// Cleanup old QR codes (delete files older than 30 days)
function cleanupOldQRCodes($directory, $days = 30) {
    if (!is_dir($directory)) {
        return;
    }
    
    $files = glob($directory . '/*.png');
    $now = time();
    $max_age = $days * 24 * 60 * 60;
    
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file) > $max_age)) {
            @unlink($file);
        }
    }
}
?>
