<?php

namespace App\Controller;

use App\Repositories\NotificationRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class NotificationController
{
    private NotificationRepository $repo;

    public function __construct(NotificationRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * GET /api/notifications/due
     *
     * Returns DoseLog entries scheduled within the next hour for the
     * authenticated user. The user_id is injected by the JWT middleware.
     */
    public function getDue(Request $request, Response $response): Response
    {
        // ── 1. Authenticate ───────────────────────────────────────────────────
        $userId = $request->getAttribute('user_id');

        if (empty($userId) || !is_numeric($userId)) {
            return $this->json($response, ['error' => 'Unauthorized.'], 401);
        }

        // ── 2. Fetch & return ─────────────────────────────────────────────────
        try {
            $data = $this->repo->getDueNotifications((int) $userId);
            return $this->json($response, $data, 200);

        } catch (Throwable $e) {
            // Do not expose internal error detail to the client
            error_log('[NotificationController::getDue] ' . $e->getMessage());
            return $this->json($response, ['error' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * GET /api/notifications
     *
     * Returns the user's full Notification history (alerts, info messages, etc.)
     * from the Notification table — separate from the DoseLog reminder feed.
     */
    public function getHistory(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');

        if (empty($userId) || !is_numeric($userId)) {
            return $this->json($response, ['error' => 'Unauthorized.'], 401);
        }

        try {
            $data = $this->repo->getNotificationHistory((int) $userId);
            return $this->json($response, $data, 200);

        } catch (Throwable $e) {
            error_log('[NotificationController::getHistory] ' . $e->getMessage());
            return $this->json($response, ['error' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * PATCH /api/notifications/{id}/read
     *
     * Marks a single Notification row as read. Verifies ownership before
     * updating so users cannot mark each other's notifications.
     */
    public function markRead(Request $request, Response $response, array $args): Response
    {
        $userId         = $request->getAttribute('user_id');
        $notificationId = (int) ($args['id'] ?? 0);

        if (empty($userId) || !is_numeric($userId)) {
            return $this->json($response, ['error' => 'Unauthorized.'], 401);
        }

        if ($notificationId <= 0) {
            return $this->json($response, ['error' => 'Invalid notification ID.'], 400);
        }

        try {
            $updated = $this->repo->markAsRead($notificationId, (int) $userId);

            if (!$updated) {
                return $this->json($response, ['error' => 'Notification not found.'], 404);
            }

            return $this->json($response, ['success' => true], 200);

        } catch (Throwable $e) {
            error_log('[NotificationController::markRead] ' . $e->getMessage());
            return $this->json($response, ['error' => 'An unexpected error occurred.'], 500);
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Encode $data as JSON and write it to $response with the correct
     * Content-Type header and HTTP status code.
     */
    private function json(Response $response, mixed $data, int $status): Response
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($payload);

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}