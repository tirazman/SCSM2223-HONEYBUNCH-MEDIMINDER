<?php
namespace App\Controller;

use App\Repositories\NotificationRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotificationController {
    private $repo;

    public function __construct(NotificationRepository $repo) {
        $this->repo = $repo;
    }

    public function getDue(Request $request, Response $response) {
        $userId = $request->getAttribute('user_id'); // From JWT Middleware
        $data = $this->repo->getDueNotifications($userId);
        
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}