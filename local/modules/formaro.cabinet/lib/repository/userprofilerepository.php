<?php

namespace Formaro\Cabinet\Repository;

use CUser;

/**
 * Данные авторизации — поля пользователя Bitrix (b_user), не элемента
 * инфоблока cabinet_partners. Отдельно от "Контактных данных" на вкладке
 * "Основное" (PartnerRepository::$contacts — витринные контакты компании,
 * свойства элемента-партнёра): здесь — данные для входа в кабинет самого
 * пользователя (вкладка "Авторизация", см. profile.php).
 *
 * EMAIL — он же логин: меняются парой (обработчик OnBeforeUserUpdate в
 * init.php ставит LOGIN = EMAIL), поэтому смена почты на этой вкладке
 * меняет и логин для входа.
 */
class UserProfileRepository
{
    /** E-mail — он же логин: занят, если это чужой e-mail или чужой логин. */
    public static function isEmailTaken(string $email, int $userId): bool
    {
        return (bool)\Bitrix\Main\UserTable::getList([
            'select' => ['ID'],
            'filter' => ['!=ID' => $userId, ['LOGIC' => 'OR', '=EMAIL' => $email, '=LOGIN' => $email]],
            'limit' => 1,
        ])->fetch();
    }

    public function get(int $userId): array
    {
        $user = CUser::GetByID($userId)->Fetch();
        if (!$user) {
            return ['name' => '', 'last_name' => '', 'email' => '', 'work_phone' => '', 'work_position' => ''];
        }

        return [
            'name' => $user['NAME'] ?? '',
            'last_name' => $user['LAST_NAME'] ?? '',
            'email' => $user['EMAIL'] ?? '',
            'work_phone' => $user['WORK_PHONE'] ?? '',
            'work_position' => $user['WORK_POSITION'] ?? '',
        ];
    }

    /**
     * @param array $payload name/last_name/email/work_phone/work_position
     * @throws \Exception если email занят другим пользователем или сохранить не удалось
     */
    public function save(int $userId, array $payload): array
    {
        $email = trim((string)($payload['email'] ?? ''));
        if ($email !== '' && self::isEmailTaken($email, $userId)) {
            throw new \RuntimeException('Этот e-mail уже используется другим пользователем');
        }

        $fields = [
            'NAME' => (string)($payload['name'] ?? ''),
            'LAST_NAME' => (string)($payload['last_name'] ?? ''),
            'WORK_PHONE' => (string)($payload['work_phone'] ?? ''),
            'WORK_POSITION' => (string)($payload['work_position'] ?? ''),
        ];
        if ($email !== '') {
            $fields['EMAIL'] = $email;
        }

        $user = new CUser();
        $ok = $user->Update($userId, $fields);
        if (!$ok) {
            throw new \RuntimeException($user->LAST_ERROR ?: 'Не удалось сохранить данные авторизации');
        }

        return $this->get($userId);
    }
}
