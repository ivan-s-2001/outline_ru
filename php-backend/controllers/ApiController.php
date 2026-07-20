<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Document;
use app\models\forms\InstallForm;
use app\models\User;
use app\services\AuthService;
use app\services\PermissionService;
use Firebase\JWT\JWT;
use Yii;
use yii\db\Expression;
use yii\filters\Cors;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

final class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action): bool
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

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
        $form = new InstallForm();
        $form->workspaceName = trim((string)($body['workspaceName'] ?? ''));
        $form->login = mb_strtolower(trim((string)($body['login'] ?? 'admin')));
        $form->email = mb_strtolower(trim((string)($body['email'] ?? '')));
        $form->fullName = trim((string)($body['fullName'] ?? $body['name'] ?? $body['userName'] ?? ''));
        $form->password = (string)($body['password'] ?? '');
        $form->passwordRepeat = (string)($body['passwordRepeat'] ?? $body['password'] ?? '');

        if (!$form->validate()) {
            throw new BadRequestHttpException($this->firstError($form->getFirstErrors()));
        }

        $user = (new AuthService())->install($form);
        Yii::$app->user->login($user, 30 * 86400);

        return ['data' => $this->presentUser($user)];
    }

    private function authLogin(): array
    {
        if (!User::find()->exists()) {
            throw new BadRequestHttpException('Сначала выполните первичную настройку');
        }

        $body = $this->body();
        $identity = mb_strtolower(trim((string)($body['login'] ?? $body['email'] ?? '')));
        $password = (string)($body['password'] ?? '');
        $remember = filter_var($body['remember'] ?? true, FILTER_VALIDATE_BOOL);

        if (mb_strlen($identity) < 3 || $password === '') {
            throw new BadRequestHttpException('Укажите логин и пароль');
        }

        $user = User::findByLoginOrEmail($identity);
        if (!$user || !$user->validatePassword($password)) {
            usleep(300000);
            throw new UnauthorizedHttpException('Неверный логин или пароль');
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
        return ['data' => $this->presentUser($this->requireUser())];
    }

    private function authConfig(): array
    {
        return [
            'data' => [
                'name' => (string)env('APP_NAME', 'Outline'),
                'providers' => [[
                    'id' => 'password',
                    'name' => 'Логин и пароль',
                    'authUrl' => '/login',
                ]],
                'passwordAuthEnabled' => true,
                'loginField' => 'login',
                'magicLinkAuthEnabled' => false,
                'oidcAuthEnabled' => false,
            ],
        ];
    }

    private function collaborationToken(): array
    {
        $user = $this->requireUser();
        $body = $this->body();
        $documentId = trim((string)($body['documentId'] ?? ''));
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $documentId)) {
            throw new BadRequestHttpException('Некорректный идентификатор документа');
        }

        $document = Document::find()
            ->with('collection')
            ->where([
                'id' => $documentId,
                'workspace_id' => $user->workspace_id,
                'archived_at' => null,
                'deleted_at' => null,
            ])
            ->one();
        if (!$document) {
            throw new NotFoundHttpException('Документ не найден');
        }

        $permissions = new PermissionService();
        $canRead = $permissions->canReadDocument($user, $document);
        $canUpdate = $permissions->canUpdateDocument($user, $document);
        if (!$canRead) {
            throw new ForbiddenHttpException('Нет доступа к документу');
        }

        $now = time();
        $secret = (string)env('JWT_SECRET', '');
        if (strlen($secret) < 32) {
            throw new BadRequestHttpException('JWT_SECRET должен содержать не менее 32 символов');
        }

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
            'canRead' => $canRead,
            'canUpdate' => $canUpdate,
        ], $secret, 'HS256');

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
            'login' => $user->login,
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

    private function firstError(array $errors): string
    {
        foreach ($errors as $error) {
            return (string)$error;
        }
        return 'Некорректные данные';
    }
}
