<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\web\Controller;

final class SiteController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionHealth(): array
    {
        Yii::$app->db->createCommand('SELECT 1')->queryScalar();
        return [
            'ok' => true,
            'service' => 'outline-yii-api',
            'database' => 'mariadb',
            'time' => gmdate(DATE_ATOM),
        ];
    }
}
