<?php

namespace Formaro\Cabinet\Security;

/**
 * Доступ в кабинет администратора площадки (/admin/, formaro:cabinet.admin)
 * — только пользователям с ролью «Администратор» (группа администраторов
 * Битрикса, $USER->IsAdmin()). Сессия общая с сайтом: вошедший на сайт
 * администратор попадает в /admin/ без отдельного входа.
 */
final class AdminContext
{
    public static function hasAccess(): bool
    {
        global $USER;

        return $USER && $USER->IsAuthorized() && $USER->IsAdmin();
    }
}
