<?php

declare(strict_types=1);

namespace app\services;

use app\models\BaseRecord;
use app\models\ProductionCalendarDay;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use JsonException;
use RuntimeException;
use Yii;

final class ProductionCalendarService
{
    public function syncYear(string $workspaceId, int $year): int
    {
        if ($year < 2000 || $year > 2200) {
            throw new RuntimeException('Некорректный год производственного календаря.');
        }

        $baseUrl = rtrim((string)env('PRODUCTION_CALENDAR_API_URL', 'https://production-calendar.ru/v2'), '/');
        $country = trim((string)env('PRODUCTION_CALENDAR_COUNTRY', 'ru')) ?: 'ru';
        $region = trim((string)env('PRODUCTION_CALENDAR_REGION', ''));
        $token = trim((string)env('PRODUCTION_CALENDAR_TOKEN', ''));
        $query = ['format' => 'json', 'week_type' => 5, 'date_format' => 'iso'];
        if ($region !== '') {
            $query['region'] = $region;
        }
        $url = sprintf('%s/%s/%d/days?%s', $baseUrl, rawurlencode($country), $year, http_build_query($query));
        $headers = ['Accept: application/json'];
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);
        $payload = @file_get_contents($url, false, $context);
        if (!is_string($payload) || $payload === '') {
            throw new RuntimeException('Не удалось загрузить производственный календарь.');
        }
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new RuntimeException('API производственного календаря вернул повреждённый JSON.', 0, $error);
        }
        $days = is_array($decoded['days'] ?? null) ? $decoded['days'] : [];
        if (!$days) {
            throw new RuntimeException('API производственного календаря не вернул дни.');
        }

        $version = sprintf('%s-%d-%s', $country, $year, gmdate('YmdHis'));
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $count = 0;
            foreach ($days as $day) {
                if (!is_array($day)) {
                    continue;
                }
                $date = (string)($day['date'] ?? '');
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    continue;
                }
                $type = is_array($day['type'] ?? null) ? $day['type'] : [];
                $isWorking = (bool)($type['is_working'] ?? false);
                $hours = isset($day['working_hours']) ? (float)$day['working_hours'] : ($isWorking ? 8.0 : 0.0);

                $model = ProductionCalendarDay::findOne([
                    'workspace_id' => $workspaceId,
                    'calendar_date' => $date,
                ]) ?? new ProductionCalendarDay([
                    'id' => BaseRecord::uuid(),
                    'workspace_id' => $workspaceId,
                    'calendar_date' => $date,
                ]);
                $model->is_working = $isWorking;
                $model->is_shortened = $isWorking && $hours > 0 && $hours < 8;
                $model->name = trim((string)($day['title'] ?? $type['name'] ?? '')) ?: null;
                $model->source = $baseUrl;
                $model->source_version = $version;
                if (!$model->save()) {
                    throw new RuntimeException('Не удалось сохранить день производственного календаря.');
                }
                $count++;
            }
            $transaction->commit();
            return $count;
        } catch (\Throwable $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        }
    }

    /** @return array{days:int,source:string,version:?string} */
    public function calculate(string $workspaceId, string $dateFrom, string $dateTo): array
    {
        $from = new DateTimeImmutable($dateFrom);
        $to = new DateTimeImmutable($dateTo);
        if ($to < $from) {
            throw new RuntimeException('Дата окончания не может быть раньше начала.');
        }
        if ($to->diff($from)->days > 730) {
            throw new RuntimeException('Период отпуска слишком большой.');
        }

        $stored = ProductionCalendarDay::find()
            ->where(['workspace_id' => $workspaceId])
            ->andWhere(['between', 'calendar_date', $from->format('Y-m-d'), $to->format('Y-m-d')])
            ->indexBy('calendar_date')
            ->all();
        $period = new DatePeriod($from, new DateInterval('P1D'), $to->modify('+1 day'));
        $days = 0;
        $usedFallback = false;
        $versions = [];
        $sources = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $record = $stored[$key] ?? null;
            if ($record) {
                if ((bool)$record->is_working) {
                    $days++;
                }
                $versions[(string)$record->source_version] = true;
                $sources[(string)$record->source] = true;
                continue;
            }

            $usedFallback = true;
            if ((int)$date->format('N') <= 5) {
                $days++;
            }
        }

        return [
            'days' => $days,
            'source' => $usedFallback ? 'fallback-weekdays' : (array_key_first($sources) ?: 'production-calendar-cache'),
            'version' => $usedFallback ? null : (array_key_first($versions) ?: null),
        ];
    }
}
