<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\User;
use Firebase\JWT\JWT;
use Throwable;
use Yii;
use yii\db\Expression;
use yii\filters\Cors;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UnauthorizedHttpException;

final class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'cors' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => [(string)env('APP_URL', 'http://outline.local')],
                    'Access-Control-Request-Method' => ['POST', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age' => 3600,
                ],
            ],
        ];
    }

    public function actionOptions(string $method): array
    {
        return ['ok' => true, 'method' => $method];
    }

    public function actionDispatch(string $method): array
    {
        return match ($method) {
            'installation.status' => $this->installationStatus(),
            'installation.setup' => $this->installationSetup(),
            'auth.login' => $this->authLogin(),
            'auth.logout' => $this->authLogout(),
            'auth.info' => $this->authInfo(),
            'auth.config' => $this->authConfig(),
            'auth.collaborationToken' => $this->collaborationToken(),
            default => throw new NotFoundHttpException('Неизвестный метод API'),
        };
    }

    private function installationStatus(): array
    {
        return [
            'data' => [
                'needsSetup' => !User::find()->exists(),
                'authentication' => ['password'],
            ],
        ];
    }

    private function installationSetup(): array
    {
        if (User::find()->exists()) {
            throw new ForbiddenHttpException('Установка уже выполнена');
        }

        $body = $this->body();
        $workspaceName = $this->requiredString($body, 'workspaceName', 2, 255);
        $email = mb_strtolower($this->requiredString($body, 'email', 3, 255));
        $password = $this->requiredString($body, 'password', 10, 1024);
        [$lastName, $firstName, $middleName] = $this->readFullName($body);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('Укажите корректный email');
        }

        $workspaceId = $this->uuid();
        $userId = $this->uuid();
        $now = gmdate('Y-m-d H:i:s.u');

        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand()->insert('{{%workspaces}}', [
                'id' => $workspaceId,
                'name' => $workspaceName,
                'default_language' => 'ru_RU',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            $user = new User();
            $user->id = $userId;
            $user->workspace_id = $workspaceId;
            $user->email = $email;
            $user->last_name = $lastName;
            $user->first_name = $firstName;
            $user->middle_name = $middleName;
            $user->role = 'owner';
            $user->status = 'active';
            $user->auth_key = Yii::$app->security->generateRandomString(64);
            $user->color = '#4E5C6E';
            $user->created_at = $now;
            $user->updated_at = $now;
            $user->setPassword($password);

            if (!$user->save()) {
                throw new BadRequestHttpException($this->firstModelError($user));
            }

            $transaction->commit();
            Yii::$app->user->login($user, 30 * 86400);

            return ['data' => $this->presentUser($user)];
        } catch (Throwable $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        }
    }

    private function authLogin(): array
    {
        if (!User::find()->exists()) {
            throw new BadRequestHttpException('Сначала выполните первичную настройку');
        }

        $body = $this->body();
        $email = mb_strtolower($this->requiredString($body, 'email', 3, 255));
        $password = $this->requiredString($body, 'password', 1, 1024);
        $remember = filter_var($body['remember'] ?? true, FILTER_VALIDATE_BOOL);
        $user = User::findByEmail($email);

        if (!$user || !$user->validatePassword($password)) {
            usleep(300000);
            throw new UnauthorizedHttpException('Неверный email или пароль');
        }

        if (!Yii::$app->user->login($user, $remember ? 30 * 86400 : 0)) {
            throw new UnauthorizedHttpException('Не удалось создать сессию');
        }

        $user->updateAttributes([
            'last_active_at' => new Expression('CURRENT_TIMESTAMP(6)'),
        ]);

        return ['data' => $this->presentUser($user)];
    }

    private function authLogout(): array
    {
        Yii::$app->user->logout(true);
        return ['data' => ['success' => true]];
    }

    private function authInfo(): array
    {
        $user = $this->requireUser();
        return ['data' => $this->presentUser($user)];
    }

    private function authConfig(): array
    {
        return [
            'data' => [
                'name' => (string)env('APP_NAME', 'Outline'),
                'providers' => [[
                    'id' => 'password',
                    'name' => 'Email и пароль',
                    'authUrl' => '/login',
                ]],
                'passwordAuthEnabled' => true,
                'magicLinkAuthEnabled' => false,
                'oidcAuthEnabled' => false,
            ],
        ];
    }

    private function collaborationToken(): array
    {
        $user = $this->requireUser();
        $body = $this->body();
        $documentId = $this->requiredString($body, 'documentId', 36, 36);

        $document = Yii::$app->db->createCommand(
            'SELECT id, workspace_id, collection_id FROM {{%documents}} WHERE id=:id AND deleted_at IS NULL LIMIT 1',
            [':id' => $documentId]
        )->queryOne();

        if (!$document || $document['workspace_id'] !== $user->workspace_id) {
            throw new NotFoundHttpException('Документ не найден');
        }

        $now = time();
        $token = JWT::encode([
            'iss' => 'outline-yii-api',
            'aud' => 'outline-collaboration',
            'sub' => $user->id,
            'iat' => $now,
            'exp' => $now + 300,
            'workspace' => $user->workspace_id,
            'document' => $documentId,
            'name' => $user->getFullName(),
            'color' => $user->color,
            'canRead' => true,
            'canUpdate' => true,
        ], (string)env('JWT_SECRET', ''), 'HS256');

        return ['data' => ['token' => $token, 'expiresIn' => 300]];
    }

    private function requireUser(): User
    {
        $identity = Yii::$app->user->identity;
        if (!$identity instanceof User) {
            throw new UnauthorizedHttpException('Требуется вход');
        }
        return $identity;
    }

    private function presentUser(User $user): array
    {
        $workspace = Yii::$app->db->createCommand(
            'SELECT id, name, default_language FROM {{%workspaces}} WHERE id=:id LIMIT 1',
            [':id' => $user->workspace_id]
        )->queryOne();

        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->getFullName(),
            'lastName' => $user->last_name,
            'firstName' => $user->first_name,
            'middleName' => $user->middle_name,
            'role' => $user->role,
            'isAdmin' => $user->isAdmin(),
            'color' => $user->color,
            'avatarUrl' => $user->avatar_url,
            'language' => $workspace['default_language'] ?? 'ru_RU',
            'team' => [
                'id' => $workspace['id'] ?? $user->workspace_id,
                'name' => $workspace['name'] ?? '',
            ],
        ];
    }

    private function body(): array
    {
        $body = Yii::$app->request->bodyParams;
        if (!is_array($body)) {
            throw new BadRequestHttpException('Ожидается JSON-объект');
        }
        return $body;
    }

    private function requiredString(array $body, string $field, int $min, int $max): string
    {
        $value = trim((string)($body[$field] ?? ''));
        $length = mb_strlen($value);
        if ($length < $min || $length > $max) {
            throw new BadRequestHttpException(sprintf('Некорректное поле %s', $field));
        }
        return $value;
    }

    private function readFullName(array $body): array
    {
        $lastName = trim((string)($body['lastName'] ?? ''));
        $firstName = trim((string)($body['firstName'] ?? ''));
        $middleName = trim((string)($body['middleName'] ?? ''));

        if ($lastName === '' || $firstName === '' || $middleName === '') {
            $parts = preg_split('/\s+/u', trim((string)($body['name'] ?? $body['userName'] ?? ''))) ?: [];
            if (count($parts) < 3) {
                throw new BadRequestHttpException('Пишите ФИО через пробел: фамилия имя отчество');
            }
            $lastName = (string)array_shift($parts);
            $firstName = (string)array_shift($parts);
            $middleName = implode(' ', $parts);
        }

        foreach ([$lastName, $firstName, $middleName] as $part) {
            if (mb_strlen($part) > 120) {
                throw new BadRequestHttpException('Часть ФИО слишком длинная');
            }
        }

        return [$lastName, $firstName, $middleName];
    }

    private function firstModelError(User $model): string
    {
        foreach ($model->getFirstErrors() as $error) {
            return (string)$error;
        }
        return 'Не удалось сохранить пользователя';
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf('%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
