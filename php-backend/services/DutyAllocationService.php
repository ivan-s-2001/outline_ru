<?php

declare(strict_types=1);

namespace app\services;

use app\models\BaseRecord;
use app\models\DutyAssignment;
use app\models\DutyParticipant;
use app\models\DutySetting;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use RuntimeException;
use Throwable;
use Yii;

final class DutyAllocationService
{
    /** @return DutyAssignment[] */
    public function generate(DutySetting $setting): array
    {
        $participants = DutyParticipant::find()
            ->with('user')
            ->where(['duty_setting_id' => $setting->id, 'is_excluded' => false])
            ->andWhere(['>', 'coefficient', 0])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
        if (!$participants) {
            throw new RuntimeException('Добавьте хотя бы одного участника дежурств.');
        }

        $slotMinutes = max(10, (int)$setting->slot_minutes);
        $startMinutes = $this->timeToMinutes((string)$setting->start_time);
        $endMinutes = $this->timeToMinutes((string)$setting->end_time);
        if ($endMinutes <= $startMinutes) {
            throw new RuntimeException('Время окончания должно быть позже начала.');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            DutyAssignment::deleteAll(['duty_setting_id' => $setting->id]);
            $assignments = [];
            $totals = array_fill_keys(array_map(static fn (DutyParticipant $participant): string => (string)$participant->user_id, $participants), 0);
            $weights = [];
            foreach ($participants as $participant) {
                $weights[(string)$participant->user_id] = max(0.001, (float)$participant->coefficient);
            }

            $period = new DatePeriod(
                new DateTimeImmutable((string)$setting->date_from),
                new DateInterval('P1D'),
                (new DateTimeImmutable((string)$setting->date_to))->modify('+1 day')
            );
            foreach ($period as $date) {
                if ((int)$date->format('N') > 5) {
                    continue;
                }
                $slots = (int)ceil(($endMinutes - $startMinutes) / $slotMinutes);
                $dayOwners = [];
                for ($slot = 0; $slot < $slots; $slot++) {
                    $owner = $this->nextOwner($participants, $totals, $weights, $dayOwners);
                    $dayOwners[] = $owner;
                    $actual = min($slotMinutes, $endMinutes - ($startMinutes + $slot * $slotMinutes));
                    $totals[$owner] += max(0, $actual);
                }

                $groups = $this->groupSlots($dayOwners);
                foreach ($groups as $group) {
                    $from = $startMinutes + $group['start'] * $slotMinutes;
                    $to = min($endMinutes, $startMinutes + $group['end'] * $slotMinutes);
                    if ($to <= $from) {
                        continue;
                    }
                    $assignment = new DutyAssignment([
                        'id' => BaseRecord::uuid(),
                        'duty_setting_id' => $setting->id,
                        'user_id' => $group['userId'],
                        'duty_date' => $date->format('Y-m-d'),
                        'start_time' => $this->minutesToTime($from),
                        'end_time' => $this->minutesToTime($to),
                        'share_coefficient' => $weights[$group['userId']],
                        'status' => 'assigned',
                    ]);
                    if (!$assignment->save()) {
                        throw new RuntimeException('Не удалось сохранить назначение дежурства.');
                    }
                    $assignments[] = $assignment;
                }
            }

            $transaction->commit();
            return $assignments;
        } catch (Throwable $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        }
    }

    /** @param DutyParticipant[] $participants */
    private function nextOwner(array $participants, array $totals, array $weights, array $dayOwners): string
    {
        $bestUser = '';
        $bestScore = INF;
        $lastUser = $dayOwners ? (string)end($dayOwners) : null;
        foreach ($participants as $participant) {
            $userId = (string)$participant->user_id;
            if (!$this->isAvailable($participant, count($dayOwners))) {
                continue;
            }
            $score = $totals[$userId] / $weights[$userId];
            if ($userId === $lastUser) {
                $score += 0.0001;
            }
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestUser = $userId;
            }
        }
        if ($bestUser === '') {
            foreach ($participants as $participant) {
                $userId = (string)$participant->user_id;
                $score = $totals[$userId] / $weights[$userId];
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestUser = $userId;
                }
            }
        }
        if ($bestUser === '') {
            throw new RuntimeException('Не удалось выбрать участника для дежурства.');
        }
        return $bestUser;
    }

    private function isAvailable(DutyParticipant $participant, int $slot): bool
    {
        $snapshot = $participant->getScheduleSnapshotData();
        $excludedSlots = is_array($snapshot['excludedSlots'] ?? null) ? $snapshot['excludedSlots'] : [];
        return !in_array($slot, array_map('intval', $excludedSlots), true);
    }

    /** @return array<int,array{userId:string,start:int,end:int}> */
    private function groupSlots(array $owners): array
    {
        $groups = [];
        foreach ($owners as $index => $owner) {
            $last = array_key_last($groups);
            if ($last !== null && $groups[$last]['userId'] === $owner && $groups[$last]['end'] === $index) {
                $groups[$last]['end'] = $index + 1;
                continue;
            }
            $groups[] = ['userId' => (string)$owner, 'start' => $index, 'end' => $index + 1];
        }
        return $groups;
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));
        return $hours * 60 + $minutes;
    }

    private function minutesToTime(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }
}
