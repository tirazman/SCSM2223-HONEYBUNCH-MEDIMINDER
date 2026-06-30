<?php

namespace App\Repositories;

use App\Data\Database;
use PDO;

class PatientCaregiverRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Check whether a caregiver is actively linked to a given patient.
     * Used by other controllers to enforce "caregivers only see their own patients".
     */
    public function isLinked(int $caregiverId, int $patientId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM PatientCaregiver
             WHERE caregiver_id = :caregiver_id AND patient_id = :patient_id AND status = "active"
             LIMIT 1'
        );
        $stmt->execute(['caregiver_id' => $caregiverId, 'patient_id' => $patientId]);

        return (bool) $stmt->fetch();
    }

    /**
     * List all patients linked to a caregiver (for the caregiver dashboard).
     */
    public function findPatientsByCaregiverId(int $caregiverId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.email, u.dob, pc.status
             FROM PatientCaregiver pc
             JOIN User u ON u.id = pc.patient_id
             WHERE pc.caregiver_id = :caregiver_id AND pc.status = "active"
             ORDER BY u.name ASC'
        );
        $stmt->execute(['caregiver_id' => $caregiverId]);

        return $stmt->fetchAll();
    }

    /**
     * List all caregivers linked to a patient.
     */
    public function findCaregiversByPatientId(int $patientId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.email, pc.status
             FROM PatientCaregiver pc
             JOIN User u ON u.id = pc.caregiver_id
             WHERE pc.patient_id = :patient_id AND pc.status = "active"
             ORDER BY u.name ASC'
        );
        $stmt->execute(['patient_id' => $patientId]);

        return $stmt->fetchAll();
    }

    /**
     * Create a new link between a patient and a caregiver.
     */
    public function create(int $patientId, int $caregiverId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO PatientCaregiver (patient_id, caregiver_id, status)
             VALUES (:patient_id, :caregiver_id, "active")'
        );
        $stmt->execute(['patient_id' => $patientId, 'caregiver_id' => $caregiverId]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Deactivate a link (soft delete) instead of removing the row outright.
     */
    public function deactivate(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE PatientCaregiver SET status = "inactive" WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM PatientCaregiver WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $link = $stmt->fetch();

        return $link ?: null;
    }
}