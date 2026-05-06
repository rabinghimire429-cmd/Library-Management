<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Librarian') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../config.php';

$query = "SELECT 
            t.transaction_id,
            m.member_id,
            m.full_name as member_name,
            m.email as member_email,
            m.phone as member_phone,
            b.book_id,
            b.title as book_title,
            b.author as book_author,
            t.borrow_date,
            t.due_date,
            DATEDIFF(CURDATE(), t.due_date) as days_overdue,
            DATEDIFF(CURDATE(), t.due_date) * 0.50 as fine_amount
          FROM transaction t
          JOIN member m ON t.member_id = m.member_id
          JOIN book b ON t.book_id = b.book_id
          WHERE t.return_date IS NULL AND t.due_date < CURDATE()
          ORDER BY t.due_date ASC";

$result = $conn->query($query);

$overdue_books = [];
$total_fines = 0;
$unique_members = [];

while($row = $result->fetch_assoc()) {
    $overdue_books[] = $row;
    $total_fines += $row['fine_amount'];
    $unique_members[$row['member_id']] = true;
}

echo json_encode([
    'success' => true,
    'overdue_books' => $overdue_books,
    'total_overdue' => count($overdue_books),
    'total_members' => count($unique_members),
    'total_fines' => number_format($total_fines, 2)
]);
?>