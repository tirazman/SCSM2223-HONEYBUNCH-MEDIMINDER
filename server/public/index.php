<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Controller\AuthController;
use App\Controller\MedicationController;
use App\Controller\PrescriptionController;
use App\Controller\DoseLogController;
use App\Controller\PatientCaregiverController;
use App\Middleware\JWTMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->get('/api/health', function (Request $request, Response $response) {
    $payload = json_encode(['status' => 'success', 'message' => 'Slim 4 Backend is working perfectly!']);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

// ---- Auth routes (public) ----
$app->post('/api/auth/login', [AuthController::class, 'login']);
$app->post('/api/auth/register', [AuthController::class, 'register']);
$app->get('/api/auth/me', [AuthController::class, 'me'])->add(new JWTMiddleware());

// ---- Admin user lookups ----
$app->get('/api/admin/patients', [AuthController::class, 'listPatients'])->add(new JWTMiddleware(['admin']));

// ---- Medication routes ----
$app->get('/api/medications', [MedicationController::class, 'index'])->add(new JWTMiddleware());
$app->get('/api/medications/{id}', [MedicationController::class, 'show'])->add(new JWTMiddleware());
$app->post('/api/medications', [MedicationController::class, 'store'])->add(new JWTMiddleware(['admin']));
$app->put('/api/medications/{id}', [MedicationController::class, 'update'])->add(new JWTMiddleware(['admin']));
$app->delete('/api/medications/{id}', [MedicationController::class, 'destroy'])->add(new JWTMiddleware(['admin']));

// ---- Prescription routes (ownership enforced inside controller) ----
$app->get('/api/prescriptions', [PrescriptionController::class, 'index'])->add(new JWTMiddleware());
$app->get('/api/prescriptions/{id}', [PrescriptionController::class, 'show'])->add(new JWTMiddleware());
$app->post('/api/prescriptions', [PrescriptionController::class, 'store'])->add(new JWTMiddleware(['caregiver', 'admin']));
$app->put('/api/prescriptions/{id}', [PrescriptionController::class, 'update'])->add(new JWTMiddleware(['caregiver', 'admin']));
$app->delete('/api/prescriptions/{id}', [PrescriptionController::class, 'destroy'])->add(new JWTMiddleware(['caregiver', 'admin']));

// ---- DoseLog routes (ownership enforced inside controller) ----
$app->get('/api/dose-logs', [DoseLogController::class, 'index'])->add(new JWTMiddleware());
$app->get('/api/dose-logs/adherence', [DoseLogController::class, 'adherence'])->add(new JWTMiddleware());
$app->post('/api/dose-logs', [DoseLogController::class, 'store'])->add(new JWTMiddleware(['caregiver', 'admin']));
$app->put('/api/dose-logs/{id}/status', [DoseLogController::class, 'updateStatus'])->add(new JWTMiddleware());
$app->delete('/api/dose-logs/{id}', [DoseLogController::class, 'destroy'])->add(new JWTMiddleware(['caregiver', 'admin']));
$app->get('/api/dose-logs/export/{format}', [DoseLogController::class, 'export'])->add(new JWTMiddleware());

// ---- PatientCaregiver routes ----
$app->get('/api/caregiver/patients', [PatientCaregiverController::class, 'myPatients'])->add(new JWTMiddleware(['caregiver']));
$app->get('/api/patient/caregivers', [PatientCaregiverController::class, 'myCaregivers'])->add(new JWTMiddleware(['patient']));
$app->post('/api/patient-caregiver', [PatientCaregiverController::class, 'store'])->add(new JWTMiddleware());
$app->delete('/api/patient-caregiver/{id}', [PatientCaregiverController::class, 'destroy'])->add(new JWTMiddleware());

$app->run();