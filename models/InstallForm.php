<?php

namespace app\models;

use Yii;
use yii\base\Model;

class InstallForm extends Model
{
    public string $workspaceName = '';
    public string $lastName = '';
    public string $firstName = '';
    public string $middleName = '';
    public string $email = '';
    public string $password = '';
    public string $passwordRepeat = '';

    public function rules(): array
    {
        return [
            [['workspaceName', 'lastName', 'firstName', 'middleName', 'email', 'password', 'passwordRepeat'], 'required'],
            [['workspaceName'], 'string', 'max' => 255],
            [['lastName', 'firstName', 'middleName'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['password'], 'string', 'min' => 8, 'max' => 200],
            [['passwordRepeat'], 'compare', 'compareAttribute' => 'password', 'message' => 'Пароли не совпадают.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'workspaceName' => 'Название организации',
            'lastName' => 'Фамилия',
            'firstName' => 'Имя',
            'middleName' => 'Отчество',
            'email' => 'Электронная почта',
            'password' => 'Пароль',
            'passwordRepeat' => 'Повторите пароль',
        ];
    }

    public function install(): ?User
    {
        if (!$this->validate() || User::find()->exists()) return null;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $workspace = new Workspace([
                'name' => trim($this->workspaceName),
                'slug' => strtolower(Yii::$app->security->generateRandomString(12)),
            ]);
            if (!$workspace->save()) {
                $this->addErrors($workspace->getErrors());
                $transaction->rollBack();
                return null;
            }
            $user = new User([
                'workspace_id' => $workspace->id,
                'last_name' => trim($this->lastName),
                'first_name' => trim($this->firstName),
                'middle_name' => trim($this->middleName),
                'email' => mb_strtolower(trim($this->email)),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'newPassword' => $this->password,
            ]);
            if (!$user->save()) {
                $this->addErrors($user->getErrors());
                $transaction->rollBack();
                return null;
            }
            $transaction->commit();
            return $user;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
