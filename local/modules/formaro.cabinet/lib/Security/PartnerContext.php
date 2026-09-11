<?php

namespace Formaro\Cabinet\Security;

use CUser;

/**
 * Резолвит "текущего партнёра" из авторизованного пользователя — замена
 * захардкоженной CURRENT_PARTNER_ID из cabinet-html/assets/js/common.js.
 * Партнёр = ELEMENT_ID элемента в инфоблоке cabinet_partners, на который
 * указывает UF_CABINET_PARTNER_ID текущего пользователя (поле заводится
 * миграцией Version20260911193006). Доступ в /cabinet/ требует ОБА условия:
 * членство в группе CABINET_PARTNERS И заполненное значение поля — так
 * администратор сайта (не партнёр) не проваливается в кабинет случайно,
 * даже если ему по ошибке проставят UF-поле, и наоборот.
 */
class PartnerContext
{
    private const GROUP_CODE = 'CABINET_PARTNERS';
    private const UF_FIELD = 'UF_CABINET_PARTNER_ID';

    private static ?int $partnerId = null;
    private static bool $resolved = false;

    public static function isAuthorized(): bool
    {
        global $USER;
        return $USER && $USER->IsAuthorized();
    }

    public static function hasAccess(): bool
    {
        if (!self::isAuthorized()) {
            return false;
        }

        global $USER;
        if (!in_array(self::getGroupId(), $USER->GetUserGroupArray(), false)) {
            return false;
        }

        return self::getPartnerId() > 0;
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

        self::$partnerId = (int)$value;

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
}
