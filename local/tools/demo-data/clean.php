<?php
/**
 * Удаляет демо-данные seed.php: партнёра «Спецодежда Плюс» со всем, что к
 * нему привязано (категории, товары, скидки, купоны, новости, заказы,
 * диалоги, обращения, операции, уведомления), демо-пользователей и
 * уведомления администраторов о них.
 *
 * Запуск (в контейнере php): php /opt/www/local/tools/demo-data/clean.php
 */

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../..');
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\HlblockEntityFactory;
use Formaro\Cabinet\Upload\FileUploader;

Loader::includeModule('iblock');
Loader::includeModule('formaro.cabinet');

require __DIR__ . '/config.php';

/** @var array $DEMO */
$partnerIblock = (int)CIBlock::GetList([], ['=CODE' => 'cabinet_partners', 'CHECK_PERMISSIONS' => 'N'])->Fetch()['ID'];
$partner = CIBlockElement::GetList([], ['IBLOCK_ID' => $partnerIblock, '=CODE' => $DEMO['partner_code']], false, ['nTopCount' => 1], ['ID'])->Fetch();
$partnerId = $partner ? (int)$partner['ID'] : 0;

$userIds = [];
foreach ([$DEMO['partner_email'], $DEMO['buyer_email']] as $email) {
    $u = CUser::GetList($by = 'id', $order = 'asc', ['=EMAIL' => $email])->Fetch();
    if ($u) {
        $userIds[] = (int)$u['ID'];
    }
}
$buyerId = 0;
if ($u = CUser::GetList($by = 'id', $order = 'asc', ['=EMAIL' => $DEMO['buyer_email']])->Fetch()) {
    $buyerId = (int)$u['ID'];
}

$deleteRows = static function (string $hl, array $filter, ?callable $each = null): int {
    $dc = HlblockEntityFactory::getDataClass($hl);
    $n = 0;
    foreach ($dc::getList(['filter' => $filter])->fetchAll() as $row) {
        if ($each) {
            $each($row);
        }
        $dc::delete($row['ID']);
        $n++;
    }

    return $n;
};
$deleteFiles = static function (array $row, string $field) {
    foreach ((array)($row[$field] ?? []) as $fid) {
        FileUploader::delete((int)$fid);
    }
};

if ($partnerId) {
    // Диалоги и сообщения
    $deleteRows('CabinetChatThreads', ['=UF_PARTNER_ID' => $partnerId], static function ($t) use ($deleteRows, $deleteFiles) {
        $deleteRows('CabinetChatMessages', ['=UF_THREAD_ID' => $t['ID']], static fn($m) => $deleteFiles($m, 'UF_ATTACHMENTS'));
    });
    // Обращения
    $deleteRows('CabinetTickets', ['=UF_PARTNER_ID' => $partnerId], static function ($t) use ($deleteRows, $deleteFiles) {
        $deleteRows('CabinetTicketMessages', ['=UF_TICKET_ID' => $t['ID']], static fn($m) => $deleteFiles($m, 'UF_ATTACHMENTS'));
    });
    foreach (['CabinetOrders', 'CabinetDiscounts', 'CabinetCoupons', 'CabinetTransactions', 'CabinetNotifications', 'CabinetPartnerDocuments'] as $hl) {
        try {
            $deleteRows($hl, ['=UF_PARTNER_ID' => $partnerId]);
        } catch (\Throwable $e) {
        }
    }
    // Товары, новости, категории партнёра
    foreach (['cabinet_catalog', 'cabinet_news'] as $code) {
        $res = CIBlockElement::GetList([], ['IBLOCK_CODE' => $code, 'PROPERTY_PARTNER_ID' => $partnerId, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID']);
        while ($row = $res->Fetch()) {
            CIBlockElement::Delete($row['ID']);
        }
    }
    $catalogId = (int)CIBlock::GetList([], ['=CODE' => 'cabinet_catalog', 'CHECK_PERMISSIONS' => 'N'])->Fetch()['ID'];
    $res = CIBlockSection::GetList(['DEPTH_LEVEL' => 'DESC'], ['IBLOCK_ID' => $catalogId, 'UF_PARTNER_ID' => $partnerId, 'CHECK_PERMISSIONS' => 'N'], false, ['ID']);
    while ($row = $res->Fetch()) {
        CIBlockSection::Delete($row['ID']);
    }
    // Уведомления администраторов о демо-партнёре
    $deleteRows('CabinetNotifications', ['=UF_ADMIN' => 1, '%UF_MESSAGE' => 'Спецодежда Плюс']);
    CIBlockElement::Delete($partnerId);
}

if ($buyerId) {
    $deleteRows('CabinetOrders', ['=UF_USER_ID' => $buyerId]);
    $deleteRows('CabinetNotifications', ['=UF_USER_ID' => $buyerId]);
    foreach (['FormaroCartItems', 'FormaroFavorites', 'FormaroViewedProducts', 'FormaroSearchHistory'] as $hl) {
        try {
            $deleteRows($hl, ['=UF_USER_ID' => $buyerId]);
        } catch (\Throwable $e) {
        }
    }
}
foreach ($userIds as $id) {
    CUser::Delete($id);
}

echo 'Демо-данные удалены: партнёр ', $partnerId ?: '—', ', пользователи ', implode(', ', $userIds) ?: '—', "\n";
