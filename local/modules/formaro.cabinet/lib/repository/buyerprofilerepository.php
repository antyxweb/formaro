<?php

namespace Formaro\Cabinet\Repository;

use CUser;
use Formaro\Cabinet\Security\PartnerContext;

/**
 * «Ваш профиль» покупателя (/personal/profile/, formaro:personal.profile).
 *
 * Контакты — поля пользователя Bitrix (b_user): фамилия, имя, отчество,
 * e-mail, телефон. E-mail — он же логин: меняются парой (обработчик
 * OnBeforeUserUpdate в init.php ставит LOGIN = EMAIL). Те же поля правит вкладка «Авторизация» кабинета
 * партнёра (UserProfileRepository), поэтому у партнёра телефон — рабочий
 * (WORK_PHONE, как в кабинете), у остальных — PERSONAL_PHONE.
 *
 * Юридические реквизиты покупателя — UF_BUYER_* пользователя (миграция
 * Version20261001120001). Пользователь может быть и партнёром, и
 * покупателем: у партнёра кабинета реквизиты — его компании из кабинета
 * (PartnerRepository), только для чтения — они меняются в кабинете и
 * проходят проверку маркетплейса.
 */
class BuyerProfileRepository
{
    /** Ключ формы => поле пользователя. */
    public const LEGAL_FIELDS = [
        'company' => 'UF_BUYER_COMPANY',
        'inn' => 'UF_BUYER_INN',
        'kpp' => 'UF_BUYER_KPP',
        'ogrn' => 'UF_BUYER_OGRN',
        'legal_address' => 'UF_BUYER_ADDRESS',
        'bank_name' => 'UF_BUYER_BANK',
        'bik' => 'UF_BUYER_BIK',
        'account' => 'UF_BUYER_ACCOUNT',
        'corr_account' => 'UF_BUYER_CORR_ACCOUNT',
        'ceo_name' => 'UF_BUYER_CEO',
    ];

    /** Реквизиты: только цифры нужной длины (если заполнено). */
    private const DIGITS = [
        'inn' => [[10, 12], 'ИНН — 10 или 12 цифр'],
        'kpp' => [[9], 'КПП — 9 цифр'],
        'ogrn' => [[13, 15], 'ОГРН — 13 цифр (ОГРНИП — 15)'],
        'bik' => [[9], 'БИК — 9 цифр'],
        'account' => [[20], 'Расчётный счёт — 20 цифр'],
        'corr_account' => [[20], 'Корр. счёт — 20 цифр'],
    ];

    /**
     * @return array{contacts: array, legal: array, partner: ?array}
     *   partner — у партнёра кабинета: {name, url} и legal из кабинета.
     */
    public function get(int $userId): array
    {
        $user = $this->user($userId);
        $partnerId = $this->partnerId($userId);

        $contacts = [
            'last_name' => (string)($user['LAST_NAME'] ?? ''),
            'name' => (string)($user['NAME'] ?? ''),
            'second_name' => (string)($user['SECOND_NAME'] ?? ''),
            'email' => (string)($user['EMAIL'] ?? ''),
            'phone' => (string)($user[$partnerId ? 'WORK_PHONE' : 'PERSONAL_PHONE'] ?? ''),
        ];

        if ($partnerId) {
            $partner = (new PartnerRepository())->get($partnerId) ?? [];
            $legal = array_merge(array_fill_keys(array_keys(self::LEGAL_FIELDS), ''), (array)($partner['legal'] ?? []));
            $legal['company'] = (string)($partner['name_full'] ?? '');

            return ['contacts' => $contacts, 'legal' => $legal, 'partner' => ['id' => $partnerId, 'url' => '/cabinet/profile/']];
        }

        $legal = [];
        foreach (self::LEGAL_FIELDS as $key => $field) {
            $legal[$key] = (string)($user[$field] ?? '');
        }

        return ['contacts' => $contacts, 'legal' => $legal, 'partner' => null];
    }

    /**
     * @throws \RuntimeException понятная пользователю ошибка
     */
    public function saveContacts(int $userId, array $data): array
    {
        $field = static fn(string $key) => trim((string)($data[$key] ?? ''));
        $email = $field('email');
        $phone = $field('phone');
        if ($field('name') === '') {
            throw new \RuntimeException('Укажите имя');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Проверьте e-mail');
        }
        if ($phone !== '' && !preg_match('/^\+?[\d\s\-()]{7,20}$/', $phone)) {
            throw new \RuntimeException('Проверьте телефон');
        }
        if (UserProfileRepository::isEmailTaken($email, $userId)) {
            throw new \RuntimeException('Этот e-mail уже используется другим пользователем');
        }

        $this->update($userId, [
            'LAST_NAME' => $field('last_name'),
            'NAME' => $field('name'),
            'SECOND_NAME' => $field('second_name'),
            'EMAIL' => $email,
            $this->partnerId($userId) ? 'WORK_PHONE' : 'PERSONAL_PHONE' => $phone,
        ]);

        return $this->get($userId)['contacts'];
    }

    /**
     * Реквизиты покупателя. У партнёра — нельзя: реквизиты его компании
     * меняются в кабинете.
     *
     * @throws \RuntimeException понятная пользователю ошибка
     */
    public function saveLegal(int $userId, array $data): array
    {
        if ($this->partnerId($userId)) {
            throw new \RuntimeException('Реквизиты вашей компании меняются в кабинете партнёра');
        }
        $fields = [];
        foreach (self::LEGAL_FIELDS as $key => $field) {
            $value = mb_substr(trim((string)($data[$key] ?? '')), 0, 255);
            if (isset(self::DIGITS[$key]) && $value !== '') {
                $value = preg_replace('/\s+/', '', $value);
                [$lengths, $message] = self::DIGITS[$key];
                if (!ctype_digit($value) || !in_array(strlen($value), $lengths, true)) {
                    throw new \RuntimeException($message);
                }
            }
            $fields[$field] = $value;
        }
        $this->update($userId, $fields);

        return $this->get($userId)['legal'];
    }

    /**
     * Смена пароля — как в кабинете партнёра (profile.php): текущий
     * проверяется входом, новый — не короче 6 символов.
     *
     * @throws \RuntimeException понятная пользователю ошибка
     */
    public function changePassword(int $userId, string $current, string $new, string $repeat): void
    {
        global $USER, $APPLICATION;
        if ($new !== $repeat) {
            throw new \RuntimeException('Пароли не совпадают');
        }
        if (strlen($new) < 6) {
            throw new \RuntimeException('Новый пароль должен быть не короче 6 символов');
        }
        // Логин — из базы: e-mail (= логин) мог смениться в этой же сессии.
        $login = (string)(CUser::GetByID($userId)->Fetch()['LOGIN'] ?? '');
        if ((int)$USER->GetID() !== $userId || $USER->Login($login, $current, 'N') !== true) {
            throw new \RuntimeException('Текущий пароль указан неверно');
        }
        $user = new CUser();
        if (!$user->Update($userId, ['PASSWORD' => $new, 'CONFIRM_PASSWORD' => $repeat])) {
            $error = $user->LAST_ERROR ?: ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : '');
            throw new \RuntimeException(strip_tags($error) ?: 'Не удалось изменить пароль');
        }
    }

    private function update(int $userId, array $fields): void
    {
        $user = new CUser();
        if (!$user->Update($userId, $fields)) {
            throw new \RuntimeException(strip_tags((string)$user->LAST_ERROR) ?: 'Не удалось сохранить');
        }
    }

    private function user(int $userId): array
    {
        return CUser::GetList($by = 'id', $order = 'asc', ['ID' => $userId], ['SELECT' => array_values(self::LEGAL_FIELDS)])->Fetch() ?: [];
    }

    /** Партнёр кабинета (у текущего пользователя), 0 — обычный покупатель. */
    private function partnerId(int $userId): int
    {
        global $USER;
        if ((int)$USER->GetID() !== $userId) {
            return 0;
        }

        return PartnerContext::hasAccess() ? PartnerContext::getPartnerId() : 0;
    }
}
