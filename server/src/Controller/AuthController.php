<?php

namespace App\Controller;

use App\Auth\JWTService;
use App\Repositories\UserRepository;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    private UserRepository $users;
    private JWTService $jwt;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->jwt = new JWTService();
    }

    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $validator = new Validator();
        $validator->required($data, ['email', 'password']);

        if ($validator->fails()) {
            return $this->jsonResponse($response, ['errors' => $validator->getErrors()], 422);
        }

        $user = $this->users->findByEmail(trim($data['email']));

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            return $this->jsonResponse($response, ['error' => 'Invalid email or password'], 401);
        }

        $token = $this->jwt->generateToken((int) $user['id'], $user['role']);

        return $this->jsonResponse($response, [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ], 200);
    }

    public function register(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $validator = new Validator();
        $validator->required($data, ['name', 'email', 'password'])
            ->email($data, 'email')
            ->minLength($data, 'password', 6);

        if (isset($data['role'])) {
            $validator->in($data, 'role', ['patient', 'caregiver', 'admin']);
        }

        if ($validator->fails()) {
            return $this->jsonResponse($response, ['errors' => $validator->getErrors()], 422);
        }

        $email = trim($data['email']);
        $role = $data['role'] ?? 'patient';

        if ($this->users->emailExists($email)) {
            return $this->jsonResponse($response, ['error' => 'Email already registered'], 409);
        }

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        $userId = $this->users->create(trim($data['name']), $email, $passwordHash, $role, $data['dob'] ?? null);

        $token = $this->jwt->generateToken($userId, $role);

        return $this->jsonResponse($response, [
            'token' => $token,
            'user' => [
                'id' => $userId,
                'name' => $data['name'],
                'email' => $email,
                'role' => $role,
            ],
        ], 201);
    }

    public function me(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute('token');
        $user = $this->users->findById((int) $tokenData['sub']);

        if (!$user) {
            return $this->jsonResponse($response, ['error' => 'User not found'], 404);
        }

        return $this->jsonResponse($response, ['user' => $user], 200);
    }

    private function jsonResponse(Response $response, array $payload, int $status): Response
    {
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}