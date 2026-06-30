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
        $validator->required($data, ['email', 'password', 'role']);

        if ($validator->fails()) {
            return $this->jsonResponse($response, ['errors' => $validator->getErrors()], 422);
        }

        $user = $this->users->findByEmail(trim($data['email']));

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            return $this->jsonResponse($response, ['error' => 'Invalid email or password'], 401);
        }

        if ($user['role'] !== $data['role']) {
            return $this->jsonResponse($response, ['error' => 'Unauthorized role for this account'], 403);
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
        $dob = !empty($data['dob']) ? $data['dob'] : null;

        if ($this->users->emailExists($email)) {
            return $this->jsonResponse($response, ['error' => 'Email already registered'], 409);
        }

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        $userId = $this->users->create(trim($data['name']), $email, $passwordHash, $role, $dob);

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

    /**
     * Updates the logged-in user's own profile. The user id is always taken
     * from the JWT (`sub`), never from the request body, so a user can only
     * ever update their own row — not arbitrary other users by id.
     *
     * Accepted body fields (all optional): name, email, dob, password.
     * Role and id are intentionally never editable through this endpoint.
     */
    public function updateProfile(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute('token');
        $userId = (int) $tokenData['sub'];

        $data = (array) $request->getParsedBody();

        $validator = new Validator();

        if (array_key_exists('email', $data)) {
            $validator->email($data, 'email');
        }
        if (array_key_exists('password', $data) && $data['password'] !== '') {
            $validator->minLength($data, 'password', 6);
        }
        if (array_key_exists('dob', $data) && $data['dob'] !== '' && $data['dob'] !== null) {
            $validator->date($data, 'dob');
        }

        if ($validator->fails()) {
            return $this->jsonResponse($response, ['errors' => $validator->getErrors()], 422);
        }

        $fields = [];

        if (array_key_exists('name', $data) && trim((string) $data['name']) !== '') {
            $fields['name'] = trim($data['name']);
        }

        if (array_key_exists('email', $data) && trim((string) $data['email']) !== '') {
            $email = trim($data['email']);

            if ($this->users->emailExistsForOtherUser($email, $userId)) {
                return $this->jsonResponse($response, ['error' => 'Email already in use'], 409);
            }

            $fields['email'] = $email;
        }

        if (array_key_exists('dob', $data)) {
            $fields['dob'] = $data['dob'] !== '' ? $data['dob'] : null;
        }

        if (array_key_exists('password', $data) && $data['password'] !== '') {
            $fields['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $updatedUser = $this->users->update($userId, $fields);

        if (!$updatedUser) {
            return $this->jsonResponse($response, ['error' => 'User not found'], 404);
        }

        return $this->jsonResponse($response, ['user' => $updatedUser], 200);
    }

    private function jsonResponse(Response $response, array $payload, int $status): Response
    {
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}