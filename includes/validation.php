<?php
/**
 * includes/validation.php - Input validation functions
 */

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9+\-\s()]{8,20}$/', $phone);
}

function validateISBN($isbn) {
    // Remove hyphens and spaces
    $isbn = preg_replace('/[-\s]/', '', $isbn);
    return preg_match('/^(97(8|9))?\d{9}(\d|X)$/i', $isbn);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validatePassword($password) {
    return strlen($password) >= 4;
}

function validateCardNumber($number) {
    $number = preg_replace('/\s+/', '', $number);
    return preg_match('/^[0-9]{15,16}$/', $number);
}

function validateExpiryDate($expiry) {
    if(!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry, $matches)) {
        return false;
    }
    $month = $matches[1];
    $year = 2000 + (int)$matches[2];
    $current_year = (int)date('Y');
    $current_month = (int)date('m');
    
    if($year < $current_year) return false;
    if($year == $current_year && $month < $current_month) return false;
    return true;
}

function validateCVV($cvv) {
    return preg_match('/^[0-9]{3,4}$/', $cvv);
}
?>