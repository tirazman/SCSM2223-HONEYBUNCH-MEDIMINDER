<?php

namespace App\Repositories;

use App\Data\Database;
use PDO;

class PrescriptionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByPatientId(int $patientId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, patient_id, medication_id, drug_name, dose, frequency, start_date, end_date
             FROM Prescription
             WHERE patient_id = :patient_id
             ORDER BY start_date DESC'
        );
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, patient_id, medication_id, drug_name, dose, frequency, start_date, end_date
             FROM Prescription
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $prescription = $stmt->fetch();

        return $prescription ?: null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, patient_id, medication_id, drug_name, dose, frequency, start_date, end_date
             FROM Prescription
             ORDER BY start_date DESC'
        );
        return $stmt->fetchAll();
    }

    public function create(
        int $patientId,
        ?int $medicationId,
        string $drugName,
        string $dose,
        string $frequency,
        ?string $startDate,
        ?string $endDate
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO Prescription (patient_id, medication_id, drug_name, dose, frequency, start_date, end_date)
             VALUES (:patient_id, :medication_id, :drug_name, :dose, :frequency, :start_date, :end_date)'
        );
        $stmt->execute([
            'patient_id' => $patientId,
            'medication_id' => $medicationId,
            'drug_name' => $drugName,
            'dose' => $dose,
            'frequency' => $frequency,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(
        int $id,
        string $drugName,
        string $dose,
        string $frequency,
        ?string $startDate,
        ?string $endDate
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE Prescription
             SET drug_name = :drug_name, dose = :dose, frequency = :frequency,
                 start_date = :start_date, end_date = :end_date
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'drug_name' => $drugName,
            'dose' => $dose,
            'frequency' => $frequency,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM Prescription WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}