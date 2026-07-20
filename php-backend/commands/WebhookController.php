<?php

declare(strict_types=1);

namespace app\commands;

use app\services\WebhookService;
use yii\console\Controller;
use yii\console\ExitCode;

final class WebhookController extends Controller
{
    public function actionRun(int $limit = 50): int
    {
        $result = (new WebhookService())->run($limit);
        $this->stdout(sprintf(
            "Delivered: %d; failed: %d; skipped: %d\n",
            $result['delivered'],
            $result['failed'],
            $result['skipped']
        ));
        return $result['failed'] > 0 ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
}
