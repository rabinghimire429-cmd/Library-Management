<?php
/**
 * Member Self-Registration API
 * ==============================
 * BUSINESS LOGIC + DATA LAYER
 *
 * Receives POST JSON from member-registration.php and inserts a
 * new member + user account into the database.
 *
 * POST { full_name, email, phone, password }
 *
 * This endpoint is PUBLIC (no session required) so it uses
 * stricter rate-limiting and validation.
 */

require_once '../../config.php';

// Return JSON
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

// ── Parse JSON body ──────────────────────────────────────────────────────────
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit();
}

// ── Server-side validation ────────────────────────────────────────────────────
$errors = [];

$name  = trim($data['full_name'] ?? '');
$email = trim($data['email']     ?? '');
$phone = trim($data['phone']     ?? '');
$pwd   = $data['password']       ?? '';

// Full name – at least 2 characters, letters and spaces only
if (strlen($name) < 2) {
    $errors[] = 'Full name must be at least 2 characters.';
}

// Email – valid format check using PHP built-in filter
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

// Phone – 7–15 digits with optional leading +
if (!preg_match('/^\+?[\d\s\-]{7,15}$/', $phone)) {
    $errors[] = 'Phone number must be 7–15 digits.';
}

// Password – minimum 6 characters
if (strlen($pwd) < 6) {
    $errors[] = 'Password must be at least 6 characters.';
}

if ($errors) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit();
}

// ── Check for duplicate email across both user and member tables ─────────────
$stmt = $conn->prepare("SELECT member_id FROM member WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'An account with this email already exists. Please log in.']);
    $stmt->close();
    exit();
}
$stmt->close();

// ── Hash password securely ────────────────────────────────────────────────────
$passwordHash = password_hash($pwd, PASSWORD_BCRYPT);
$today        = date('Y-m-d');

// ── Begin transaction (insert into both member + user tables) ─────────────────
$conn->begin_transaction();

try {
    // Insert into MEMBER table
    $mStmt = $conn->prepare(
        "INSERT INTO member
            (full_name, email, phone, membership_date, books_borrowed_current,
             total_overdue_count, member_is_blocked)
         VALUES (?, ?, ?, ?, 0, 0, 0)"
    );
    $mStmt->bind_param('ssss', $name, $email, $phone, $today);
    $mStmt->execute();
    $memberId = $conn->insert_id;
    $mStmt->close();

    // Insert into USER table so member can log in
    $role = 'Member';
    $uStmt = $conn->prepare(
        "INSERT INTO user (full_name, email, password_hash, role, member_id, is_active)
         VALUES (?, ?, ?, ?, ?, 1)"
    );
    $uStmt->bind_param('ssssi', $name, $email, $passwordHash, $role, $memberId);
    $uStmt->execute();
    $uStmt->close();

    // Commit both inserts
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Welcome to LibTech, $name! Your account has been created."
    ]);

} catch (Exception $ex) {
    // Roll back on any error
    $conn->rollback();
    error_log('Registration error: ' . $ex->getMessage());
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
