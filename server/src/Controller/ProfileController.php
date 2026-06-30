<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ProfileController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // GET /api/profile
    public function show(Request $request, Response $response): Response
    {
        $authUser = $request->getAttribute('user');
        $userId = $authUser['id'];

        $stmt = $this->db->prepare(
            'SELECT id, name, email, role, dob, created_at
             FROM users
             WHERE id = :id'
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Never leak the password hash
        unset($user['password'], $user['password_hash']);

        $response->getBody()->write(json_encode(['user' => $user]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // PUT /api/profile
    public function update(Request $request, Response $response): Response
    {
        $authUser = $request->getAttribute('user');
        $userId = $authUser['id'];
        $role = $authUser['role'];

        $data = $request->getParsedBody() ?? json_decode((string) $request->getBody(), true);

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $dob = $data['dob'] ?? null;
        $password = $data['password'] ?? null;

        // --- Validation ---
        $errors = [];
        if ($name === '') {
            $errors[] = 'Name is required';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required';
        }
        if ($password !== null && strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if ($role === 'patient' && $dob !== null && $dob !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            $errors[] = 'Date of birth must be in YYYY-MM-DD format';
        }

        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['error' => implode('; ', $errors)]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        // --- Email uniqueness check (excluding the current user) ---
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $stmt->execute(['email' => $email, 'id' => $userId]);
        if ($stmt->fetch()) {
            $response->getBody()->write(json_encode(['error' => 'Email is already in use']));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }

        // --- Build dynamic UPDATE ---
        $fields = ['name = :name', 'email = :email'];
        $params = ['name' => $name, 'email' => $email, 'id' => $userId];

        if ($role === 'patient' && $dob !== null && $dob !== '') {
            $fields[] = 'dob = :dob';
            $params['dob'] = $dob;
        }

        if ($password) {
            $fields[] = 'password = :password';
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // --- Fetch and return the updated record ---
        $stmt = $this->db->prepare(
            'SELECT id, name, email, role, dob, created_at
             FROM users
             WHERE id = :id'
        );
        $stmt->execute(['id' => $userId]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        
        unset($updated['password'], $updated['password_hash']);

        $response->getBody()->write(json_encode(['user' => $updated]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}