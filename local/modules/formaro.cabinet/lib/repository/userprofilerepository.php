<?php

namespace Formaro\Cabinet\Repository;

use CUser;

/**
 * Данные авторизации — поля пользователя Bitrix (b_user), не элемента
 * инфоблока cabinet_partners. Раньше контактные данные (телефон/email/
 * контактное лицо/должность) жили как свойства партнёра-элемента, но это
 * смешивало "витринные" данные компании с данными для входа в кабинет —
 * перенесены сюда, на вкладку "Авторизация" (см. profile.php).
 *
 * EMAIL здесь — тот же email, по которому происходит вход (см.
 * login.php — резолвит LOGIN по точному совпадению EMAIL), поэтому смена
 * почты на этой вкладке одновременно меняет логин для входа.
 */
class UserProfileRepository
{
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
        if ($email !== '') {
            $existing = CUser::GetList($by = 'id', $order = 'asc', ['=EMAIL' => $email], ['SELECT' => ['ID']])->Fetch();
            if ($existing && (int)$existing['ID'] !== $userId) {
                throw new \RuntimeException('Этот e-mail уже используется другим пользователем');
            }
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
