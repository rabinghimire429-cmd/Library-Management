<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'libtech_db');
$id = $_GET['id'];
$conn->query("DELETE FROM book WHERE book_id = $id");
header('Location: search-books.php');
exit();
?>