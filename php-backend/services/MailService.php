<?php

declare(strict_types=1);

namespace app\services;

use app\models\BaseRecord;
use app\models\EmailJob;
use RuntimeException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;
use Yii;
use yii\db\Expression;

final class MailService
{
    public function queue(string $to, string $subject, string $text, ?string $html = null): EmailJob
    {
        $job = new EmailJob([
            'id' => BaseRecord::uuid(),
            'to_email' => mb_strtolower(trim($to)),
            'subject' => trim($subject),
            'text_body' => $text,
            'html_body' => $html,
            'status' => 'pending',
            'attempts' => 0,
            'next_attempt_at' => gmdate('Y-m-d H:i:s.u'),
        ]);
        if (!$job->save()) {
            throw new RuntimeException('Не удалось поставить письмо в очередь.');
        }
        return $job;
    }

    public function run(int $limit = 50): array
    {
        $jobs = EmailJob::find()
            ->where(['in', 'status', ['pending', 'failed']])
            ->andWhere(['or', ['next_attempt_at' => null], ['<=', 'next_attempt_at', new Expression('CURRENT_TIMESTAMP(6)')]])
            ->orderBy(['created_at' => SORT_ASC])
            ->limit(max(1, min(500, $limit)))
            ->all();
        $result = ['sent' => 0, 'failed' => 0];
        foreach ($jobs as $job) {
            try {
                $this->send($job);
                $result['sent']++;
            } catch (Throwable $error) {
                $this->fail($job, $error->getMessage());
                $result['failed']++;
            }
        }
        return $result;
    }

    public function send(EmailJob $job): void
    {
        $dsn = trim((string)env('MAILER_DSN', 'smtp://127.0.0.1:1025'));
        if ($dsn === '') {
            throw new RuntimeException('MAILER_DSN не настроен.');
        }
        $fromEmail = trim((string)env('MAIL_FROM_EMAIL', 'outline@local.test'));
        $fromName = trim((string)env('MAIL_FROM_NAME', env('APP_NAME', 'Outline')));
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('MAIL_FROM_EMAIL некорректен.');
        }

        $job->updateAttributes([
            'status' => 'processing',
            'attempts' => (int)$job->attempts + 1,
            'updated_at' => new Expression('CURRENT_TIMESTAMP(6)'),
        ]);
        $email = (new Email())
            ->from(new Address($fromEmail, $fromName))
            ->to($job->to_email)
            ->subject((string)$job->subject)
            ->text((string)$job->text_body);
        if (is_string($job->html_body) && trim($job->html_body) !== '') {
            $email->html((string)$job->html_body);
        }

        (new Mailer(Transport::fromDsn($dsn)))->send($email);
        $job->updateAttributes([
            'status' => 'sent',
            'last_error' => null,
            'next_attempt_at' => null,
            'sent_at' => new Expression('CURRENT_TIMESTAMP(6)'),
            'updated_at' => new Expression('CURRENT_TIMESTAMP(6)'),
        ]);
    }

    private function fail(EmailJob $job, string $error): void
    {
        $attempts = max(1, (int)$job->attempts);
        $permanent = $attempts >= 8;
        $delay = min(86400, 60 * (2 ** min(10, $attempts - 1)));
        $job->updateAttributes([
            'status' => 'failed',
            'last_error' => mb_substr($error, 0, 10000),
            'next_attempt_at' => $permanent ? null : gmdate('Y-m-d H:i:s.u', time() + $delay),
            'updated_at' => new Expression('CURRENT_TIMESTAMP(6)'),
        ]);
        Yii::warning([
            'message' => 'Email delivery failed',
            'jobId' => $job->id,
            'error' => $error,
        ], __METHOD__);
    }
}
