<?php

declare(strict_types=1);

namespace app\models;

final class EmailJob extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%email_jobs}}';
    }

    public function rules(): array
    {
        return [
            [['to_email', 'subject', 'text_body'], 'required'],
            [['to_email'], 'email'],
            [['to_email'], 'string', 'max' => 255],
            [['subject'], 'string', 'max' => 998],
            [['text_body', 'html_body', 'last_error'], 'string'],
            [['status'], 'in', 'range' => ['pending', 'processing', 'sent', 'failed']],
            [['attempts'], 'integer', 'min' => 0],
            [['next_attempt_at', 'sent_at'], 'safe'],
        ];
    }
}
