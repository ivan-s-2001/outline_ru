<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\OAuthApp;
use app\services\OAuthService;
use RuntimeException;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class OAuthAuthorizeController extends AuthenticatedController
{
    public function actionIndex(): Response|string
    {
        $request = Yii::$app->request;
        $clientId = trim((string)$request->getBodyParam('client_id', $request->get('client_id', '')));
        $redirectUri = trim((string)$request->getBodyParam('redirect_uri', $request->get('redirect_uri', '')));
        $responseType = trim((string)$request->getBodyParam('response_type', $request->get('response_type', 'code')));
        $scopeText = trim((string)$request->getBodyParam('scope', $request->get('scope', 'read')));
        $state = (string)$request->getBodyParam('state', $request->get('state', ''));
        $codeChallenge = trim((string)$request->getBodyParam('code_challenge', $request->get('code_challenge', '')));
        $codeChallengeMethod = trim((string)$request->getBodyParam('code_challenge_method', $request->get('code_challenge_method', '')));

        if ($responseType !== 'code') {
            throw new BadRequestHttpException('Поддерживается только response_type=code.');
        }
        $app = OAuthApp::findOne([
            'client_id' => $clientId,
            'workspace_id' => $this->workspaceId(),
            'is_active' => true,
        ]);
        if (!$app) {
            throw new NotFoundHttpException('OAuth-приложение не найдено.');
        }
        if (!$app->allowsRedirect($redirectUri)) {
            throw new BadRequestHttpException('Redirect URI не зарегистрирован.');
        }
        $scopes = array_values(array_unique(array_filter(preg_split('/\s+/', $scopeText) ?: [])));
        if (!$app->allowsScopes($scopes)) {
            throw new BadRequestHttpException('Запрошены недоступные разрешения.');
        }

        if ($request->isPost) {
            if ($request->post('decision') !== 'allow') {
                return $this->redirect($this->redirectUri($redirectUri, [
                    'error' => 'access_denied',
                    'error_description' => 'Пользователь отклонил запрос доступа',
                    'state' => $state,
                ]));
            }
            try {
                $code = (new OAuthService())->issueAuthorizationCode(
                    $app,
                    $this->currentUser(),
                    $redirectUri,
                    $scopes,
                    $codeChallenge ?: null,
                    $codeChallengeMethod ?: null
                );
            } catch (RuntimeException $error) {
                throw new BadRequestHttpException($error->getMessage(), 0, $error);
            }
            return $this->redirect($this->redirectUri($redirectUri, [
                'code' => $code,
                'state' => $state,
            ]));
        }

        $this->layout = 'auth';
        return $this->render('index', [
            'app' => $app,
            'scopes' => $scopes,
            'params' => compact('clientId', 'redirectUri', 'responseType', 'scopeText', 'state', 'codeChallenge', 'codeChallengeMethod'),
        ]);
    }

    private function redirectUri(string $uri, array $params): string
    {
        $params = array_filter($params, static fn ($value): bool => $value !== '');
        $separator = str_contains($uri, '?') ? '&' : '?';
        return $uri . $separator . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
