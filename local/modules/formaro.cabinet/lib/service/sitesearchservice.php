<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Loader;
use CFile;
use CIBlock;
use CIBlockElement;
use CIBlockSection;
use Formaro\Cabinet\Repository\DiscountRepository;
use Formaro\Cabinet\Repository\ProductRepository;

/**
 * Поиск в шапке сайта (/local/ajax/header_search.php, js/header-search.js):
 * совпадения по названию среди того, что видно на витрине —
 *   categories — категории каталога: активные и допущенные площадкой
 *                (UF_APPROVED) вместе с родителями, с активными товарами;
 *   products   — активные товары (название или артикул), цена со скидкой;
 *   partners   — активные партнёры (полное или краткое название);
 *   news       — активные новости.
 * В каждой группе — первые LIMIT, плюс общее число товаров (ссылка «Все
 * товары» ведёт в поиск на главной, /?q=…).
 */
class SiteSearchService
{
    public const MIN_LENGTH = 2;
    private const LIMIT = 5;

    /**
     * @return array{query: string, categories: array, products: array, products_total: int, partners: array, news: array}
     */
    public static function search(string $query): array
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query));
        $result = ['query' => $query, 'categories' => [], 'products' => [], 'products_total' => 0, 'partners' => [], 'news' => []];
        if (mb_strlen($query) < self::MIN_LENGTH || !Loader::includeModule('iblock')) {
            return $result;
        }

        $result['categories'] = self::categories($query);
        [$result['products'], $result['products_total']] = self::products($query);
        $result['partners'] = self::partners($query);
        $result['news'] = self::news($query);

        return $result;
    }

    private static function iblockId(string $code): int
    {
        $row = CIBlock::GetList([], ['=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'])->Fetch();

        return $row ? (int)$row['ID'] : 0;
    }

    private static function categories(string $query): array
    {
        $iblockId = self::iblockId('cabinet_catalog');
        if (!$iblockId) {
            return [];
        }
        $items = [];
        $res = CIBlockSection::GetList(
            ['DEPTH_LEVEL' => 'ASC', 'SORT' => 'ASC', 'NAME' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, '%NAME' => $query, 'GLOBAL_ACTIVE' => 'Y', 'UF_APPROVED' => 1, 'CNT_ACTIVE' => 'Y', 'ELEMENT_SUBSECTIONS' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            true,
            ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'SECTION_PAGE_URL'],
            ['nTopCount' => 30]
        );
        while (count($items) < self::LIMIT && ($row = $res->GetNext())) {
            if ((int)$row['ELEMENT_CNT'] === 0) {
                continue;
            }
            $chain = self::approvedChain($iblockId, (int)$row['ID']);
            if ($chain === null) {
                continue;
            }
            array_pop($chain);
            $items[] = [
                'name' => (string)$row['~NAME'],
                'url' => (string)$row['~SECTION_PAGE_URL'],
                'hint' => implode(' / ', $chain),
                'count' => (int)$row['ELEMENT_CNT'],
            ];
        }

        return $items;
    }

    /** Названия раздела и родителей, если все они активны и допущены; иначе null. */
    private static function approvedChain(int $iblockId, int $sectionId): ?array
    {
        $ids = array_map('intval', array_column(CIBlockSection::GetNavChain($iblockId, $sectionId, ['ID'], true), 'ID'));
        $names = [];
        $res = CIBlockSection::GetList(['DEPTH_LEVEL' => 'ASC'], ['IBLOCK_ID' => $iblockId, 'ID' => $ids, 'CHECK_PERMISSIONS' => 'N'], false, ['ID', 'NAME', 'ACTIVE', 'UF_APPROVED']);
        while ($row = $res->Fetch()) {
            if ($row['ACTIVE'] !== 'Y' || empty($row['UF_APPROVED'])) {
                return null;
            }
            $names[] = (string)$row['NAME'];
        }

        return count($names) === count($ids) ? $names : null;
    }

    private static function products(string $query): array
    {
        $repo = new ProductRepository();
        $filter = ProductRepository::buildPublicFilter(['q' => $query]);
        $total = $repo->countPublic($filter);
        if (!$total) {
            return [[], 0];
        }
        $discounts = (new DiscountRepository())->listActive();
        $items = array_map(static function (array $p) use ($discounts) {
            $display = ProductPricingService::computeDisplay($p, $discounts);

            return [
                'name' => (string)$p['name'],
                'url' => (string)$p['public_url'],
                'sku' => (string)$p['sku'],
                'image' => (string)$p['preview_image'],
                'price' => (float)$display['price'],
                'old_price' => $display['old_price'] !== null ? (float)$display['old_price'] : null,
            ];
        }, $repo->listPublic(self::LIMIT, 0, $filter, 'popular'));

        return [$items, $total];
    }

    private static function partners(string $query): array
    {
        $items = [];
        $res = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            [
                'IBLOCK_CODE' => 'cabinet_partners',
                'ACTIVE' => 'Y',
                'CHECK_PERMISSIONS' => 'N',
                ['LOGIC' => 'OR', '%NAME' => $query, '%PROPERTY_NAME_SHORT' => $query],
            ],
            false,
            ['nTopCount' => self::LIMIT],
            ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL', 'PROPERTY_NAME_SHORT']
        );
        while ($row = $res->GetNext()) {
            $short = trim((string)$row['~PROPERTY_NAME_SHORT_VALUE']);
            $items[] = [
                'name' => $short !== '' ? $short : (string)$row['~NAME'],
                'hint' => $short !== '' && $short !== $row['~NAME'] ? (string)$row['~NAME'] : '',
                'url' => (string)$row['~DETAIL_PAGE_URL'],
                'image' => $row['PREVIEW_PICTURE'] ? (string)CFile::GetPath((int)$row['PREVIEW_PICTURE']) : '',
            ];
        }

        return $items;
    }

    private static function news(string $query): array
    {
        $items = [];
        $res = CIBlockElement::GetList(
            ['ACTIVE_FROM' => 'DESC', 'DATE_CREATE' => 'DESC'],
            ['IBLOCK_CODE' => 'cabinet_news', 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y', '%NAME' => $query, 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['nTopCount' => self::LIMIT],
            ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL', 'ACTIVE_FROM', 'DATE_CREATE']
        );
        while ($row = $res->GetNext()) {
            $date = (string)($row['ACTIVE_FROM'] ?: $row['DATE_CREATE']);
            $items[] = [
                'name' => (string)$row['~NAME'],
                'url' => (string)$row['~DETAIL_PAGE_URL'],
                'hint' => $date !== '' ? FormatDate('j F Y', MakeTimeStamp($date)) : '',
            ];
        }

        return $items;
    }
}
