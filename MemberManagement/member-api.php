<?php
/**
 * Member Management API
 * ======================
 * BUSINESS LOGIC LAYER + DATA LAYER
 *
 * This file acts as the REST-style API endpoint for all Member
 * Management operations. It receives JSON (POST) or query-string
 * (GET) requests from the Presentation Layer (member-management.php,
 * member-registration.php, member-profile.php) and communicates
 * directly with the MySQL database.
 *
 * Supported actions:
 *   GET  ?action=list          – List / search / filter / sort members
 *   GET  ?action=stats         – Summary statistics for dashboard
 *   GET  ?action=find&id=N     – Retrieve a single member by ID
 *   POST action=add            – Insert a new member
 *   POST action=update         – Update an existing member's details
 *   POST action=toggle_block   – Block or unblock a member
 *   POST action=delete         – Remove a member record
 *
 * All responses are JSON objects with at minimum:
 *   { "success": true/false, "message": "…" }
 */

// ── Data Layer: Connect to database ─────────────────────────────────────────
require_once '../../config.php';   // Sets $conn (MySQLi) and starts session

// Return JSON only – set correct Content-Type header
header('Content-Type: application/json');

// ── Access Control ───────────────────────────────────────────────────────────
// API requires a logged-in librarian; member self-service goes through member-profile-api.php
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised. Please log in.']);
    exit();
}

// ── Route Request ────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Determine action from query string
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'list':   handleList($conn);  break;
        case 'stats':  handleStats($conn); break;
        case 'find':   handleFind($conn);  break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown GET action.']);
    }

} elseif ($method === 'POST') {
    // Decode JSON body sent by the front-end JavaScript
    $body   = file_get_contents('php://input');
    $data   = json_decode($body, true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'add':          handleAdd($conn, $data);         break;
        case 'update':       handleUpdate($conn, $data);      break;
        case 'toggle_block': handleToggleBlock($conn, $data); break;
        case 'delete':       handleDelete($conn, $data);      break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown POST action.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}


/* ═════════════════════════════════════════════════════════════════════════════
   HANDLER: LIST – Search, Filter, Sort, Paginate
   GET ?action=list&search=&status=&dateFrom=&dateTo=&sortBy=&page=&perPage=
═════════════════════════════════════════════════════════════════════════════ */
function handleList($conn) {
    // ── Read and sanitise query parameters ──────────────────────────────────
    $search   = trim($_GET['search']   ?? '');
    $status   = trim($_GET['status']   ?? '');
    $dateFrom = trim($_GET['dateFrom'] ?? '');
    $dateTo   = trim($_GET['dateTo']   ?? '');
    $page     = max(1, (int)($_GET['page']    ?? 1));
    $perPage  = max(1, min(100, (int)($_GET['perPage'] ?? 10)));
    $offset   = ($page - 1) * $perPage;

    // Whitelist sortable columns to prevent SQL injection
    $allowed  = ['member_id','full_name','membership_date','total_overdue_count','email'];
    $sortBy   = in_array($_GET['sortBy'] ?? '', $allowed) ? $_GET['sortBy'] : 'member_id';

    // ── Build WHERE clause dynamically ──────────────────────────────────────
    $conditions = [];
    $params     = [];
    $types      = '';

    // Full-text search across name and email columns
    if ($search !== '') {
        $conditions[] = '(full_name LIKE ? OR email LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $types   .= 'ss';
    }

    // Filter by blocked status
    if ($status === 'active') {
        $conditions[] = 'member_is_blocked = 0';
    } elseif ($status === 'blocked') {
        $conditions[] = 'member_is_blocked = 1';
    }

    // Filter by membership date range
    if ($dateFrom !== '') {
        $conditions[] = 'membership_date >= ?';
        $params[]      = $dateFrom;
        $types        .= 's';
    }
    if ($dateTo !== '') {
        $conditions[] = 'membership_date <= ?';
        $params[]      = $dateTo;
        $types        .= 's';
    }

    $where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // ── Count total results (for pagination) ────────────────────────────────
    $countSql  = "SELECT COUNT(*) AS total FROM member $where";
    $countStmt = $conn->prepare($countSql);
    if ($params) $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    // ── Fetch paginated rows ─────────────────────────────────────────────────
    $sql  = "SELECT member_id, full_name, email, phone, membership_date,
                    books_borrowed_current, total_overdue_count, member_is_blocked
             FROM member
             $where
             ORDER BY $sortBy ASC
             LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);

    // Append pagination params
    $params[] = $perPage;
    $params[] = $offset;
    $types   .= 'ii';

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result  = $stmt->get_result();
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'members' => $members,
        'total'   => (int)$total,
        'perPage' => $perPage,
        'page'    => $page
    ]);
}


/* ═════════════════════════════════════════════════════════════════════════════
   HANDLER: STATS – Dashboard summary counts
   GET ?action=stats
═════════════════════════════════════════════════════════════════════════════ */
function handleStats($conn) {
    // Run four aggregate queries for the stat cards
    $sql  = "SELECT
                COUNT(*)                                     AS total,
                SUM(member_is_blocked = 0)                   AS active,
                SUM(member_is_blocked = 1)                   AS blocked,
                SUM(total_overdue_count > 0)                 AS with_overdue
             FROM member";
    $result = $conn->query($sql);
    $stats  = $result->fetch_assoc();

    echo json_encode(['success' => true, 'stats' => $stats]);
}


/* ═════════════════════════════════════════════════════════════════════════════
   HANDLER: FIND – Retrieve a single member by ID
   GET ?action=find&id=N
═════════════════════════════════════════════════════════════════════════════ */
function handleFind($conn) {
    // Validate ID parameter
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid member ID.']);
        return;
    }

    $stmt = $conn->prepare(
        "SELECT member_id, full_name, email, phone, membership_date,
                books_borrowed_current, total_overdue_count, member_is_blocked
         FROM member WHERE member_id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Member not found.']);
        return;
    }

    echo json_encode(['success' => true, 'member' => $row]);
}


/* ═════════════════════════════════════════════════════════════════════════════
   HANDLER: ADD – Insert a new member
   POST { action:"add", full_name, email, phone, membership_date }
═════════════════════════════════════════════════════════════════════════════ */
function handleAdd($conn, $data) {
    // ── Server-side validation (mirrors client-side rules) ───────────────────
    $errors = validateMemberData($data);
    if ($errors) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        return;
    }

    // Sanitise inputs
    $name = trim($data['full_name']);
    $email = trim($data['email']);
    $phone = trim($data['phone']);
    $date  = $data['membership_date'];

    // ── Check for duplicate email ────────────────────────────────────────────
    $check = $conn->prepare("SELECT member_id FROM member WHERE email = ? LIMIT 1");
    $check->bind_param('s', $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'A member with this email already exists.']);
        $check->close();
        return;
    }
    $check->close();

    // ── Insert into database ─────────────────────────────────────────────────
    $stmt = $conn->prepare(
        "INSERT INTO member (full_name, email, phone, membership_date,
                             books_borrowed_current, total_overdue_count, member_is_blocked)
         VALUES (?, ?, ?, ?, 0, 0, 0)"
    );
    $stmt->bind_param('ssss', $name, $email, $phone, $date);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Member \"$name\" added successfully."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
}


/* ═════════════════════════════════════════════════════════════════════════════
   HANDLER: UPDATE – Edit an existing member's details
   POST { action:"update", member_id, full_name, email, phone, membership_date }
═════════════════════════════════════════════════════════════════════════════ */
function handleUpdate($conn, $data) {
    // Validate member ID
    $id = (int)($data['member_id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid member ID.']);
        return;
    }

    // ── Server-side validation ───────────────────────────────────────────────
    $errors = validateMemberData($data);
    if ($errors) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        return;
    }

    $name  = trim($data['full_name']);
    $email = trim($data['email']);
    $phone = trim($data['phone']);
    $date  = $data['membership_date'];

    // ── Check for duplicate email (excluding current member) ─────────────────
    $check = $conn->prepare(
        "SELECT member_id FROM member WHERE email = ? AND member_id <> ? LIMIT 1"
    );
    $check->bind_param('si', $email, $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Another member already uses this email.']);
        $check->close();
        return;
    }
    $check->close();

    // ── Update record ────────────────────────────────────────────────────────
    $stmt = $conn->prepare(
        "UPDATE member
         SET full_name = ?, email = ?, phone = ?, membership_date = ?
         WHERE member_id = ?"
    );
    $stmt->bind_param('ssssi', $name, $email, $phone, $date, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Member \"$name\" updated successfully."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
}


/* ═════════════════════════════════════════════════════════════════════════════
   HANDLER: TOGGLE BLOCK – Block or Unblock a member
   POST { action:"toggle_block", member_id, blocked: 0|1 }
═════════════════════════════════════════════════════════════════════════════ */
function handleToggleBlock($conn, $data) {
    $id      = (int)($data['member_id'] ?? 0);
    $blocked = (int)($data['blocked']   ?? 0); // 1 = block, 0 = unblock

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid member ID.']);
        return;
    }

    // Make sure member exists before updating
    $check = $conn->prepare("SELECT member_id FROM member WHERE member_id = ? LIMIT 1");
    $check->bind_param('i', $id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Member not found.']);
        $check->close();
        return;
    }
    $check->close();

    // Update the blocked flag
    $stmt = $conn->prepare("UPDATE member SET member_is_blocked = ? WHERE member_id = ?");
    $stmt->bind_param('ii', $blocked, $id);

    if ($stmt->execute()) {
        $label = $blocked ? 'blocked' : 'unblocked';
        echo json_encode(['success' => true, 'message' => "Member has been $label."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
}


/* ═════════════════════════════════════════════════════════════════════════════
   HANDLER: DELETE – Remove a member record
   POST { action:"delete", member_id }
═════════════════════════════════════════════════════════════════════════════ */
function handleDelete($conn, $data) {
    $id = (int)($data['member_id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid member ID.']);
        return;
    }

    // Safety check: prevent deletion if the member has active borrows
    $borrowCheck = $conn->prepare(
        "SELECT COUNT(*) AS cnt FROM transaction WHERE member_id = ? AND return_date IS NULL"
    );
    // Gracefully skip this check if transaction table doesn't exist yet
    if ($borrowCheck) {
        $borrowCheck->bind_param('i', $id);
        $borrowCheck->execute();
        $cnt = $borrowCheck->get_result()->fetch_assoc()['cnt'];
        $borrowCheck->close();

        if ($cnt > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete: member has ' . $cnt . ' unreturned book(s).'
            ]);
            return;
        }
    }

    // Delete the member
    $stmt = $conn->prepare("DELETE FROM member WHERE member_id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Member deleted successfully.']);
    } elseif ($stmt->affected_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Member not found.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
}


/* ═════════════════════════════════════════════════════════════════════════════
   UTILITY: SERVER-SIDE INPUT VALIDATION
   Returns array of error strings; empty array means valid.
═════════════════════════════════════════════════════════════════════════════ */
function validateMemberData($data) {
    $errors = [];

    // Full Name – required, at least 2 chars
    $name = trim($data['full_name'] ?? '');
    if (strlen($name) < 2) {
        $errors[] = 'Full name must be at least 2 characters.';
    }

    // Email – required, valid format
    $email = trim($data['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    // Phone – required, 7-15 chars, digits / spaces / hyphens / leading +
    $phone = trim($data['phone'] ?? '');
    if (!preg_match('/^\+?[\d\s\-]{7,15}$/', $phone)) {
        $errors[] = 'Phone number must be 7–15 digits (optional leading +).';
    }

    // Membership Date – required, valid calendar date
    $date = $data['membership_date'] ?? '';
    if (!$date || !strtotime($date)) {
        $errors[] = 'A valid membership date is required.';
    }

    return $errors;
}
