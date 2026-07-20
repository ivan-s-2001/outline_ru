<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Notification;
use Yii;
use yii\web\Response;

final class RealtimeController extends AuthenticatedController
{
    public function actionStream(?string $after = null): string
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/event-stream; charset=UTF-8');
        Yii::$app->response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        Yii::$app->response->headers->set('Connection', 'keep-alive');
        Yii::$app->response->headers->set('X-Accel-Buffering', 'no');
        if (Yii::$app->has('session') && Yii::$app->session->getIsActive()) {
            Yii::$app->session->close();
        }
        @set_time_limit(30);
        @ignore_user_abort(true);

        $cursor = $this->normalizeCursor($after);
        $deadline = microtime(true) + 25;
        while (microtime(true) < $deadline && !connection_aborted()) {
            $notifications = Notification::find()
                ->where([
                    'workspace_id' => $this->workspaceId(),
                    'user_id' => $this->currentUser()->id,
                    'archived_at' => null,
                ])
                ->andWhere(['>', 'created_at', $cursor])
                ->orderBy(['created_at' => SORT_ASC])
                ->limit(100)
                ->all();
            foreach ($notifications as $notification) {
                $cursor = max($cursor, (string)$notification->created_at);
                $payload = [
                    'id' => $notification->id,
                    'event' => $notification->event,
                    'title' => $notification->title,
                    'documentId' => $notification->document_id,
                    'createdAt' => $notification->created_at,
                    'unread' => $notification->read_at === null,
                    'cursor' => $cursor,
                ];
                echo "id: " . str_replace(["\r", "\n"], '', (string)$notification->id) . "\n";
                echo "event: notification\n";
                echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
            }
            echo 'event: heartbeat' . "\n";
            echo 'data: ' . json_encode(['cursor' => $cursor], JSON_UNESCAPED_SLASHES) . "\n\n";
            if (function_exists('fastcgi_finish_request')) {
                @ob_flush();
            }
            @flush();
            usleep(2_000_000);
        }
        return '';
    }

    private function normalizeCursor(?string $after): string
    {
        if ($after !== null && preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?$/', $after)) {
            return str_replace('T', ' ', $after);
        }
        return gmdate('Y-m-d H:i:s.u');
    }
}
