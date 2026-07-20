<?php

declare(strict_types=1);

namespace app\tests;

use app\models\forms\InstallForm;
use PHPUnit\Framework\TestCase;

final class InstallFormTest extends TestCase
{
    public function testValidAdministratorData(): void
    {
        $form = new InstallForm();
        $form->workspaceName = 'Тестовая команда';
        $form->login = 'admin.local';
        $form->email = 'admin@example.local';
        $form->fullName = 'Иванов Иван Иванович';
        $form->password = 'correct-password';
        $form->passwordRepeat = 'correct-password';

        self::assertTrue($form->validate(), json_encode($form->getErrors(), JSON_UNESCAPED_UNICODE));
        self::assertSame(['Иванов', 'Иван', 'Иванович'], $form->splitFullName());
    }

    public function testRejectsIncompleteFullName(): void
    {
        $form = new InstallForm();
        $form->workspaceName = 'Тестовая команда';
        $form->login = 'admin';
        $form->email = 'admin@example.local';
        $form->fullName = 'Иван Иванов';
        $form->password = 'correct-password';
        $form->passwordRepeat = 'correct-password';

        self::assertFalse($form->validate());
        self::assertArrayHasKey('fullName', $form->getErrors());
    }

    public function testRejectsUnsafeLoginAndShortPassword(): void
    {
        $form = new InstallForm();
        $form->workspaceName = 'Тестовая команда';
        $form->login = 'администратор';
        $form->email = 'admin@example.local';
        $form->fullName = 'Иванов Иван Иванович';
        $form->password = '123';
        $form->passwordRepeat = '123';

        self::assertFalse($form->validate());
        self::assertArrayHasKey('login', $form->getErrors());
        self::assertArrayHasKey('password', $form->getErrors());
    }
}
