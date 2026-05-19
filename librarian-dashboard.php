<?php
/**
 
 * 
 * FUNCTIONALITY: List, Display statistics, overdue preview
 * ETHICS: Transparency - Help section explains librarian responsibilities
 * SECURITY: Role-based access control (only Librarian can access)
 */

session_start();
if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Librarian') {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];

// Get librarian details
$member_query = $conn->query("SELECT * FROM member WHERE admin_id = $admin_id");
$member = $member_query->fetch_assoc();

if($member && !empty($member['full_name'])) {
    $name = $member['full_name'];
} else {
    $email_parts = explode('@', $admin_email);
    $name = ucfirst($email_parts[0]) . ' (Librarian)';
}
$librarian_id = $member['member_id'] ?? 0;
$librarian_phone = $member['phone'] ?? '';

// Get statistics
$total_books = $conn->query("SELECT COUNT(*) as count FROM book")->fetch_assoc()['count'];
$total_members = $conn->query("SELECT COUNT(*) as count FROM member")->fetch_assoc()['count'];
$active_borrowings = $conn->query("SELECT COUNT(*) as count FROM transaction WHERE return_date IS NULL")->fetch_assoc()['count'];
$overdue_count = $conn->query("SELECT COUNT(*) as count FROM transaction WHERE return_date IS NULL AND due_date < CURDATE()")->fetch_assoc()['count'];

// Get recent overdue books for preview
$overdue_preview = $conn->query("SELECT t.*, m.full_name as member_name, b.title as book_title 
                                  FROM transaction t 
                                  JOIN member m ON t.member_id = m.member_id 
                                  JOIN book b ON t.book_id = b.book_id 
                                  WHERE t.return_date IS NULL AND t.due_date < CURDATE() 
                                  LIMIT 5");

// Update profile
$update_msg = '';
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $conn->query("UPDATE member SET full_name = '$full_name', phone = '$phone' WHERE member_id = $librarian_id");
    $update_msg = "<div class='success-msg'>✅ Profile updated successfully!</div>";
    $name = $full_name;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - LibTech Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
            color: #e4e6eb;
        }
        
        /* Navigation Bar */
        .navbar {
            background: rgba(15,23,42,0.95);
            backdrop-filter: blur(12px);
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo-img { height: 45px; width: auto; border-radius: 10px; }
        .logo-text { font-size: 22px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Profile Dropdown */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }
        .profile-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            padding: 6px 15px 6px 8px;
            cursor: pointer;
        }
        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            color: white;
        }
        .profile-name { color: white; font-weight: 500; }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 55px;
            background: rgba(20,20,50,0.98);
            backdrop-filter: blur(12px);
            min-width: 220px;
            border-radius: 16px;
            padding: 10px 0;
            border: 1px solid rgba(255,255,255,0.1);
            z-index: 200;
        }
        .profile-dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #e4e6eb;
            text-decoration: none;
        }
        .dropdown-content a:hover { background: rgba(99,102,241,0.3); }
        .logout-btn { color: #f87171 !important; }
        
        /* Main Container */
        .container { max-width: 1400px; margin: 40px auto; padding: 0 40px; }
        
        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(236,72,153,0.15));
            border-radius: 40px;
            padding: 45px;
            margin-bottom: 40px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .welcome-section h1 { font-size: 36px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .welcome-section p { color: #b9bbbe; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 50px; }
        .stat-card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 30px; text-align: center; transition: all 0.3s; position: relative; }
        .stat-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.08); border-color: #6366f1; }
        .stat-number { font-size: 48px; font-weight: 800; background: linear-gradient(135deg, #818cf8, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 10px; }
        .stat-label { color: #b9bbbe; font-size: 14px; }
        .warning-badge { position: absolute; top: 15px; right: 15px; background: #ef4444; color: white; font-size: 11px; padding: 4px 10px; border-radius: 20px; }
        
        /* Menu Grid */
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin-bottom: 50px; }
        .menu-card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 30px; text-align: center; text-decoration: none; transition: all 0.3s; display: block; }
        .menu-card:hover { transform: translateY(-8px); background: rgba(255,255,255,0.1); border-color: #6366f1; }
        .menu-icon { font-size: 48px; margin-bottom: 15px; }
        .menu-card h3 { font-size: 18px; font-weight: 600; color: white; margin-bottom: 8px; }
        .menu-card p { color: #8b8d94; font-size: 12px; }
        
        /* Overdue Preview Section */
        .overdue-section { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 30px; margin-top: 20px; }
        .overdue-section h3 { font-size: 20px; margin-bottom: 20px; color: #f87171; }
        .overdue-list { display: flex; flex-direction: column; gap: 12px; }
        .overdue-item { background: rgba(255,255,255,0.03); border-radius: 16px; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .overdue-member { font-weight: 600; }
        .overdue-book { font-size: 14px; color: rgba(255,255,255,0.8); }
        .overdue-days { color: #f87171; font-weight: 600; }
        .view-all { text-align: center; margin-top: 20px; }
        .view-all a { color: #818cf8; text-decoration: none; }
        
        /* Help Section - Ethics: Transparency */
        .help-section { background: rgba(99,102,241,0.08); border-radius: 24px; padding: 25px; margin-top: 40px; border: 1px solid rgba(99,102,241,0.2); }
        .help-section h3 { color: #a78bfa; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .help-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .help-card { background: rgba(255,255,255,0.03); border-radius: 16px; padding: 15px; }
        .help-card strong { display: block; margin-bottom: 8px; }
        .help-card p { font-size: 13px; color: #b9bbbe; line-height: 1.5; }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: rgba(20,20,50,0.98); border-radius: 30px; padding: 30px; width: 450px; max-width: 90%; border: 1px solid rgba(255,255,255,0.2); }
        .modal-content h3 { margin-bottom: 20px; color: #a78bfa; }
        .modal-content input { width: 100%; padding: 12px; margin: 10px 0; background: #0f1419; border: 1px solid #2d3139; border-radius: 12px; color: #e4e6eb; }
        .modal-content button { width: 100%; padding: 12px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 12px; color: white; cursor: pointer; }
        .close-btn { float: right; font-size: 24px; cursor: pointer; color: #888; }
        .success-msg { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        
        /*