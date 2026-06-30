<?php

namespace App\Repositories;

use App\Data\Database;
use PDO;

class DoseLogRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * All dose log entries for a given prescription.
     */
    public function findByPrescriptionId(int $prescriptionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, prescription_id, scheduled_at, taken_at, status
             FROM DoseLog
             WHERE prescription_id = :prescription_id
             ORDER BY scheduled_at DESC'
        );
        $stmt->execute(['prescription_id' => $prescriptionId]);
        return $stmt->fetchAll();
    }

    /**
     * All dose log entries for a patient (joins through Prescription),
     * optionally filtered to a date range — used for "today's schedule" or
     * adherence charts.
     */
    public function findByPatientId(int $patientId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $sql = 'SELECT dl.id, dl.prescription_id, dl.scheduled_at, dl.taken_at, dl.status,
                       p.drug_name, p.dose, p.frequency
                FROM DoseLog dl
                JOIN Prescription p ON p.id = dl.prescription_id
                WHERE p.patient_id = :patient_id';

        $params = ['patient_id' => $patientId];

        if ($fromDate !== null) {
            $sql .= ' AND dl.scheduled_at >= :from_date';
            $params['from_date'] = $fromDate;
        }

        if ($toDate !== null) {
            $sql .= ' AND dl.scheduled_at <= :to_date';
            $params['to_date'] = $toDate;
        }

        $sql .= ' ORDER BY dl.scheduled_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT dl.id, dl.prescription_id, dl.scheduled_at, dl.taken_at, dl.status, p.patient_id
             FROM DoseLog dl
             JOIN Prescription p ON p.id = dl.prescription_id
             WHERE dl.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $log = $stmt->fetch();

        return $log ?: null;
    }

    /**
     * Create a new scheduled dose entry (e.g. generated when a prescription starts).
     */
    public function create(int $prescriptionId, string $scheduledAt): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO DoseLog (prescription_id, scheduled_at, status)
             VALUES (:prescription_id, :scheduled_at, "scheduled")'
        );
        $stmt->execute([
            'prescription_id' => $prescriptionId,
            'scheduled_at' => $scheduledAt,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Mark a dose as taken or skipped. This is the "Mark Taken" / "Skipped" button action.
     */
    public function updateStatus(int $id, string $status, ?string $takenAt = null): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE DoseLog SET status = :status, taken_at = :taken_at WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'taken_at' => $takenAt,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM DoseLog WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Adherence percentage for a patient over a date range:
     * (taken doses / total non-scheduled-pending doses) * 100.
     * Used for the 7/30-day adherence chart.
     */
    public function getAdherenceStats(int $patientId, string $fromDate, string $toDate): array
    {
        $stmt = $this->db->prepare(
            "SELECT dl.status, COUNT(*) as count
             FROM DoseLog dl
             JOIN Prescription p ON p.id = dl.prescription_id
             WHERE p.patient_id = :patient_id
               AND dl.scheduled_at BETWEEN :from_date AND :to_date
             GROUP BY dl.status"
        );
        $stmt->execute([
            'patient_id' => $patientId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        $counts = ['scheduled' => 0, 'taken' => 0, 'skipped' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        $total = $counts['taken'] + $counts['skipped'];
        $adherencePercentage = $total > 0 ? round(($counts['taken'] / $total) * 100, 1) : 0;

        return [
            'taken' => $counts['taken'],
            'skipped' => $counts['skipped'],
            'scheduled' => $counts['scheduled'],
            'adherence_percentage' => $adherencePercentage,
        ];
    }
}