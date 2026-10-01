<?php
/**
 * Поиск в шапке сайта (js/header-search.js): GET q → JSON с группами
 * categories / products (+ products_total) / partners / news — см.
 * SiteSearchService. Только чтение — без check_bitrix_sessid(), как
 * catalog_grid.php.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\SiteSearchService;

header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('formaro.cabinet')) {
    http_response_code(500);
    echo json_encode(['error' => 'formaro.cabinet module not found']);
    die();
}

echo json_encode(
    SiteSearchService::search(mb_substr((string)($_GET['q'] ?? ''), 0, 100)),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
