<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class DoctorController extends Controller
{
    public function actionIndex(): int
    {
        $checks = [];
        $checks['PHP >= 8.2'] = version_compare(PHP_VERSION, '8.2.0', '>=');
        $checks['PDO MySQL'] = extension_loaded('pdo_mysql');
        $checks['mbstring'] = extension_loaded('mbstring');
        try {
            Yii::$app->db->open();
            $version = Yii::$app->db->createCommand('SELECT VERSION()')->queryScalar();
            $checks['MariaDB соединение (' . $version . ')'] = true;
        } catch (\Throwable $e) {
            $checks['MariaDB: ' . $e->getMessage()] = false;
        }
        $ok = true;
        foreach ($checks as $name => $status) {
            $this->stdout(($status ? '[OK] ' : '[FAIL] ') . $name . PHP_EOL);
            $ok = $ok && $status;
        }
        return $ok ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }
}
