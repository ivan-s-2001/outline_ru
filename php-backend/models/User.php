<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

final class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        return '{{%users}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'email', 'last_name', 'first_name', 'middle_name'], 'required'],
            [['workspace_id'], 'integer'],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['last_name', 'first_name', 'middle_name', 'email', 'role', 'status', 'color'], 'string', 'max' => 255],
        ];
    }

    public static function findIdentity($id): ?self
    {
        return self::findOne(['id' => $id, 'status' => 'active']);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        return null;
    }

    public static function findByEmail(string $email): ?self
    {
        return self::findOne(['email' => mb_strtolower(trim($email)), 'status' => 'active']);
    }

    public function getId(): int
    {
        return (int)$this->id;
    }

    public function getAuthKey(): string
    {
        return (string)$this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return hash_equals((string)$this->auth_key, (string)$authKey);
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, (string)$this->password_hash);
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function getFullName(): string
    {
        return trim(implode(' ', array_filter([
            $this->last_name,
            $this->first_name,
            $this->middle_name,
        ], static fn ($value) => $value !== null && $value !== '')));
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'owner'], true);
    }
}
