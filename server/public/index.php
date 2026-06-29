<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Parse JSON request bodies automatically
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Error Middleware (Set true to see errors on screen during local development)
$app->addErrorMiddleware(true, true, true);

// Quick Connection Test Route
$app->get('/api/health', function (Request $request, Response $response) {
    $payload = json_encode([
        "status" => "success", 
        "message" => "Slim 4 Backend is working perfectly!"
    ]);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();