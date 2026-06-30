<?php

namespace App\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JWTService
{
    private string $secret;
    private string $algo = 'HS256';
    private int $expirySeconds;

    public function __construct(?string $secret = null, int $expirySeconds = 3600 * 24)
    {
        $this->secret = $secret ?? ($_ENV['JWT_SECRET'] ?? '');
        $this->expirySeconds = $expirySeconds;
    }

    /**
     * Create a signed JWT. Payload kept minimal: user id + role.
     */
    public function generateToken(int $userId, string $role): string
    {
        $now = time();

        $payload = [
            'sub'  => $userId,
            'role' => $role,
            'iat'  => $now,
            'exp'  => $now + $this->expirySeconds,
        ];

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Decode and verify a token. Returns payload array, or null if invalid/expired.
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            return null;
        } catch (SignatureInvalidException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}