<?php

declare(strict_types=1);

namespace app\tests;

use app\models\BaseRecord;
use app\models\DutyAssignment;
use app\models\DutyParticipant;
use app\models\DutySetting;
use app\models\ProductionCalendarDay;
use app\models\Shift;
use app\services\DutyAllocationService;
use app\services\ProductionCalendarService;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\db\Transaction;

final class WorkforceServiceTest extends TestCase
{
    private Transaction $transaction;
    private string $workspaceId;
    private string $adminId;
    private string $employeeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transaction = Yii::$app->db->beginTransaction();
        $this->workspaceId = BaseRecord::uuid();
        $this->adminId = BaseRecord::uuid();
        $this->employeeId = BaseRecord::uuid();
        $now = gmdate('Y-m-d H:i:s.u');

        Yii::$app->db->createCommand()->insert('{{%workspaces}}', [
            'id' => $this->workspaceId,
            'name' => 'Workforce tests',
            'default_language' => 'ru_RU',
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();
        foreach ([
            [$this->adminId, 'workforce-admin', 'owner'],
            [$this->employeeId, 'workforce-employee', 'member'],
        ] as [$id, $login, $role]) {
            Yii::$app->db->createCommand()->insert('{{%users}}', [
                'id' => $id,
                'workspace_id' => $this->workspaceId,
                'email' => $login . '@example.local',
                'login' => $login,
                'password_hash' => 'not-used',
                'auth_key' => bin2hex(random_bytes(32)),
                'last_name' => 'Тестов',
                'first_name' => $login,
                'middle_name' => 'Кадрович',
                'role' => $role,
                'status' => 'active',
                'color' => '#6B7280',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
        }
    }

    protected function tearDown(): void
    {
        if ($this->transaction->isActive) {
            $this->transaction->rollBack();
        }
        parent::tearDown();
    }

    public function testShiftDurationSubtractsBreak(): void
    {
        $shift = new Shift([
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'break_minutes' => 60,
        ]);
        self::assertSame(480, $shift->durationMinutes());
    }

    public function testProductionCalendarCountsTransferredWorkingDay(): void
    {
        foreach ([
            ['2026-01-09', false, 'Праздничный день'],
            ['2026-01-10', true, 'Перенесённый рабочий день'],
            ['2026-01-11', false, 'Выходной'],
        ] as [$date, $working, $name]) {
            $day = new ProductionCalendarDay([
                'id' => BaseRecord::uuid(),
                'workspace_id' => $this->workspaceId,
                'calendar_date' => $date,
                'is_working' => $working,
                'is_shortened' => false,
                'name' => $name,
                'source' => 'test-calendar',
                'source_version' => '2026-test',
            ]);
            self::assertTrue($day->save(), json_encode($day->getErrors(), JSON_UNESCAPED_UNICODE));
        }

        $result = (new ProductionCalendarService())->calculate(
            $this->workspaceId,
            '2026-01-09',
            '2026-01-11'
        );
        self::assertSame(1, $result['days']);
        self::assertSame('test-calendar', $result['source']);
        self::assertSame('2026-test', $result['version']);
    }

    public function testDutyAllocationUsesCoefficientsAndCoversEveryMinute(): void
    {
        $setting = new DutySetting([
            'id' => BaseRecord::uuid(),
            'workspace_id' => $this->workspaceId,
            'name' => 'Одночасовое дежурство',
            'date_from' => '2026-07-20',
            'date_to' => '2026-07-20',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'slot_minutes' => 10,
            'status' => 'draft',
            'created_by_id' => $this->adminId,
        ]);
        self::assertTrue($setting->save(), json_encode($setting->getErrors(), JSON_UNESCAPED_UNICODE));

        foreach ([[$this->adminId, 1.0], [$this->employeeId, 0.5]] as [$userId, $coefficient]) {
            $participant = new DutyParticipant([
                'id' => BaseRecord::uuid(),
                'duty_setting_id' => $setting->id,
                'user_id' => $userId,
                'coefficient' => $coefficient,
                'is_excluded' => false,
                'schedule_snapshot' => [],
            ]);
            self::assertTrue($participant->save(), json_encode($participant->getErrors(), JSON_UNESCAPED_UNICODE));
        }

        $assignments = (new DutyAllocationService())->generate($setting);
        self::assertNotEmpty($assignments);
        $minutes = [$this->adminId => 0, $this->employeeId => 0];
        foreach (DutyAssignment::find()->where(['duty_setting_id' => $setting->id])->all() as $assignment) {
            $start = strtotime('1970-01-01 ' . $assignment->start_time);
            $end = strtotime('1970-01-01 ' . $assignment->end_time);
            $minutes[$assignment->user_id] += (int)(($end - $start) / 60);
        }

        self::assertSame(60, array_sum($minutes));
        self::assertSame(40, $minutes[$this->adminId]);
        self::assertSame(20, $minutes[$this->employeeId]);
    }
}
