<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_MEMBER = 'member';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';

    public ?string $newPassword = null;

    public static function tableName(): string { return '{{%users}}'; }

    public function rules(): array
    {
        return [
            [['workspace_id', 'last_name', 'first_name', 'middle_name', 'email', 'role', 'status'], 'required'],
            [['workspace_id'], 'integer'],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['last_name', 'first_name', 'middle_name'], 'string', 'max' => 100],
            [['email'], 'string', 'max' => 255],
            [['role'], 'in', 'range' => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_MEMBER]],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DISABLED]],
            [['color'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/'],
            [['newPassword'], 'string', 'min' => 8, 'max' => 200],
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) return false;
        $this->email = mb_strtolower(trim($this->email));
        if ($this->newPassword) {
            $this->password_hash = Yii::$app->security->generatePasswordHash($this->newPassword);
            $this->auth_key = Yii::$app->security->generateRandomString(64);
        }
        if (!$this->color) {
            $colors = ['#2563EB', '#7C3AED', '#DB2777', '#DC2626', '#059669', '#D97706'];
            $this->color = $colors[((int)$this->workspace_id + (int)($this->id ?? 0)) % count($colors)];
        }
        return true;
    }

    public function getFullName(): string
    {
        return trim(implode(' ', array_filter([$this->last_name, $this->first_name, $this->middle_name])));
    }

    public function isAdmin(): bool { return $this->role === self::ROLE_ADMIN; }
    public function canManage(): bool { return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MANAGER], true); }

    public static function findIdentity($id): ?self { return self::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]); }
    public static function findIdentityByAccessToken($token, $type = null): ?IdentityInterface { return null; }
    public function getId(): int|string { return $this->id; }
    public function getAuthKey(): string { return (string)$this->auth_key; }
    public function validateAuthKey($authKey): bool { return hash_equals((string)$this->auth_key, (string)$authKey); }
    public function validatePassword(string $password): bool { return Yii::$app->security->validatePassword($password, (string)$this->password_hash); }
}
