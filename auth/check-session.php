<?php
session_start();

header('Content-Type: application/json');

if(isset($_SESSION['admin_id']) && isset($_SESSION['admin_role'])) {
    echo json_encode([
        'logged_in' => true,
        'role' => $_SESSION['admin_role'],
        'email' => $_SESSION['admin_email']
    ]);
} else {
    echo json_encode([
        'logged_in' => false
    ]);
}
?>