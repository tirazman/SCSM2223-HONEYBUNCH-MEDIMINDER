<?php

namespace App\Controller;

use App\Repositories\DoseLogRepository;
use App\Repositories\PatientCaregiverRepository;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DoseLogController
{
    private DoseLogRepository $doseLogs;
    private PatientCaregiverRepository $links;

    public function __construct()
    {
        $this->doseLogs = new DoseLogRepository();
        $this->links = new PatientCaregiverRepository();
    }

    /**
     * List dose logs for a patient (daily schedule view).
     * Patients see their own; caregivers must specify ?patient_id= and be linked; admin can specify any.
     * Optional ?from_date= and ?to_date= (YYYY-MM-DD) for adherence-range views.
     */
    public function index(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute('token');
        $params = $request->getQueryParams();
        $role = $tokenData['role'];
        $userId = (int) $tokenData['sub'];

        $fromDate = $params['from_date'] ?? null;
        $toDate = $params['to_date'] ?? null;

        if ($role === 'patient') {
            $logs = $this->doseLogs->findByPatientId($userId, $fromDate, $toDate);
            return $this->jsonResponse($response, ['dose_logs' => $logs], 200);
        }

        if (!isset($params['patient_id'])) {
            return $this->jsonResponse($response, ['error' => 'patient_id is required'], 422);
        }

        $patientId = (int) $params['patient_id'];

        if (!$this->canAccess($tokenData, $patientId)) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $logs = $this->doseLogs->findByPatientId($patientId, $fromDate, $toDate);
        return $this->jsonResponse($response, ['dose_logs' => $logs], 200);
    }

    /**
     * Adherence percentage + breakdown for a date range (7/30-day chart).
     */
    public function adherence(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute('token');
        $params = $request->getQueryParams();
        $role = $tokenData['role'];
        $userId = (int) $tokenData['sub'];

        $patientId = $role === 'patient' ? $userId : (int) ($params['patient_id'] ?? 0);

        if ($patientId === 0) {
            return $this->jsonResponse($response, ['error' => 'patient_id is required'], 422);
        }

        if (!$this->canAccess($tokenData, $patientId)) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        // Default to last 7 days if no range given
        $toDate = $params['to_date'] ?? date('Y-m-d');
        $fromDate = $params['from_date'] ?? date('Y-m-d', strtotime('-7 days'));

        $stats = $this->doseLogs->getAdherenceStats($patientId, $fromDate, $toDate);

        return $this->jsonResponse($response, ['adherence' => $stats], 200);
    }

    public function store(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute('token');
        $data = (array) $request->getParsedBody();

        $validator = new Validator();
        $validator->required($data, ['prescription_id', 'scheduled_at']);

        if ($validator->fails()) {
            return $this->jsonResponse($response, ['errors' => $validator->getErrors()], 422);
        }

        // Only caregiver/admin can schedule new doses
        if (!in_array($tokenData['role'], ['caregiver', 'admin'], true)) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $id = $this->doseLogs->create((int) $data['prescription_id'], $data['scheduled_at']);
        $log = $this->doseLogs->findById($id);

        return $this->jsonResponse($response, ['dose_log' => $log], 201);
    }

    /**
     * "Mark Taken" / "Skipped" button action.
     */
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute('token');
        $id = (int) $args['id'];
        $data = (array) $request->getParsedBody();

        $log = $this->doseLogs->findById($id);

        if (!$log) {
            return $this->jsonResponse($response, ['error' => 'Dose log not found'], 404);
        }

        if (!$this->canAccess($tokenData, (int) $log['patient_id'])) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $validator = new Validator();
        $validator->required($data, ['status'])
            ->in($data, 'status', ['taken', 'skipped', 'scheduled']);

        if ($validator->fails()) {
            return $this->jsonResponse($response, ['errors' => $validator->getErrors()], 422);
        }

        $takenAt = $data['status'] === 'taken' ? date('Y-m-d H:i:s') : null;
        $this->doseLogs->updateStatus($id, $data['status'], $takenAt);

        $updated = $this->doseLogs->findById($id);

        return $this->jsonResponse($response, ['dose_log' => $updated], 200);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute('token');
        $id = (int) $args['id'];

        $log = $this->doseLogs->findById($id);

        if (!$log) {
            return $this->jsonResponse($response, ['error' => 'Dose log not found'], 404);
        }

        if (!in_array($tokenData['role'], ['caregiver', 'admin'], true)) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        if (!$this->canAccess($tokenData, (int) $log['patient_id'])) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $this->doseLogs->delete($id);

        return $this->jsonResponse($response, ['message' => 'Dose log deleted'], 200);
    }

    private function canAccess(array $tokenData, int $patientId): bool
    {
        $role = $tokenData['role'];
        $userId = (int) $tokenData['sub'];

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'patient') {
            return $userId === $patientId;
        }

        if ($role === 'caregiver') {
            return $this->links->isLinked($userId, $patientId);
        }

        return false;
    }

    private function jsonResponse(Response $response, array $payload, int $status): Response
    {
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}