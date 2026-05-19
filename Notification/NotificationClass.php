<?php
// Notification/NotificationClass.php - Uses read_status column
class NotificationManager {
    private $conn;
    private $status_column = 'read_status'; // Using read_status column
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    // ADD - Create notification
    public function addNotification($member_id, $message, $type) {
        $errors = $this->validateNotification($member_id, $message, $type);
        if(!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $sent_date = date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare("INSERT INTO notification (member_id, message, notification_type, sent_date, read_status) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("isss", $member_id, $message, $type, $sent_date);
        
        if($stmt->execute()) {
            return ['success' => true, 'notification_id' => $stmt->insert_id];
        }
        return ['success' => false, 'errors' => ['Database error: ' . $stmt->error]];
    }
    
    // LIST - Get notifications with filters
    public function listNotifications($member_id, $type = 'all', $status_filter = 'all', $limit = 100) {
        $query = "SELECT * FROM notification WHERE member_id = ?";
        $params = [$member_id];
        $types = "i";
        
        if($type != 'all') {
            $query .= " AND notification_type = ?";
            $params[] = strtoupper($type);
            $types .= "s";
        }
        
        if($status_filter != 'all') {
            $status_value = ($status_filter == 'unread') ? 1 : 0;
            $query .= " AND read_status = ?";
            $params[] = $status_value;
            $types .= "i";
        }
        
        $query .= " ORDER BY sent_date DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        return $notifications;
    }
    
    // FIND - Get single notification
    public function findNotification($notification_id) {
        $stmt = $this->conn->prepare("SELECT * FROM notification WHERE notification_id = ?");
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    // EDIT - Update notification
    public function editNotification($notification_id, $message, $type) {
        $allowed_types = ['BORROW', 'REMINDER', 'OVERDUE', 'FINE'];
        if(!in_array($type, $allowed_types)) {
            return ['success' => false, 'errors' => ['Invalid notification type']];
        }
        
        if(empty($message) || strlen($message) > 255) {
            return ['success' => false, 'errors' => ['Message must be 1-255 characters']];
        }
        
        $stmt = $this->conn->prepare("UPDATE notification SET message = ?, notification_type = ? WHERE notification_id = ?");
        $stmt->bind_param("ssi", $message, $type, $notification_id);
        
        if($stmt->execute()) {
            return ['success' => true];
        }
        return ['success' => false, 'errors' => ['Update failed']];
    }
    
    // DELETE - Remove notification
    public function deleteNotification($notification_id) {
        $stmt = $this->conn->prepare("DELETE FROM notification WHERE notification_id = ?");
        $stmt->bind_param("i", $notification_id);
        return $stmt->execute();
    }
    
    // MARK AS READ
    public function markAsRead($notification_id) {
        $stmt = $this->conn->prepare("UPDATE notification SET read_status = 0 WHERE notification_id = ?");
        $stmt->bind_param("i", $notification_id);
        return $stmt->execute();
    }
    
    // GET UNREAD COUNT
    public function getUnreadCount($member_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM notification WHERE member_id = ? AND read_status = 1");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        return $data['count'] ?? 0;
    }
    
    // VALIDATION
    private function validateNotification($member_id, $message, $type) {
        $errors = [];
        
        $check = $this->conn->prepare("SELECT member_id FROM member WHERE member_id = ?");
        $check->bind_param("i", $member_id);
        $check->execute();
        if($check->get_result()->num_rows == 0) {
            $errors[] = "Member does not exist";
        }
        
        if(empty($message)) {
            $errors[] = "Message cannot be empty";
        } elseif(strlen($message) > 255) {
            $errors[] = "Message cannot exceed 255 characters";
        }
        
        $allowed = ['BORROW', 'REMINDER', 'OVERDUE', 'FINE'];
        if(!in_array($type, $allowed)) {
            $errors[] = "Notification type must be BORROW, REMINDER, OVERDUE, or FINE";
        }
        
        return $errors;
    }
}
?>