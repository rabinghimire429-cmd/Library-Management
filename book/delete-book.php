<?php

// Start session
session_start();

// Check admin login
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'libtech_db');

// Get book id
$id = $_GET['id'];

// Delete book from database
$conn->query("DELETE FROM book WHERE book_id = $id");

// Redirect back to search page
header('Location: search-books.php');
exit();
?>