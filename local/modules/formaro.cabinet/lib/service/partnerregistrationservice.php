<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Loader;
use CIBlock;
use CIBlockElement;
use CUser;
use Formaro\Cabinet\Repository\UserProfileRepository;
use Formaro\Cabinet\Security\PartnerContext;

/**
 * Регистрация партнёра с сайта («Стать партнером», форма
 * formaro:partner.register, /local/ajax/partner_register.php).
 *
 * Создаёт партнёра — элемент инфоблока cabinet_partners: неактивный (на
 * витрине /partners/ не виден) и без статуса проверки («Не проверен» в
 * кабинете) — партнёр дозаполняет профиль в кабинете и отправляет на
 * проверку, после неё маркетплейс активирует его. Пользователь получает
 * доступ в кабинет: группа CABINET_PARTNERS + UF_CABINET_PARTNER_ID.
 *
 * Не авторизован — register(): создаётся пользователь (логин = e-mail,
 * телефон — рабочий, как у партнёров) и сразу входит. Авторизован
 * (покупатель) — createFromBuyerProfile(): без формы, по кнопке «Создать
 * кабинет партнёра», данные — из профиля покупателя, кабинет привязывается
 * к его учётной записи. Уже партнёр — ошибка. Маркетплейсу — уведомление в
 * админке.
 */
class PartnerRegistrationService
{
    private const IBLOCK_CODE = 'cabinet_partners';
    private const IBLOCK_TYPE = 'marketplace';
    private const GROUP_CODE = 'CABINET_PARTNERS';

    /**
     * @param array $data company, inn, last_name, name, phone, email, password, password_repeat, consent
     * @return int ID партнёра
     * @throws \RuntimeException понятная пользователю ошибка
     */
    public static function register(array $data): int
    {
        global $USER;
        Loader::includeModule('iblock');

        $field = static fn(string $key) => trim((string)($data[$key] ?? ''));
        // Вошедшему — кнопка «Создать кабинет партнёра» (createFromBuyerProfile()).
        if (is_object($USER) && $USER->IsAuthorized()) {
            throw new \RuntimeException(PartnerContext::hasOwnPartner()
                ? 'Вы уже партнёр — войдите в кабинет партнёра'
                : 'Вы вошли на сайт — создайте кабинет партнёра кнопкой на этой странице');
        }

        $company = $field('company');
        $inn = preg_replace('/\s+/', '', $field('inn'));
        $email = $field('email');
        $phone = $field('phone');
        if ($company === '') {
            throw new \RuntimeException('Укажите название организации или ИП');
        }
        if (!ctype_digit($inn) || !in_array(strlen($inn), [10, 12], true)) {
            throw new \RuntimeException('ИНН — 10 или 12 цифр');
        }
        if ($field('name') === '' || $field('last_name') === '') {
            throw new \RuntimeException('Укажите фамилию и имя контактного лица');
        }
        if (!preg_match('/^\+?[\d\s\-()]{7,20}$/', $phone)) {
            throw new \RuntimeException('Проверьте телефон');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Проверьте e-mail');
        }
        if (empty($data['consent'])) {
            throw new \RuntimeException('Нужно согласие на обработку персональных данных');
        }
        if (UserProfileRepository::isEmailTaken($email, 0)) {
            throw new \RuntimeException('Этот e-mail уже зарегистрирован — войдите и создайте кабинет партнёра из своей учётной записи');
        }
        if (strlen((string)($data['password'] ?? '')) < 6) {
            throw new \RuntimeException('Пароль — не короче 6 символов');
        }
        if ((string)$data['password'] !== (string)($data['password_repeat'] ?? '')) {
            throw new \RuntimeException('Пароли не совпадают');
        }

        $iblockId = self::iblockId();
        $groupId = self::groupId();
        if (!$iblockId || !$groupId) {
            throw new \RuntimeException('Регистрация партнёров временно недоступна');
        }

        $contactPerson = $field('last_name') . ' ' . $field('name');
        $partnerId = self::createPartner($iblockId, $company, [
            'LEGAL_INN' => $inn,
            'CONTACT_PHONE' => $phone,
            'CONTACT_EMAIL' => $email,
            'CONTACT_PERSON' => $contactPerson,
        ]);

        try {
            $USER->Authorize(self::createUser($data, $partnerId, $groupId));
        } catch (\Throwable $e) {
            CIBlockElement::Delete($partnerId);
            throw $e;
        }
        PartnerContext::reset();

        self::notifyMarketplace($iblockId, $partnerId, $company, 'ИНН ' . $inn . ', ' . $contactPerson . ', ' . $phone . ', ' . $email);

        return $partnerId;
    }

    /**
     * Кабинет партнёра для вошедшего покупателя по кнопке «Создать кабинет
     * партнёра» — без формы: в данные партнёра переносится профиль
     * покупателя (название и юридические реквизиты UF_BUYER_*, ФИО, телефон,
     * e-mail). Чего нет — партнёр заполнит в кабинете.
     *
     * @return int ID партнёра
     * @throws \RuntimeException понятная пользователю ошибка
     */
    public static function createFromBuyerProfile(): int
    {
        global $USER;
        Loader::includeModule('iblock');
        if (!is_object($USER) || !$USER->IsAuthorized()) {
            throw new \RuntimeException('Войдите, чтобы создать кабинет партнёра');
        }
        if (PartnerContext::hasOwnPartner()) {
            throw new \RuntimeException('Кабинет партнёра уже создан');
        }
        $iblockId = self::iblockId();
        $groupId = self::groupId();
        if (!$iblockId || !$groupId) {
            throw new \RuntimeException('Создание кабинета партнёра временно недоступно');
        }

        $userId = (int)$USER->GetID();
        $profile = self::buyerProfile($userId);
        $person = trim($profile['last_name'] . ' ' . $profile['name'] . ' ' . $profile['second_name']);
        $company = $profile['company'] !== '' ? $profile['company'] : ($person !== '' ? $person : $profile['email']);

        $partnerId = self::createPartner($iblockId, $company, [
            'LEGAL_INN' => $profile['inn'],
            'LEGAL_OGRN' => $profile['ogrn'],
            'LEGAL_ADDRESS' => $profile['legal_address'],
            'LEGAL_BANK_NAME' => $profile['bank_name'],
            'LEGAL_BIK' => $profile['bik'],
            'LEGAL_ACCOUNT' => $profile['account'],
            'LEGAL_CORR_ACCOUNT' => $profile['corr_account'],
            'LEGAL_CEO_NAME' => $profile['ceo_name'],
            'CONTACT_PHONE' => $profile['phone'],
            'CONTACT_EMAIL' => $profile['email'],
            'CONTACT_PERSON' => $person,
        ]);
        try {
            self::attachUser($userId, $partnerId, $groupId);
            // У партнёра телефон в профиле — рабочий (BuyerProfileRepository).
            if ($profile['work_phone'] === '' && $profile['phone'] !== '') {
                (new CUser())->Update($userId, ['WORK_PHONE' => $profile['phone']]);
            }
        } catch (\Throwable $e) {
            CIBlockElement::Delete($partnerId);
            throw $e;
        }
        PartnerContext::reset();

        self::notifyMarketplace($iblockId, $partnerId, $company, 'создан из профиля покупателя ' . $profile['email'] . ($profile['inn'] !== '' ? ', ИНН ' . $profile['inn'] : ''));

        return $partnerId;
    }

    /**
     * Профиль покупателя для переноса в партнёра (и для показа «что
     * перенесётся» на странице).
     */
    public static function buyerProfile(int $userId): array
    {
        $fields = ['UF_BUYER_COMPANY', 'UF_BUYER_INN', 'UF_BUYER_OGRN', 'UF_BUYER_ADDRESS', 'UF_BUYER_BANK', 'UF_BUYER_BIK', 'UF_BUYER_ACCOUNT', 'UF_BUYER_CORR_ACCOUNT', 'UF_BUYER_CEO'];
        $user = CUser::GetList($by = 'id', $order = 'asc', ['ID' => $userId], ['SELECT' => $fields])->Fetch() ?: [];
        $value = static fn(string $key) => trim((string)($user[$key] ?? ''));

        return [
            'last_name' => $value('LAST_NAME'),
            'name' => $value('NAME'),
            'second_name' => $value('SECOND_NAME'),
            'email' => $value('EMAIL'),
            'phone' => $value('PERSONAL_PHONE') !== '' ? $value('PERSONAL_PHONE') : $value('WORK_PHONE'),
            'work_phone' => $value('WORK_PHONE'),
            'company' => $value('UF_BUYER_COMPANY'),
            'inn' => $value('UF_BUYER_INN'),
            'ogrn' => $value('UF_BUYER_OGRN'),
            'legal_address' => $value('UF_BUYER_ADDRESS'),
            'bank_name' => $value('UF_BUYER_BANK'),
            'bik' => $value('UF_BUYER_BIK'),
            'account' => $value('UF_BUYER_ACCOUNT'),
            'corr_account' => $value('UF_BUYER_CORR_ACCOUNT'),
            'ceo_name' => $value('UF_BUYER_CEO'),
        ];
    }

    /** Неактивный партнёр без статуса проверки. @return int ID */
    private static function createPartner(int $iblockId, string $name, array $props): int
    {
        $el = new CIBlockElement();
        $partnerId = (int)$el->Add([
            'IBLOCK_ID' => $iblockId,
            'NAME' => $name,
            'CODE' => \CUtil::translit($name, 'ru', ['replace_space' => '-', 'replace_other' => '-']) . '-' . substr(md5(uniqid('', true)), 0, 6),
            'ACTIVE' => 'N',
            'PROPERTY_VALUES' => array_filter($props, static fn($v) => $v !== ''),
        ]);
        if (!$partnerId) {
            throw new \RuntimeException(strip_tags((string)$el->LAST_ERROR) ?: 'Не удалось сохранить заявку');
        }

        return $partnerId;
    }

    private static function notifyMarketplace(int $iblockId, int $partnerId, string $name, string $details): void
    {
        \CAdminNotify::Add([
            'MESSAGE' => 'Новый партнёр «' . $name . '» (' . $details . ') зарегистрировался на сайте. '
                . 'Партнёр неактивен — проверьте данные и <a href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $iblockId . '&type=' . self::IBLOCK_TYPE . '&ID=' . $partnerId . '&lang=ru">активируйте</a>.',
            'TAG' => 'formaro_partner_registration_' . $partnerId,
            'MODULE_ID' => 'main',
            'ENABLE_CLOSE' => 'Y',
        ]);
    }

    private static function createUser(array $data, int $partnerId, int $groupId): int
    {
        $user = new CUser();
        $groups = array_unique(array_merge(self::defaultGroups(), [$groupId]));
        $id = (int)$user->Add([
            'LOGIN' => trim((string)$data['email']),
            'EMAIL' => trim((string)$data['email']),
            'NAME' => trim((string)$data['name']),
            'LAST_NAME' => trim((string)$data['last_name']),
            'WORK_PHONE' => trim((string)$data['phone']),
            'WORK_COMPANY' => trim((string)$data['company']),
            'PASSWORD' => (string)$data['password'],
            'CONFIRM_PASSWORD' => (string)$data['password_repeat'],
            'ACTIVE' => 'Y',
            'GROUP_ID' => $groups,
            'UF_CABINET_PARTNER_ID' => $partnerId,
        ]);
        if (!$id) {
            throw new \RuntimeException(strip_tags((string)$user->LAST_ERROR) ?: 'Не удалось создать учётную запись');
        }

        return $id;
    }

    private static function attachUser(int $userId, int $partnerId, int $groupId): void
    {
        $user = new CUser();
        $groups = array_unique(array_merge(CUser::GetUserGroup($userId), [$groupId]));
        if (!$user->Update($userId, ['UF_CABINET_PARTNER_ID' => $partnerId, 'GROUP_ID' => $groups])) {
            throw new \RuntimeException(strip_tags((string)$user->LAST_ERROR) ?: 'Не удалось привязать кабинет');
        }
    }

    /** Группы новых пользователей по настройкам главного модуля. */
    private static function defaultGroups(): array
    {
        $groups = array_filter(array_map('intval', explode(',', (string)\COption::GetOptionString('main', 'new_user_registration_def_group', ''))));

        return $groups ?: [2];
    }

    private static function iblockId(): int
    {
        $row = CIBlock::GetList([], ['=CODE' => self::IBLOCK_CODE, 'TYPE' => self::IBLOCK_TYPE, 'CHECK_PERMISSIONS' => 'N'])->Fetch();

        return $row ? (int)$row['ID'] : 0;
    }

    private static function groupId(): int
    {
        $group = \CGroup::GetList($by = 'id', $order = 'asc', ['STRING_ID' => self::GROUP_CODE])->Fetch();

        return $group ? (int)$group['ID'] : 0;
    }
}
