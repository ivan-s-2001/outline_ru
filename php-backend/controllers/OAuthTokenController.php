<?php

declare(strict_types=1);

namespace app\controllers;

use app\services\OAuthService;
use RuntimeException;
use Yii;
use yii\web\Controller;
use yii\web\Response;

final class OAuthTokenController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action): bool
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->headers->set('Cache-Control', 'no-store');
        Yii::$app->response->headers->set('Pragma', 'no-cache');
        return parent::beforeAction($action);
    }

    public function actionIndex(): array
    {
        $body = Yii::$app->request->bodyParams;
        $body = is_array($body) ? $body : [];
        [$basicClientId, $basicSecret] = $this->basicCredentials();
        $clientId = trim((string)($body['client_id'] ?? $basicClientId ?? ''));
        $clientSecret = isset($body['client_secret'])
            ? (string)$body['client_secret']
            : $basicSecret;
        $grantType = trim((string)($body['grant_type'] ?? ''));

        try {
            $tokens = match ($grantType) {
                'authorization_code' => (new OAuthService())->exchangeAuthorizationCode(
                    (string)($body['code'] ?? ''),
                    $clientId,
                    $clientSecret,
                    (string)($body['redirect_uri'] ?? ''),
                    isset($body['code_verifier']) ? (string)$body['code_verifier'] : null
                ),
                'refresh_token' => (new OAuthService())->refresh(
                    (string)($body['refresh_token'] ?? ''),
                    $clientId,
                    $clientSecret
                ),
                default => throw new RuntimeException('Поддерживаются grant_type=authorization_code и refresh_token.'),
            };
            return $tokens;
        } catch (RuntimeException $error) {
            Yii::$app->response->statusCode = 400;
            return [
                'error' => $grantType === '' ? 'invalid_request' : 'invalid_grant',
                'error_description' => $error->getMessage(),
            ];
        }
    }

    /** @return array{0:?string,1:?string} */
    private function basicCredentials(): array
    {
        $authorization = trim((string)Yii::$app->request->headers->get('Authorization', ''));
        if (!preg_match('/^Basic\s+(.+)$/i', $authorization, $match)) {
            return [null, null];
        }
        $decoded = base64_decode($match[1], true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return [null, null];
        }
        [$clientId, $secret] = explode(':', $decoded, 2);
        return [rawurldecode($clientId), rawurldecode($secret)];
    }
}
