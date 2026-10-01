<?php
CModule::IncludeModule("form");
CModule::IncludeModule("catalog");
CModule::IncludeModule("sale");

/**
 * обёртка для print_r() и var_dump()
 * @param $val - значение
 * @param string $name - заголовок
 * @param bool $mode - использовать var_dump() или print_r()
 * @param bool $die - использовать die() после вывода
 */
function print_p($val, $name = 'Содержимое переменной', $mode = false, $die = false)
{
    global $USER;
    if ($USER->IsAdmin()) {
        echo '<pre>' . (!empty($name) ? $name . ': ' : '');
        if ($mode) {
            var_dump($val);
        } else {
            print_r($val);
        }
        echo '</pre>';
        if ($die) die;
    }
}

/**
 * E-mail пользователя — одновременно его логин: меняются только парой. При
 * создании и изменении пользователя (личный кабинет покупателя, вкладка
 * «Авторизация» кабинета партнёра, регистрация, админка) LOGIN ставится
 * равным EMAIL, если тот передан и не пуст.
 */
$formaroSyncLoginWithEmail = static function (&$fields) {
    $email = trim((string)($fields['EMAIL'] ?? ''));
    if ($email !== '') {
        $fields['LOGIN'] = $email;
    }

    return true;
};
\Bitrix\Main\EventManager::getInstance()->addEventHandlerCompatible('main', 'OnBeforeUserAdd', $formaroSyncLoginWithEmail);
\Bitrix\Main\EventManager::getInstance()->addEventHandlerCompatible('main', 'OnBeforeUserUpdate', $formaroSyncLoginWithEmail);
