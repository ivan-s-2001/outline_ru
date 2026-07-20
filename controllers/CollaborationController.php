<?php

namespace app\controllers;

use app\components\Jwt;
use app\models\Document;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CollaborationController extends BaseController
{
    public function actionToken(int $document): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = Document::findOne([
            'id' => $document,
            'workspace_id' => $this->currentUser()->workspace_id,
        ]);
        if (!$model) {
            throw new NotFoundHttpException('Документ не найден.');
        }

        $now = time();
        $canUpdate = $this->currentUser()->canManage() || (int)$model->created_by === (int)$this->currentUser()->id || $model->permission_mode === 'workspace_edit';
        $token = Jwt::encode([
            'iss' => 'outline-php',
            'aud' => 'outline-collaboration',
            'iat' => $now,
            'nbf' => $now - 5,
            'exp' => $now + 600,
            'sub' => (string)$this->currentUser()->id,
            'workspace' => (int)$this->currentUser()->workspace_id,
            'document' => (int)$model->id,
            'name' => $this->currentUser()->fullName,
            'color' => $this->currentUser()->color,
            'canRead' => true,
            'canUpdate' => $canUpdate,
        ], (string)env('COLLABORATION_SECRET', ''));

        return [
            'token' => $token,
            'expiresAt' => gmdate(DATE_ATOM, $now + 600),
            'canUpdate' => $canUpdate,
            'url' => (string)env('COLLABORATION_URL', 'ws://outline.local/collaboration'),
        ];
    }
}
