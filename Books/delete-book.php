<?php
session_start();
if(!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'Librarian') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id > 0) {
    // Get book title before deleting for audit log
    $stmt = $conn->prepare("SELECT title FROM book WHERE book_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if($book) {
        // Delete using prepared statement
        $delete_stmt = $conn->prepare("DELETE FROM book WHERE book_id = ?");
        $delete_stmt->bind_param("i", $id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Log the action
        logAdminAction($conn, $_SESSION['admin_id'], 'DELETE_BOOK', "Deleted book ID $id: " . $book['title']);
    }
}

// Redirect back
header('Location: search-books.php?msg=deleted');
exit();
?>