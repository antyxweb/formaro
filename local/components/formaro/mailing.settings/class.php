<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\MailingService;

/**
 * «Управление рассылками» на сайте (MailingService): категории уведомлений
 * по типам событий и подписки на новости; сохранение — /local/ajax/mailing.php
 * (script.js шаблона).
 *
 * Чей адрес:
 *   - ссылка из письма (?u=…&t=…) — адрес из ссылки, вход не нужен
 *     (страница /subscriptions/);
 *   - иначе — e-mail вошедшего пользователя.
 *
 * Параметры:
 *   EMBED — блок внизу «Вашего профиля» (/personal/profile/): только
 *           уведомления покупателя. Уведомления кабинета партнёра
 *           настраиваются в самом кабинете, здесь только ссылка туда.
 * Данные личные — без кэша.
 */
class FormaroMailingSettingsComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['EMBED'] = ($arParams['EMBED'] ?? 'N') === 'Y' ? 'Y' : 'N';

        return $arParams;
    }

    public function executeComponent()
    {
        global $USER;
        $this->arResult = ['EMAIL' => '', 'LINK' => null, 'STATE' => null, 'PARTNER' => false, 'HIGHLIGHT' => '', 'INVALID_LINK' => false];

        if (!Loader::includeModule('formaro.cabinet')) {
            return;
        }

        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        $u = (string)$request->getQuery('u');
        $t = (string)$request->getQuery('t');
        $email = null;
        if ($this->arParams['EMBED'] !== 'Y' && ($u !== '' || $t !== '')) {
            $email = MailingService::emailFromLink($u, $t);
            if ($email !== null) {
                $this->arResult['LINK'] = ['u' => $u, 't' => $t];
            } else {
                $this->arResult['INVALID_LINK'] = true;
            }
        }
        if ($email === null && is_object($USER) && $USER->IsAuthorized()) {
            $email = MailingService::normalize((string)$USER->GetEmail());
        }

        if ($email !== null && $email !== '') {
            $audiences = MailingService::audiencesFor($email);
            $this->arResult['EMAIL'] = $email;
            $this->arResult['PARTNER'] = in_array('partner', $audiences, true);
            $this->arResult['STATE'] = MailingService::state(
                $email,
                $this->arParams['EMBED'] === 'Y' ? array_intersect($audiences, ['buyer']) : $audiences
            );
            $this->arResult['HIGHLIGHT'] = (string)$request->getQuery('c');
        }

        $this->includeComponentTemplate();
    }
}
