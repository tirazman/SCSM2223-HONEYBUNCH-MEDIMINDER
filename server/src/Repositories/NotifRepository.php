<?php

namespace App\Repositories;

use App\Data\Database;
use PDO;

class NotificationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // =========================================================================
    // DOSE-LOG REMINDER FEED  (powers the Capacitor + in-app notifications)
    // =========================================================================

    /**
     * Returns 'scheduled' DoseLog entries for the given patient that fall
     * within the next 60 minutes. These are the items shown in the bell panel
     * and scheduled as native Capacitor popups.
     *
     * Column alias `scheduled_time` keeps the API response field name stable
     * regardless of any future internal column renames.
     */
    public function getDueNotifications(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                dl.id,
                dl.prescription_id,
                dl.scheduled_at            AS scheduled_time,
                dl.status,
                p.drug_name,
                p.dose,
                p.frequency
             FROM DoseLog dl
             JOIN Prescription p ON p.id = dl.prescription_id
             WHERE p.patient_id  = :user_id
               AND dl.status     = 'scheduled'
               AND dl.scheduled_at BETWEEN NOW()
                                       AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
             ORDER BY dl.scheduled_at ASC"
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // NOTIFICATION TABLE  (persistent alert / info history)
    // =========================================================================

    /**
     * Full notification history for a user, newest first.
     * Used by getHistory() in the controller.
     */
    public function getNotificationHistory(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, type, body, sent_at, read_at
             FROM Notification
             WHERE user_id = :user_id
             ORDER BY sent_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserts a new row into the Notification table. Called by backend
     * services (e.g. a scheduled job) to record that a reminder was sent.
     *
     * @param  int    $userId
     * @param  string $type   One of: 'reminder' | 'alert' | 'info'
     * @param  string $body   Human-readable message text
     * @return int            The new notification's ID
     */
    public function create(int $userId, string $type, string $body): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO Notification (user_id, type, body)
             VALUES (:user_id, :type, :body)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'type'    => $type,
            'body'    => $body,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Marks a notification as read. Checks user_id ownership so that one
     * user cannot mark another's notifications read.
     *
     * @return bool  true if a row was actually updated, false if not found
     *               or the notification belongs to a different user.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE Notification
             SET read_at = NOW()
             WHERE id      = :id
               AND user_id = :user_id
               AND read_at IS NULL'
        );
        $stmt->execute([
            'id'      => $notificationId,
            'user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Marks ALL unread notifications for a user as read.
     * Useful if you want a server-side "mark all read" endpoint later.
     */
    public function markAllRead(int $userId): int
    {
        $stmt = $this->db->prepare(
            'UPDATE Notification
             SET read_at = NOW()
             WHERE user_id = :user_id
               AND read_at IS NULL'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->rowCount();         // number of rows updated
    }

    /**
     * Hard-deletes a notification. Ownership is verified via user_id.
     */
    public function delete(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM Notification
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'id'      => $notificationId,
            'user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }
}