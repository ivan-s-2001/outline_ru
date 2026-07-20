<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AdminController;
use app\models\BaseRecord;
use app\models\OAuthAccessToken;
use app\models\OAuthApp;
use app\services\OAuthService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class OAuthAppController extends AdminController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'rotate' => ['POST'],
                    'revoke-tokens' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'provider' => new ActiveDataProvider([
                'query' => OAuthApp::find()->where(['workspace_id' => $this->workspaceId()])->orderBy(['created_at' => SORT_DESC]),
                'pagination' => ['pageSize' => 30],
            ]),
        ]);
    }

    public function actionView(string $id): string
    {
        $model = $this->findModel($id);
        return $this->render('view', [
            'model' => $model,
            'tokens' => new ActiveDataProvider([
                'query' => OAuthAccessToken::find()->with('user')->where(['app_id' => $model->id])->orderBy(['created_at' => SORT_DESC]),
                'pagination' => ['pageSize' => 50],
            ]),
        ]);
    }

    public function actionCreate(?string $id = null): Response|string
    {
        $model = $id ? $this->findModel($id) : new OAuthApp([
            'id' => BaseRecord::uuid(),
            'workspace_id' => $this->workspaceId(),
            'client_id' => (new OAuthService())->generateClientId(),
            'redirect_uris' => [],
            'scopes' => ['read'],
            'is_confidential' => true,
            'is_active' => true,
            'created_by_id' => $this->currentUser()->id,
        ]);
        $redirectUrisText = implode("\n", $model->getRedirectUris());
        $scopesText = implode(' ', $model->getScopes());

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $redirectUrisText = trim((string)Yii::$app->request->post('redirectUrisText', ''));
            $scopesText = trim((string)Yii::$app->request->post('scopesText', 'read'));
            $uris = array_values(array_unique(array_filter(array_map('trim', preg_split('/\R+/', $redirectUrisText) ?: []))));
            foreach ($uris as $uri) {
                if (!filter_var($uri, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($uri, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                    $model->addError('redirect_uris', 'Каждый Redirect URI должен быть полным HTTP/HTTPS URL.');
                    break;
                }
            }
            $scopes = array_values(array_unique(array_intersect(['read', 'write', 'create'], preg_split('/[\s,]+/', $scopesText) ?: [])));
            $model->redirect_uris = $uris;
            $model->scopes = $scopes ?: ['read'];
            $newSecret = null;
            if ($model->isNewRecord && $model->is_confidential) {
                $newSecret = (new OAuthService())->generateClientSecret();
                (new OAuthService())->setClientSecret($model, $newSecret);
            }
            if (!$model->hasErrors() && $model->save()) {
                if ($newSecret !== null) {
                    Yii::$app->session->setFlash('oauthClientSecret', $newSecret);
                }
                Yii::$app->session->setFlash('success', 'OAuth-приложение сохранено.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('form', compact('model', 'redirectUrisText', 'scopesText'));
    }

    public function actionRotate(string $id): Response
    {
        $model = $this->findModel($id);
        $secret = (new OAuthService())->generateClientSecret();
        (new OAuthService())->setClientSecret($model, $secret);
        $model->save(false, ['client_secret_hash', 'updated_at']);
        Yii::$app->session->setFlash('oauthClientSecret', $secret);
        Yii::$app->session->setFlash('success', 'Секрет OAuth-приложения обновлён.');
        return $this->redirect(['view', 'id' => $model->id]);
    }

    public function actionRevokeTokens(string $id): Response
    {
        $model = $this->findModel($id);
        OAuthAccessToken::updateAll(['revoked_at' => new Expression('CURRENT_TIMESTAMP(6)')], ['app_id' => $model->id, 'revoked_at' => null]);
        Yii::$app->session->setFlash('success', 'Все OAuth-токены приложения отозваны.');
        return $this->redirect(['view', 'id' => $model->id]);
    }

    public function actionDelete(string $id): Response
    {
        $model = $this->findModel($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'OAuth-приложение удалено.');
        return $this->redirect(['index']);
    }

    private function findModel(string $id): OAuthApp
    {
        $model = OAuthApp::findOne(['id' => $id, 'workspace_id' => $this->workspaceId()]);
        if (!$model) {
            throw new NotFoundHttpException('OAuth-приложение не найдено.');
        }
        return $model;
    }
}
