<?php

namespace App\Controller;

use App\Repositories\MedicationRepository;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MedicationController
{
    private MedicationRepository $medications;

    public function __construct()
    {
        $this->medications = new MedicationRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        $medications = $this->medications->findAll();
        return $this->jsonResponse($response, ['medications' => $medications], 200);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $medication = $this->medications->findById((int) $args['id']);

        if (!$medication) {
            return $this->jsonResponse($response, ['error' => 'Medication not found'], 404);
        }

        return $this->jsonResponse($response, ['medication' => $medication], 200);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $validator = new Validator();
        $validator->required($data, ['name']);

        if ($validator->fails()) {
            return $this->jsonResponse($response, ['errors' => $validator->getErrors()], 422);
        }

        $id = $this->medications->create(
            trim($data['name']),
            $data['form'] ?? null,
            $data['strength'] ?? null,
            $data['default_unit'] ?? null
        );

        $medication = $this->medications->findById($id);

        return $this->jsonResponse($response, ['medication' => $medication], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $data = (array) $request->getParsedBody();

        if (!$this->medications->findById($id)) {
            return $this->jsonResponse($response, ['error' => 'Medication not found'], 404);
        }

        $validator = new Validator();
        $validator->required($data, ['name']);

        if ($validator->fails()) {
            return $this->jsonResponse($response, ['errors' => $validator->getErrors()], 422);
        }

        $this->medications->update(
            $id,
            trim($data['name']),
            $data['form'] ?? null,
            $data['strength'] ?? null,
            $data['default_unit'] ?? null
        );

        $medication = $this->medications->findById($id);

        return $this->jsonResponse($response, ['medication' => $medication], 200);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        if (!$this->medications->findById($id)) {
            return $this->jsonResponse($response, ['error' => 'Medication not found'], 404);
        }

        $this->medications->delete($id);

        return $this->jsonResponse($response, ['message' => 'Medication deleted'], 200);
    }

    private function jsonResponse(Response $response, array $payload, int $status): Response
    {
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}