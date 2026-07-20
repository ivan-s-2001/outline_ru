<?php

declare(strict_types=1);

namespace app\components;

use Yii;
use yii\web\ForbiddenHttpException;

abstract class AdminController extends AuthenticatedController
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!$this->currentUser()->isAdmin()) {
            throw new ForbiddenHttpException('Раздел доступен только администраторам.');
        }
        return true;
    }
}
