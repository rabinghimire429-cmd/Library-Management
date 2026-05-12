<?php
session_start();

if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'libtech_db');

$id = intval($_GET['id'] ?? 0);

if($id <= 0) {
    die("Invalid transaction ID");
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    $due_date = $_POST['due_date'];

    if(empty($due_date)) {
        die("Due date required");
    }

    $conn->query("UPDATE transaction
                  SET due_date = '$due_date'
                  WHERE transaction_id = $id");

    header('Location: my-borrowings.php');
    exit();
}

$transaction = $conn->query("SELECT * FROM transaction
                             WHERE transaction_id = $id")
                             ->fetch_assoc();
?>
</html>