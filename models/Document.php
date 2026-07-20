<?php

namespace app\models;

use yii\db\ActiveRecord;

class Document extends ActiveRecord
{
    public static function tableName(): string { return '{{%documents}}'; }
    public function rules(): array
    {
        return [
            [['workspace_id', 'title', 'created_by', 'updated_by'], 'required'],
            [['workspace_id', 'collection_id', 'parent_id', 'created_by', 'updated_by', 'version'], 'integer'],
            [['title'], 'string', 'max' => 500],
            [['content', 'content_json', 'content_text'], 'string'],
            [['status'], 'in', 'range' => ['draft', 'published', 'archived']],
            [['permission_mode'], 'in', 'range' => ['inherit', 'workspace_read', 'workspace_edit', 'private']],
            [['is_template'], 'boolean'],
        ];
    }
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) return false;
        if ($this->content !== null && $this->content_text === null) {
            $this->content_text = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$this->content)) ?? '');
        }
        return true;
    }
    public function getCollection() { return $this->hasOne(Collection::class, ['id' => 'collection_id']); }
    public function getCreator() { return $this->hasOne(User::class, ['id' => 'created_by']); }
    public function getUpdater() { return $this->hasOne(User::class, ['id' => 'updated_by']); }
    public function getRevisions() { return $this->hasMany(DocumentRevision::class, ['document_id' => 'id'])->orderBy(['version' => SORT_DESC]); }
    public function getComments() { return $this->hasMany(Comment::class, ['document_id' => 'id'])->orderBy(['created_at' => SORT_ASC]); }
}
