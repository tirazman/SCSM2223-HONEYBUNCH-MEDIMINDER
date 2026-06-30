<?php

namespace App\Repositories;

use App\Data\Database;
use PDO;

class MedicationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, form, strength, default_unit, created_at FROM Medications ORDER BY name ASC'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, form, strength, default_unit, created_at FROM Medications WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $medication = $stmt->fetch();

        return $medication ?: null;
    }

    public function create(string $name, ?string $form, ?string $strength, ?string $defaultUnit): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO Medications (name, form, strength, default_unit)
             VALUES (:name, :form, :strength, :default_unit)'
        );
        $stmt->execute([
            'name' => $name,
            'form' => $form,
            'strength' => $strength,
            'default_unit' => $defaultUnit,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, ?string $form, ?string $strength, ?string $defaultUnit): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE Medications
             SET name = :name, form = :form, strength = :strength, default_unit = :default_unit
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'form' => $form,
            'strength' => $strength,
            'default_unit' => $defaultUnit,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM Medications WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}