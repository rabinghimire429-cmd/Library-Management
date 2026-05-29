<?php

// Start session
session_start();

// Check if user is logged in and is a Librarian
if(!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'Librarian') {
    header('Location: ../index.php');
    exit();
}

// Connect to database
require_once '../config.php';

// Get book ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if ID is valid
if($id > 0) {

    // Get book title before deleting
    $stmt = $conn->prepare("SELECT title FROM book WHERE book_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Check if book exists
    if($book) {

        // Delete book from database
        $delete_stmt = $conn->prepare("DELETE FROM book WHERE book_id = ?");
        $delete_stmt->bind_param("i", $id);
        $delete_stmt->execute();
        $delete_stmt->close();

        // Save delete action in log
        logAdminAction($conn, $_SESSION['admin_id'], 'DELETE_BOOK', "Deleted book ID $id: " . $book['title']);
    }
}

// Return to search page
header('Location: search-books.php?msg=deleted');
exit();

?>