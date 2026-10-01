<?php

namespace Formaro\Cabinet\Service;

use CUser;
use Formaro\Cabinet\Repository\UserProfileRepository;

/**
 * Вход и регистрация покупателя, восстановление и смена пароля
 * (formaro:auth.form, /local/ajax/auth.php). Логин — e-mail (обработчик
 * в init.php держит LOGIN = EMAIL).
 *
 * Восстановление — стандартный CUser::SendPassword(): письмо
 * USER_PASS_REQUEST со ссылкой /auth/index.php?change_password=yes…,
 * а /auth/index.php переадресует на форму смены пароля в профиле
 * (/personal/profile/?change_password=yes…).
 */
class BuyerAuthService
{
    /** @throws \RuntimeException */
    public static function login(string $email, string $password, bool $remember): void
    {
        global $USER;
        $email = trim($email);
        if ($email === '' || $password === '') {
            throw new \RuntimeException('Укажите e-mail и пароль');
        }
        // У старых учётных записей логин мог не совпадать с e-mail — ищем по обоим.
        $row = \Bitrix\Main\UserTable::getList([
            'select' => ['LOGIN'],
            'filter' => ['LOGIC' => 'OR', '=LOGIN' => $email, '=EMAIL' => $email],
            'limit' => 1,
        ])->fetch();
        $result = $USER->Login($row ? (string)$row['LOGIN'] : $email, $password, $remember ? 'Y' : 'N');
        if ($result !== true) {
            throw new \RuntimeException('Неверный e-mail или пароль');
        }
    }

    /**
     * @param array $data last_name, name, email, phone, password, password_repeat, consent
     * @throws \RuntimeException
     */
    public static function register(array $data): void
    {
        global $USER;
        if ($USER->IsAuthorized()) {
            throw new \RuntimeException('Вы уже вошли на сайт');
        }
        $field = static fn(string $key) => trim((string)($data[$key] ?? ''));
        $email = $field('email');
        $phone = $field('phone');
        $password = (string)($data['password'] ?? '');
        if ($field('name') === '') {
            throw new \RuntimeException('Укажите имя');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Проверьте e-mail');
        }
        if ($phone !== '' && !preg_match('/^\+?[\d\s\-()]{7,20}$/', $phone)) {
            throw new \RuntimeException('Проверьте телефон');
        }
        if (strlen($password) < 6) {
            throw new \RuntimeException('Пароль — не короче 6 символов');
        }
        if ($password !== (string)($data['password_repeat'] ?? '')) {
            throw new \RuntimeException('Пароли не совпадают');
        }
        if (empty($data['consent'])) {
            throw new \RuntimeException('Нужно согласие на обработку персональных данных');
        }
        if (UserProfileRepository::isEmailTaken($email, 0)) {
            throw new \RuntimeException('Этот e-mail уже зарегистрирован — войдите или восстановите пароль');
        }

        $groups = array_filter(array_map('intval', explode(',', (string)\COption::GetOptionString('main', 'new_user_registration_def_group', ''))));
        $user = new CUser();
        $id = (int)$user->Add([
            'LOGIN' => $email,
            'EMAIL' => $email,
            'NAME' => $field('name'),
            'LAST_NAME' => $field('last_name'),
            'PERSONAL_PHONE' => $phone,
            'PASSWORD' => $password,
            'CONFIRM_PASSWORD' => $password,
            'ACTIVE' => 'Y',
            'GROUP_ID' => $groups ?: [2],
            'LID' => SITE_ID,
        ]);
        if (!$id) {
            throw new \RuntimeException(strip_tags((string)$user->LAST_ERROR) ?: 'Не удалось зарегистрироваться');
        }
        // Письмо о регистрации (NEW_USER) — как при стандартной регистрации.
        \CEvent::Send('NEW_USER', SITE_ID, [
            'USER_ID' => $id, 'LOGIN' => $email, 'EMAIL' => $email,
            'NAME' => $field('name'), 'LAST_NAME' => $field('last_name'),
            'USER_IP' => $_SERVER['REMOTE_ADDR'] ?? '', 'USER_HOST' => '',
        ]);
        $USER->Authorize($id);
    }

    /** Ответ одинаковый, найден e-mail или нет — не раскрываем, кто зарегистрирован. */
    public static function forgot(string $email): void
    {
        global $USER;
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Проверьте e-mail');
        }
        $row = \Bitrix\Main\UserTable::getList([
            'select' => ['LOGIN'],
            'filter' => ['LOGIC' => 'OR', '=LOGIN' => $email, '=EMAIL' => $email],
            'limit' => 1,
        ])->fetch();
        if ($row) {
            $USER->SendPassword((string)$row['LOGIN'], $email, SITE_ID);
        }
    }

    /** @throws \RuntimeException */
    public static function changePassword(string $login, string $checkword, string $password, string $repeat): void
    {
        global $USER;
        if ($password !== $repeat) {
            throw new \RuntimeException('Пароли не совпадают');
        }
        if (strlen($password) < 6) {
            throw new \RuntimeException('Пароль — не короче 6 символов');
        }
        $result = $USER->ChangePassword($login, $checkword, $password, $repeat, SITE_ID);
        if (($result['TYPE'] ?? '') !== 'OK') {
            $message = trim(strip_tags(str_replace('<br>', ' ', (string)($result['MESSAGE'] ?? ''))));
            throw new \RuntimeException($message ?: 'Ссылка устарела — запросите восстановление пароля ещё раз');
        }
    }
}
