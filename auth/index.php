<?php
/**
 * Стандартные ссылки Bitrix на /auth/ (письмо «Запрос на смену пароля»
 * USER_PASS_REQUEST: /auth/index.php?change_password=yes&USER_CHECKWORD=…&
 * USER_LOGIN=…) — на форму входа / смены пароля в профиле покупателя
 * (formaro:auth.form). Параметры адреса сохраняются.
 */
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
$query = (string)($_SERVER['QUERY_STRING'] ?? '');
LocalRedirect('/personal/profile/' . ($query !== '' ? '?' . $query : ''), true, '301 Moved Permanently');
