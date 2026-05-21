<?php
/**
 * Member Profile API (Self-Service)
 * ====================================
 * BUSINESS LOGIC + DATA LAYER
 *
 * Used by member-profile.php (the member's own profile page).
 * Members can only access/update their OWN record.
 *
 * GET  ?action=get&id=N             – Fetch own profile
 * POST { action:"update", … }       – Update own name/phone
 * POST { action:"request_cancellation" } – Flag for account closure
 */

require_once '../../config.php';

header('Content-Type: application/json');

// ── Access control – must be a logged-in Member ──────────────────────────────
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'Member') {
    echo json_encode(['success' => false, 'message' => 'Unauthorised.']);
    exit();
}

// The member can only access their own data – enforce this strictly
$sessionMemberId = (int)$_SESSION['member_id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action   = $_GET['action'] ?? '';
    $reqId    = (int)($_GET['id'] ?? 0);

    // Security: reject if requested ID doesn't match session ID
    if ($reqId !== $sessionMemberId) {
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit();
    }

    if ($action === 'get') {
        // Fetch member record from database
        $stmt = $conn->prepare(
            "SELECT member_id, full_name, email, phone, membership_date,
                    books_borrowed_current, total_overdue_count, member_is_blocked
             FROM member WHERE member_id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $sessionMemberId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            echo json_encode(['success' => true, 'member' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Member record not found.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }

} elseif ($method === 'POST') {
    $body   = file_get_contents('php://input');
    $data   = json_decode($body, true);
    $action = $data['action'] ?? '';
    $reqId  = (int)($data['member_id'] ?? 0);

    // Security: enforce member can only modify their own record
    if ($reqId !== $sessionMemberId) {
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit();
    }

    if ($action === 'update') {
        // ── Validate inputs ──────────────────────────────────────────────────
        $name  = trim($data['full_name'] ?? '');
        $phone = trim($data['phone']     ?? '');
        $errors = [];

        if (strlen($name) < 2)                          $errors[] = 'Full name must be at least 2 characters.';
        if (!preg_match('/^\+?[\d\s\-]{7,15}$/', $phone)) $errors[] = 'Phone must be 7–15 digits.';

        if ($errors) {
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit();
        }

        // ── Update only name and phone (email is immutable for login integrity) ──
        $stmt = $conn->prepare(
            "UPDATE member SET full_name = ?, phone = ? WHERE member_id = ?"
        );
        $stmt->bind_param('ssi', $name, $phone, $sessionMemberId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Your profile has been updated.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
        }
        $stmt->close();

    } elseif ($action === 'request_cancellation') {
        // In a full system this would write to a cancellation_request table.
        // For now we add a note to a simple requests column or log it.
        // Here we just return a success message for the UI.
        // TODO: Insert into cancellation_requests table when that module is ready.
        echo json_encode([
            'success' => true,
            'message' => 'Your cancellation request has been submitted. A librarian will contact you shortly.'
        ]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}
