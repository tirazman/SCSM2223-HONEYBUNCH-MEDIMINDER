<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Controller\AuthController;
use App\Controller\MedicationController;
use App\Middleware\JWTMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Load .env variables into $_ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// CORS - allows the Vue frontend (different port) to call this API
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Quick Connection Test Route
$app->get('/api/health', function (Request $request, Response $response) {
    $payload = json_encode(['status' => 'success', 'message' => 'Slim 4 Backend is working perfectly!']);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

// ---- Auth routes (public) ----
$app->post('/api/auth/login', [AuthController::class, 'login']);
$app->post('/api/auth/register', [AuthController::class, 'register']);
$app->get('/api/auth/me', [AuthController::class, 'me'])->add(new JWTMiddleware());

// ---- Medication routes ----
$app->get('/api/medications', [MedicationController::class, 'index'])->add(new JWTMiddleware());
$app->get('/api/medications/{id}', [MedicationController::class, 'show'])->add(new JWTMiddleware());
$app->post('/api/medications', [MedicationController::class, 'store'])->add(new JWTMiddleware(['admin']));
$app->put('/api/medications/{id}', [MedicationController::class, 'update'])->add(new JWTMiddleware(['admin']));
$app->delete('/api/medications/{id}', [MedicationController::class, 'destroy'])->add(new JWTMiddleware(['admin']));

$app->run();