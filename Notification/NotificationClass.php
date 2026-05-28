<?php
/**
 * NOTIFICATION MODULE - COMPLETE BUSINESS LOGIC
 * 
 * FUNCTIONS IMPLEMENTED:
 * 1. ADD - Create notification template
 * 2. EDIT - Update notification template
 * 3. DELETE - Remove notification template
 * 4. LIST - List all templates
 * 5. SEARCH - Search templates by keyword
 * 6. FILTER - Filter templates by type
 * 7. VALIDATE - Input validation for templates and sending
 * 8. SEND - Send notification using template
 */

class NotificationManager {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->createTemplatesTableIfNotExists();
    }
    
    /**
     * CREATE TEMPLATES TABLE if not exists
     */
    private function createTemplatesTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS notification_templates (
            template_id INT(11) AUTO_INCREMENT PRIMARY KEY,
            template_name VARCHAR(100) NOT NULL,
            template_type ENUM('BORROW', 'REMINDER', 'OVERDUE', 'FINE') NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            placeholders TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->conn->query($sql);
    }
    
    // ============ TEMPLATE FUNCTIONS ============
    
    /**
     * ADD TEMPLATE - Create a new notification template
     */
    public function addTemplate($template_name, $template_type, $subject, $message, $placeholders = null) {
        $errors = $this->validateTemplate($template_name, $template_type, $subject, $message);
        if(!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $stmt = $this->conn->prepare("INSERT INTO notification_templates (template_name, template_type, subject, message, placeholders) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $template_name, $template_type, $subject, $message, $placeholders);
        
        if($stmt->execute()) {
            return ['success' => true, 'template_id' => $stmt->insert_id];
        }
        return ['success' => false, 'errors' => ['Failed to add template: ' . $stmt->error]];
    }
    
    /**
     * EDIT TEMPLATE - Update existing template
     */
    public function editTemplate($template_id, $template_name, $template_type, $subject, $message, $placeholders = null) {
        $errors = $this->validateTemplate($template_name, $template_type, $subject, $message);
        if(!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $stmt = $this->conn->prepare("UPDATE notification_templates SET template_name = ?, template_type = ?, subject = ?, message = ?, placeholders = ? WHERE template_id = ?");
        $stmt->bind_param("sssssi", $template_name, $template_type, $subject, $message, $placeholders, $template_id);
        
        if($stmt->execute()) {
            return ['success' => true];
        }
        return ['success' => false, 'errors' => ['Failed to update template: ' . $stmt->error]];
    }
    
    /**
     * DELETE TEMPLATE - Remove template
     */
    public function deleteTemplate($template_id) {
        $stmt = $this->conn->prepare("DELETE FROM notification_templates WHERE template_id = ?");
        $stmt->bind_param("i", $template_id);
        return $stmt->execute();
    }
    
    /**
     * LIST TEMPLATES - Get all templates with filters
     */
    public function getTemplates($type = 'all', $search = '') {
        $query = "SELECT * FROM notification_templates WHERE 1=1";
        $params = [];
        $types = "";
        
        if($type != 'all') {
            $query .= " AND template_type = ?";
            $params[] = strtoupper($type);
            $types .= "s";
        }
        
        if(!empty($search)) {
            $query .= " AND (template_name LIKE ? OR subject LIKE ? OR message LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= "sss";
        }
        
        $query .= " ORDER BY template_type, template_name";
        
        $stmt = $this->conn->prepare($query);
        if(!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * FIND TEMPLATE - Get single template by ID
     */
    public function findTemplate($template_id) {
        $stmt = $this->conn->prepare("SELECT * FROM notification_templates WHERE template_id = ?");
        $stmt->bind_param("i", $template_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * VALIDATE TEMPLATE
     */
    private function validateTemplate($template_name, $template_type, $subject, $message) {
        $errors = [];
        
        if(empty($template_name) || strlen($template_name) > 100) {
            $errors[] = "Template name must be 1-100 characters";
        }
        
        $allowed_types = ['BORROW', 'REMINDER', 'OVERDUE', 'FINE'];
        if(!in_array($template_type, $allowed_types)) {
            $errors[] = "Invalid template type";
        }
        
        if(empty($subject) || strlen($subject) > 200) {
            $errors[] = "Subject must be 1-200 characters";
        }
        
        if(empty($message)) {
            $errors[] = "Message cannot be empty";
        }
        
        return $errors;
    }
    
    // ============ SEND NOTIFICATION FUNCTIONS ============
    
    /**
     * SEND NOTIFICATION - Send to member using template
     */
    public function sendNotification($member_id, $template_id, $custom_message = null, $custom_subject = null) {
        // VALIDATE - Member exists
        $check = $this->conn->prepare("SELECT member_id, full_name FROM member WHERE member_id = ?");
        $check->bind_param("i", $member_id);
        $check->execute();
        $member = $check->get_result()->fetch_assoc();
        
        if(!$member) {
            return ['success' => false, 'errors' => ['Member does not exist']];
        }
        
        // Get template
        $template = $this->findTemplate($template_id);
        if(!$template) {
            return ['success' => false, 'errors' => ['Template not found']];
        }
        
        // Prepare message with member name replacement
        $subject = $custom_subject ?: $template['subject'];
        $message = $custom_message ?: $template['message'];
        $message = str_replace('{member_name}', $member['full_name'] ?: 'Member', $message);
        
        $sent_date = date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare("INSERT INTO notification (member_id, message, notification_type, sent_date, read_status) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("isss", $member_id, $message, $template['template_type'], $sent_date);
        
        if($stmt->execute()) {
            return ['success' => true, 'notification_id' => $stmt->insert_id];
        }
        return ['success' => false, 'errors' => ['Failed to send: ' . $stmt->error]];
    }
    
    /**
     * GET MEMBER NOTIFICATIONS
     */
    public function getMemberNotifications($member_id, $type = 'all', $status = 'all') {
        $query = "SELECT * FROM notification WHERE member_id = ?";
        $params = [$member_id];
        $types = "i";
        
        if($type != 'all') {
            $query .= " AND notification_type = ?";
            $params[] = strtoupper($type);
            $types .= "s";
        }
        
        if($status != 'all') {
            $status_value = ($status == 'unread') ? 1 : 0;
            $query .= " AND read_status = ?";
            $params[] = $status_value;
            $types .= "i";
        }
        
        $query .= " ORDER BY sent_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * GET ALL NOTIFICATIONS (Librarian view)
     */
    public function getAllNotifications($type = 'all', $status = 'all', $member_filter = null, $search = '') {
        $query = "SELECT n.*, m.full_name as member_name, m.email as member_email
                  FROM notification n
                  JOIN member m ON n.member_id = m.member_id
                  WHERE 1=1";
        $params = [];
        $types = "";
        
        if($type != 'all') {
            $query .= " AND n.notification_type = ?";
            $params[] = strtoupper($type);
            $types .= "s";
        }
        
        if($status != 'all') {
            $status_value = ($status == 'unread') ? 1 : 0;
            $query .= " AND n.read_status = ?";
            $params[] = $status_value;
            $types .= "i";
        }
        
        if($member_filter && $member_filter != 'all') {
            $query .= " AND n.member_id = ?";
            $params[] = $member_filter;
            $types .= "i";
        }
        
        if(!empty($search)) {
            $query .= " AND (n.message LIKE ? OR m.full_name LIKE ? OR n.notification_type LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= "sss";
        }
        
        $query .= " ORDER BY n.sent_date DESC";
        
        $stmt = $this->conn->prepare($query);
        if(!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * DELETE NOTIFICATION
     */
    public function deleteNotification($notification_id) {
        $stmt = $this->conn->prepare("DELETE FROM notification WHERE notification_id = ?");
        $stmt->bind_param("i", $notification_id);
        return $stmt->execute();
    }
    
    /**
     * EDIT NOTIFICATION (sent notification)
     */
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
    
    /**
     * MARK NOTIFICATION AS READ
     */
    public function markAsRead($notification_id) {
        $stmt = $this->conn->prepare("UPDATE notification SET read_status = 0 WHERE notification_id = ?");
        $stmt->bind_param("i", $notification_id);
        return $stmt->execute();
    }
    
    /**
     * GET UNREAD COUNT
     */
    public function getUnreadCount($member_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM notification WHERE member_id = ? AND read_status = 1");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    }
    
    /**
     * GET ALL MEMBERS
     */
    public function getAllMembers() {
        $result = $this->conn->query("SELECT member_id, full_name, email FROM member ORDER BY full_name");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * GET STATISTICS
     */
    public function getStatistics() {
        $stats = [];
        
        $result = $this->conn->query("SELECT COUNT(*) as count FROM notification");
        $stats['total_notifications'] = $result->fetch_assoc()['count'];
        
        $result = $this->conn->query("SELECT COUNT(*) as count FROM notification WHERE read_status = 1");
        $stats['unread'] = $result->fetch_assoc()['count'];
        
        $result = $this->conn->query("SELECT COUNT(*) as count FROM notification_templates");
        $stats['total_templates'] = $result->fetch_assoc()['count'];
        
        return $stats;
    }
}
?>