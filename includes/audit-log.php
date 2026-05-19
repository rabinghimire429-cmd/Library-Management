<?php
/**
 * includes/audit-log.php - Admin action logging
 */

function logAdminAction($conn, $admin_id, $action, $details) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt = $conn->prepare("INSERT INTO audit_log (admin_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issss", $admin_id, $action, $details, $ip, $user_agent);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function getAdminActions($conn, $admin_id = null, $limit = 50) {
    if($admin_id) {
        $stmt = $conn->prepare("SELECT * FROM audit_log WHERE admin_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $admin_id, $limit);
    } else {
        $stmt = $conn->prepare("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
    }
    $stmt->execute();
    return $stmt->get_result();
}
?>