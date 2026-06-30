<?php
namespace App\Repositories;

class NotificationRepository {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getDueNotifications($userId) {
        // Fetch medications due for the current user
        $stmt = $this->db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = :user_id 
            AND scheduled_time >= NOW() 
            AND scheduled_time <= DATE_ADD(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}