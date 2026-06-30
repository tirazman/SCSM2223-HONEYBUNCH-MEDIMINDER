<?php

namespace App\Middleware;

use App\Auth\JWTService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;

class JWTMiddleware implements MiddlewareInterface
{
    private JWTService $jwt;

    /** @var string[] Allowed roles; empty array means "any authenticated user" */
    private array $allowedRoles;

    public function __construct(array $allowedRoles = [])
    {
        $this->jwt = new JWTService();
        $this->allowedRoles = $allowedRoles;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return $this->jsonError('Missing or malformed Authorization header', 401);
        }

        $token = $matches[1];
        $payload = $this->jwt->validateToken($token);

        if ($payload === null) {
            return $this->jsonError('Invalid or expired token', 401);
        }

        if (!empty($this->allowedRoles) && !in_array($payload['role'], $this->allowedRoles, true)) {
            return $this->jsonError('Forbidden: insufficient role', 403);
        }

        // Controllers can read this via $request->getAttribute('token')
        $request = $request->withAttribute('token', $payload);

        return $handler->handle($request);
    }

    private function jsonError(string $message, int $status): Response
    {
        $response = (new ResponseFactory())->createResponse($status);
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}