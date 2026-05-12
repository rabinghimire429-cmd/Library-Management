<?php
// notification_functions.php - Complete business logic layer

class NotificationModule {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    // CREATE - Add notification
    public function addNotification($member_id, $message, $type, $sent_date = null) {
        // Validate inputs
        $errors = $this->validateNotification($member_id, $message, $type);
        if(!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $sent_date = $sent_date ?? date('Y-m-d H:i:s');
        
        $stmt = $this->conn->prepare("
            INSERT INTO notification (member_id, message, notification_type, sent_date, status)
            VALUES (?, ?, ?, ?, TRUE)
        ");
        $stmt->bind_param("iiss", $member_id, $message, $type, $sent_date);
        
        if($stmt->execute()) {
            return ['success' => true, 'notification_id' => $stmt->insert_id];
        }
        return ['success' => false, 'errors' => ['Database error: ' . $stmt->error]];
    }
    
    // READ - Get single notification
    public function getNotification($notification_id) {
        $stmt = $this->conn->prepare("
            SELECT n.*, m.name as member_name 
            FROM notification n
            JOIN member m ON n.member_id = m.member_id
            WHERE n.notification_id = ?
        ");
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    // READ - List all notifications for a member (with filter)
    public function listNotifications($member_id, $type = null, $status = null, $limit = 50, $offset = 0) {
        $query = "SELECT * FROM notification WHERE member_id = ?";
        $params = [$member_id];
        $types = "i";
        
        if($type && $type != 'all') {
            $query .= " AND notification_type = ?";
            $params[] = strtoupper($type);
            $types .= "s";
        }
        
        if($status !== null) {
            $query .= " AND status = ?";
            $params[] = $status;
            $types .= "i";
        }
        
        $query .= " ORDER BY sent_date DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // UPDATE - Mark notification as read
    public function markAsRead($notification_id) {
        $stmt = $this->conn->prepare("
            UPDATE notification SET status = FALSE WHERE notification_id = ?
        ");
        $stmt->bind_param("i", $notification_id);
        return $stmt->execute();
    }
    
    // UPDATE - Edit notification (admin only)
    public function editNotification($notification_id, $message, $type) {
        $allowed_types = ['BORROW', 'REMINDER', 'OVERDUE', 'FINE'];
        if(!in_array($type, $allowed_types)) {
            return ['success' => false, 'errors' => ['Invalid notification type']];
        }
        
        if(empty($message) || strlen($message) > 255) {
            return ['success' => false, 'errors' => ['Message must be 1-255 characters']];
        }
        
        $stmt = $this->conn->prepare("
            UPDATE notification SET message = ?, notification_type = ? WHERE notification_id = ?
        ");
        $stmt->bind_param("ssi", $message, $type, $notification_id);
        
        if($stmt->execute()) {
            return ['success' => true];
        }
        return ['success' => false, 'errors' => ['Update failed']];
    }
    
    // DELETE - Remove notification (admin only)
    public function deleteNotification($notification_id) {
        $stmt = $this->conn->prepare("DELETE FROM notification WHERE notification_id = ?");
        $stmt->bind_param("i", $notification_id);
        return $stmt->execute();
    }
    
    // AUTO-GENERATE notifications based on borrowing events
    public function generateBorrowNotification($member_id, $book_title, $due_date) {
        $message = "You have borrowed '{$book_title}'. Due date: {$due_date}. Please return on time.";
        return $this->addNotification($member_id, $message, 'BORROW');
    }
    
    public function generateReminderNotification($member_id, $book_title, $due_date) {
        $days_left = (strtotime($due_date) - time()) / 86400;
        $message = "REMINDER: '{$book_title}' is due in {$days_left} days on {$due_date}. Please return soon.";
        return $this->addNotification($member_id, $message, 'REMINDER');
    }
    
    public function generateOverdueNotification($member_id, $book_title, $days_overdue, $fine_amount) {
        $message = "OVERDUE: '{$book_title}' is {$days_overdue} days late. Fine: \${$fine_amount}. Please return immediately.";
        return $this->addNotification($member_id, $message, 'OVERDUE');
    }
    
    public function generateFineNotification($member_id, $fine_amount, $book_title) {
        $message = "FINE ADDED: \${$fine_amount} for late return of '{$book_title}'. Please pay at library counter.";
        return $this->addNotification($member_id, $message, 'FINE');
    }
    
    // VALIDATION - According to spec
    private function validateNotification($member_id, $message, $type) {
        $errors = [];
        
        // Check member exists
        $stmt = $this->conn->prepare("SELECT member_id FROM member WHERE member_id = ?");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        if($stmt->get_result()->num_rows == 0) {
            $errors[] = "Member does not exist";
        }
        
        // Validate message
        if(empty($message) || strlen($message) > 255) {
            $errors[] = "Message must be between 1 and 255 characters";
        }
        
        // Validate type
        $allowed = ['BORROW', 'REMINDER', 'OVERDUE', 'FINE'];
        if(!in_array($type, $allowed)) {
            $errors[] = "Notification type must be one of: " . implode(', ', $allowed);
        }
        
        return $errors;
    }
    
    // Get unread count for a member
    public function getUnreadCount($member_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count FROM notification 
            WHERE member_id = ? AND status = TRUE
        ");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
}
?>