<?php
/**
 * includes/2fa.php - Two-Factor Authentication Functions
 */

/**
 * Generate a random 6-digit code for 2FA
 */
function generate2FACode() {
    return sprintf("%06d", mt_rand(1, 999999));
}

/**
 * Send 2FA code via email (simulated for demo)
 */
function send2FACodeByEmail($email, $code) {
    // For demo purposes, store in session to display in console
    $_SESSION['2fa_code'] = $code;
    $_SESSION['2fa_email'] = $email;
    $_SESSION['2fa_expires'] = time() + 300; // 5 minutes expiry
    
    // Log that code was sent (for debugging)
    error_log("2FA code for $email: $code");
    
    return true;
}

/**
 * Verify 2FA code
 */
function verify2FACode($user_input, $stored_code, $expiry_time) {
    // Check if code has expired
    if ($expiry_time < time()) {
        return ['success' => false, 'message' => '2FA code has expired. Please login again.'];
    }
    
    // Check if code matches
    if ($user_input == $stored_code) {
        return ['success' => true, 'message' => 'Code verified successfully'];
    }
    
    return ['success' => false, 'message' => 'Invalid verification code. Please try again.'];
}

/**
 * Enable 2FA for a user
 */
function enable2FAForUser($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE admin SET two_factor_enabled = 1 WHERE User_id = ?");
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

/**
 * Disable 2FA for a user
 */
function disable2FAForUser($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE admin SET two_factor_enabled = 0, two_factor_secret = NULL WHERE User_id = ?");
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

/**
 * Check if user has 2FA enabled
 */
function is2FAEnabled($conn, $user_id) {
    $stmt = $conn->prepare("SELECT two_factor_enabled FROM admin WHERE User_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row && $row['two_factor_enabled'] == 1;
}
?>