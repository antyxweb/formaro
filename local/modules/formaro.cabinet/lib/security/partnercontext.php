<?php

namespace Formaro\Cabinet\Security;

use CUser;

/**
 * Резолвит "текущего партнёра" из авторизованного пользователя — замена
 * захардкоженной CURRENT_PARTNER_ID из cabinet-html/assets/js/common.js.
 * Партнёр = ELEMENT_ID элемента в инфоблоке cabinet_partners, на который
 * указывает UF_CABINET_PARTNER_ID текущего пользователя (поле заводится
 * миграцией Version20260911193006). Обычному пользователю доступ в
 * /cabinet/ требует ОБА условия: членство в группе CABINET_PARTNERS И
 * заполненное значение поля.
 *
 * Сквозная авторизация для администратора сайта: $USER->IsAdmin() (реальные
 * админ-права, а не жёстко заданная группа) даёт доступ в кабинет без
 * отдельного логина на /cabinet/login/ — сессии общие, раз уже вошёл в
 * /bitrix/admin/, тот же $USER уже авторизован и для /cabinet/. Если у
 * самого администратора своего UF_CABINET_PARTNER_ID нет, подставляется
 * первый активный партнёр (см. resolveDefaultPartnerId()) — чтобы сразу
 * был рабочий контекст для проверки/поддержки кабинета, без завода
 * отдельной тестовой учётки на каждый раз.
 */
class PartnerContext
{
    private const GROUP_CODE = 'CABINET_PARTNERS';
    private const UF_FIELD = 'UF_CABINET_PARTNER_ID';
    private const PARTNERS_IBLOCK_CODE = 'cabinet_partners';
    private const PARTNERS_IBLOCK_TYPE = 'marketplace';

    private static ?int $partnerId = null;
    private static bool $resolved = false;

    public static function isAuthorized(): bool
    {
        global $USER;
        return $USER && $USER->IsAuthorized();
    }

    /** Сбрасывает кэш этого запроса — нужно вызвать сразу после успешного
     *  $USER->Login()/Logout() в рамках одного PHP-запроса (например, в
     *  login.php), иначе hasAccess()/getPartnerId(), вызванные ДО логина
     *  (см. guardAccess() в class.php — он проверяет доступ ещё для
     *  неавторизованного запроса на /login/), отдадут закэшированный
     *  результат "не авторизован" и после успешного входа в этом же запросе. */
    public static function reset(): void
    {
        self::$partnerId = null;
        self::$resolved = false;
    }

    public static function hasAccess(): bool
    {
        if (!self::isAuthorized()) {
            return false;
        }

        global $USER;
        if (!$USER->IsAdmin() && !in_array(self::getGroupId(), $USER->GetUserGroupArray(), false)) {
            return false;
        }

        return self::getPartnerId() > 0;
    }

    /**
     * Пользователь сам привязан к партнёру (свой UF_CABINET_PARTNER_ID и
     * группа CABINET_PARTNERS; администратору группа не нужна). В отличие от
     * hasAccess() не учитывает подстановку партнёра по умолчанию для
     * администратора без привязки — для «уже партнёр?» (регистрация).
     */
    public static function hasOwnPartner(): bool
    {
        if (!self::isAuthorized()) {
            return false;
        }
        global $USER;
        $row = CUser::GetList($by = 'id', $order = 'asc', ['ID' => $USER->GetID()], ['SELECT' => [self::UF_FIELD]])->Fetch();
        if ((int)($row[self::UF_FIELD] ?? 0) <= 0) {
            return false;
        }

        return $USER->IsAdmin() || in_array(self::getGroupId(), $USER->GetUserGroupArray(), false);
    }

    /** @return int ELEMENT_ID в инфоблоке cabinet_partners, 0 — если не резолвится */
    public static function getPartnerId(): int
    {
        if (self::$resolved) {
            return self::$partnerId ?? 0;
        }
        self::$resolved = true;
        self::$partnerId = 0;

        global $USER;
        if (!$USER || !$USER->IsAuthorized()) {
            return 0;
        }

        $value = $USER->GetParam(self::UF_FIELD);
        if ($value === null || $value === false) {
            // GetParam кэширует не все UF на кастомных полях — подстрахуемся прямым запросом
            $userFields = CUser::GetList($by = 'id', $order = 'asc', ['ID' => $USER->GetID()], ['SELECT' => [self::UF_FIELD]])->Fetch();
            $value = $userFields[self::UF_FIELD] ?? null;
        }

        $partnerId = (int)$value;

        if ($partnerId <= 0 && $USER->IsAdmin()) {
            $partnerId = self::resolveDefaultPartnerId();
        }

        self::$partnerId = $partnerId;

        return self::$partnerId;
    }

    private static function getGroupId(): int
    {
        static $groupId = null;
        if ($groupId === null) {
            $group = \CGroup::GetList($by = 'id', $order = 'asc', ['STRING_ID' => self::GROUP_CODE])->Fetch();
            $groupId = $group ? (int)$group['ID'] : 0;
        }

        return $groupId;
    }

    /** Первый активный партнёр — рабочий контекст для администратора без
     *  собственной привязки к партнёру (см. докблок класса). */
    private static function resolveDefaultPartnerId(): int
    {
        \Bitrix\Main\Loader::includeModule('iblock');

        $iblock = \CIBlock::GetList([], [
            'CODE' => self::PARTNERS_IBLOCK_CODE,
            'TYPE' => self::PARTNERS_IBLOCK_TYPE,
            'CHECK_PERMISSIONS' => 'N',
        ])->Fetch();
        if (!$iblock) {
            return 0;
        }

        $el = \CIBlockElement::GetList(
            ['ID' => 'ASC'],
            ['IBLOCK_ID' => $iblock['ID'], 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID']
        )->Fetch();

        return $el ? (int)$el['ID'] : 0;
    }
}
