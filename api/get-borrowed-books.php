<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config.php';

$admin_id = $_SESSION['admin_id'];
$member_query = $conn->query("SELECT member_id FROM member WHERE admin_id = $admin_id");
$member = $member_query->fetch_assoc();

if(!$member) {
    echo json_encode(['success' => false, 'message' => 'Member not found']);
    exit();
}

$member_id = $member['member_id'];

$query = "SELECT 
            t.transaction_id,
            b.book_id,
            b.title,
            b.author,
            t.borrow_date,
            t.due_date,
            DATEDIFF(CURDATE(), t.due_date) as days_overdue,
            CASE 
                WHEN t.return_date IS NOT NULL THEN 'Returned'
                WHEN t.due_date < CURDATE() THEN 'Overdue'
                ELSE 'Borrowed'
            END as status,
            t.fine_amount
          FROM transaction t
          JOIN book b ON t.book_id = b.book_id
          WHERE t.member_id = $member_id AND t.return_date IS NULL
          ORDER BY t.due_date ASC";

$result = $conn->query($query);

$books = [];
while($row = $result->fetch_assoc()) {
    $books[] = $row;
}

echo json_encode([
    'success' => true,
    'books' => $books,
    'count' => count($books)
]);
?>