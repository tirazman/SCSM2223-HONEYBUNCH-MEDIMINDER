<?php

namespace App\Controller;

use App\Repositories\PrescriptionRepository;
use App\Repositories\PatientCaregiverRepository;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PrescriptionController
{
    private PrescriptionRepository $prescriptions;
    private PatientCaregiverRepository $links;

    public function __construct()
    {
        $this->prescriptions = new PrescriptionRepository();
        $this->links = new PatientCaregiverRepository();
    }

    /**
     * List prescriptions. Behavior depends on the logged-in role:
     * - patient: only their own
     * - caregiver: only their linked patients' (must pass ?patient_id=)
     * - admin: any patient, or all if no patient_id given
     */
    public function index(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute('token');
        $params = $request->getQueryParams();
        $role = $tokenData['role'];
        $userId = (int) $tokenData['sub'];

        if ($role === 'patient') {
            $prescriptions = $this->prescriptions->findByPatientId($userId);
            return $this->jsonResponse($response, ['prescriptions' => $prescriptions], 200);
        }

        if ($role === 'caregiver') {
            if (!isset($params['patient_id'])) {
                return $this->jsonResponse($response, ['error' => 'patient_id is required for caregivers'], 422);
            }

            $patientId = (int) $params['patient_id'];

            if (!$this->links->isLinked($userId, $patientId)) {
                return $this->jsonResponse($response, ['error' => 'Forbidden: not linked to this patient'], 403);
            }

            $prescriptions = $this->prescriptions->findByPatientId($patientId);
            return $this->jsonResponse($response, ['prescriptions' => $prescriptions], 200);
        }

        // admin
        if (isset($params['patient_id'])) {
            $prescriptions = $this->prescriptions->findByPatientId((int) $params['patient_id']);
        } else {
            $prescriptions = $this->prescriptions->findAll();
        }

        return $this->jsonResponse($response, ['prescriptions' => $prescriptions], 200);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute('token');
        $prescription = $this->prescriptions->findById((int) $args['id']);

        if (!$prescription) {
            return $this->jsonResponse($response, ['error' => 'Prescription not found'], 404);
        }

        if (!$this->canAccess($tokenData, (int) $prescription['patient_id'])) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        return $this->jsonResponse($response, ['prescription' => $prescription], 200);
    }

    public function store(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute('token');
        $data = (array) $request->getParsedBody();

        $validator = new Validator();
        $validator->required($data, ['patient_id', 'drug_name', 'dose', 'frequency'])
            ->date($data, 'start_date')
            ->date($data, 'end_date');

        if ($validator->fails()) {
            return $this->jsonResponse($response, ['errors' => $validator->getErrors()], 422);
        }

        $patientId = (int) $data['patient_id'];

        if (!$this->canAccess($tokenData, $patientId)) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $id = $this->prescriptions->create(
            $patientId,
            isset($data['medication_id']) ? (int) $data['medication_id'] : null,
            trim($data['drug_name']),
            trim($data['dose']),
            trim($data['frequency']),
            $data['start_date'] ?? null,
            $data['end_date'] ?? null
        );

        $prescription = $this->prescriptions->findById($id);

        return $this->jsonResponse($response, ['prescription' => $prescription], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute('token');
        $id = (int) $args['id'];
        $data = (array) $request->getParsedBody();

        $prescription = $this->prescriptions->findById($id);

        if (!$prescription) {
            return $this->jsonResponse($response, ['error' => 'Prescription not found'], 404);
        }

        if (!$this->canAccess($tokenData, (int) $prescription['patient_id'])) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $validator = new Validator();
        $validator->required($data, ['drug_name', 'dose', 'frequency'])
            ->date($data, 'start_date')
            ->date($data, 'end_date');

        if ($validator->fails()) {
            return $this->jsonResponse($response, ['errors' => $validator->getErrors()], 422);
        }

        $this->prescriptions->update(
            $id,
            trim($data['drug_name']),
            trim($data['dose']),
            trim($data['frequency']),
            $data['start_date'] ?? null,
            $data['end_date'] ?? null
        );

        $updated = $this->prescriptions->findById($id);

        return $this->jsonResponse($response, ['prescription' => $updated], 200);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute('token');
        $id = (int) $args['id'];

        $prescription = $this->prescriptions->findById($id);

        if (!$prescription) {
            return $this->jsonResponse($response, ['error' => 'Prescription not found'], 404);
        }

        if (!$this->canAccess($tokenData, (int) $prescription['patient_id'])) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $this->prescriptions->delete($id);

        return $this->jsonResponse($response, ['message' => 'Prescription deleted'], 200);
    }

    /**
     * Central ownership check: can this token's user act on this patient's data?
     * - admin: always
     * - patient: only their own id
     * - caregiver: only if actively linked via PatientCaregiver
     */
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