<?php

namespace App\Controller;

use App\Repositories\PatientCaregiverRepository;
use App\Repositories\UserRepository;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PatientCaregiverController
{
    private PatientCaregiverRepository $links;
    private UserRepository $users;

    public function __construct()
    {
        $this->links = new PatientCaregiverRepository();
        $this->users = new UserRepository();
    }

    /**
     * List patients linked to the logged-in caregiver (Caregiver Hub dashboard).
     */
    public function myPatients(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute('token');
        $patients = $this->links->findPatientsByCaregiverId((int) $tokenData['sub']);

        return $this->jsonResponse($response, ['patients' => $patients], 200);
    }

    /**
     * List caregivers linked to the logged-in patient.
     */
    public function myCaregivers(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute('token');
        $caregivers = $this->links->findCaregiversByPatientId((int) $tokenData['sub']);

        return $this->jsonResponse($response, ['caregivers' => $caregivers], 200);
    }

    /**
     * Create a new patient-caregiver link.
     * Accepts EITHER caregiver_id (numeric) OR caregiver_email (looked up here).
     * A patient can only link themselves; an admin may specify any patient_id.
     */
    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $tokenData = $request->getAttribute('token');

        if (empty($data['caregiver_id']) && empty($data['caregiver_email'])) {
            return $this->jsonResponse($response, ['errors' => ['caregiver' => 'caregiver_id or caregiver_email is required']], 422);
        }

        // Resolve caregiver_email -> caregiver_id if an id wasn't given directly
        if (!empty($data['caregiver_email'])) {
            $caregiverUser = $this->users->findByEmail(trim($data['caregiver_email']));

            if (!$caregiverUser) {
                return $this->jsonResponse($response, ['error' => 'No account found with that caregiver email'], 404);
            }

            if ($caregiverUser['role'] !== 'caregiver') {
                return $this->jsonResponse($response, ['error' => 'That email does not belong to a caregiver account'], 422);
            }

            $caregiverId = (int) $caregiverUser['id'];
        } else {
            $caregiverId = (int) $data['caregiver_id'];
        }

        // If the requester is a patient, they can only link themselves.
        // Admins may specify any patient_id.
        if ($tokenData['role'] === 'patient') {
            $patientId = (int) $tokenData['sub'];
        } elseif (isset($data['patient_id'])) {
            $patientId = (int) $data['patient_id'];
        } else {
            return $this->jsonResponse($response, ['error' => 'patient_id is required'], 422);
        }

        $id = $this->links->create($patientId, $caregiverId);
        $link = $this->links->findById($id);

        return $this->jsonResponse($response, ['link' => $link], 201);
    }

    /**
     * Deactivate a link. Only the patient involved or an admin may do this.
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $tokenData = $request->getAttribute('token');

        $link = $this->links->findById($id);

        if (!$link) {
            return $this->jsonResponse($response, ['error' => 'Link not found'], 404);
        }

        if ($tokenData['role'] === 'patient' && (int) $link['patient_id'] !== (int) $tokenData['sub']) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $this->links->deactivate($id);

        return $this->jsonResponse($response, ['message' => 'Link deactivated'], 200);
    }

    private function jsonResponse(Response $response, array $payload, int $status): Response
    {
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}