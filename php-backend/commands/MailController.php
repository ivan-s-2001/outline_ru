<?php

declare(strict_types=1);

namespace app\commands;

use app\services\MailService;
use yii\console\Controller;
use yii\console\ExitCode;

final class MailController extends Controller
{
    public function actionRun(int $limit = 50): int
    {
        $result = (new MailService())->run($limit);
        $this->stdout(sprintf("Sent: %d; failed: %d\n", $result['sent'], $result['failed']));
        return $result['failed'] > 0 ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
}
