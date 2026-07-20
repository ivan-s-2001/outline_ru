<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class Favorite extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%favorites}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'document_id'], 'required'],
            [['workspace_id', 'user_id', 'document_id'], 'string', 'max' => 36],
            [['sort_order'], 'string', 'max' => 255],
        ];
    }

    public function getDocument(): ActiveQuery
    {
        return $this->hasOne(Document::class, ['id' => 'document_id']);
    }
}
