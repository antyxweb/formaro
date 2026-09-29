<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Loader;
use CIBlock;
use CIBlockSection;

/**
 * Данные для фильтра витрины (главная — formaro:catalog.search,
 * избранное — formaro:favorites.list).
 */
class CatalogFilterService
{
    /** Буквенные размеры одежды по возрастанию; 2XL/3XL приводятся к XXL/XXXL. */
    private const LETTER_SIZES = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '4XL', '5XL', '6XL'];

    /**
     * Порядок размеров для фильтра: сначала буквенные (S, M, L, XL, ...),
     * затем числовые по первому числу (8, 9, 56, 110-116, 122-128, ...),
     * остальное — по алфавиту.
     */
    public static function sortSizes(array $sizes): array
    {
        $rank = static function (string $size): array {
            $key = strtoupper(str_replace(' ', '', $size));
            if (preg_match('/^([23])XL$/', $key, $m)) {
                $key = str_repeat('X', (int)$m[1]) . 'L';
            }
            $letter = array_search($key, self::LETTER_SIZES, true);
            if ($letter !== false) {
                return [0, $letter, $size];
            }
            if (preg_match('/^\d+(?:[.,]\d+)?/', $size, $m)) {
                return [1, (float)str_replace(',', '.', $m[0]), $size];
            }

            return [2, 0, mb_strtolower($size)];
        };

        usort($sizes, static fn($a, $b) => $rank((string)$a) <=> $rank((string)$b));

        return $sizes;
    }

    public static function getCatalogIblockId(): int
    {
        if (!Loader::includeModule('iblock')) {
            return 0;
        }

        return (int)(CIBlock::GetList([], ['CODE' => 'cabinet_catalog', 'CHECK_PERMISSIONS' => 'N'])->Fetch()['ID'] ?? 0);
    }

    /**
     * Корневые категории → их подкатегории, только допущенные к показу
     * (UF_APPROVED), как и в каталоге на главной.
     *
     * $partnerId — страница партнёра: только категории, где есть его
     * активные товары (корневая остаётся, если товары в её подкатегориях).
     *
     * @return array<int, array{ID: int, NAME: string, ITEMS: array<int, array{ID: int, NAME: string}>}>
     */
    public static function getSectionGroups(int $partnerId = 0): array
    {
        $iblockId = self::getCatalogIblockId();
        if (!$iblockId) {
            return [];
        }

        $groups = [];
        $children = [];
        $res = CIBlockSection::GetList(
            ['LEFT_MARGIN' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'UF_APPROVED' => 1, 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID']
        );
        while ($row = $res->Fetch()) {
            if ((int)$row['DEPTH_LEVEL'] === 1) {
                $groups[(int)$row['ID']] = ['ID' => (int)$row['ID'], 'NAME' => $row['NAME'], 'ITEMS' => []];
            } elseif ((int)$row['DEPTH_LEVEL'] === 2) {
                $children[(int)$row['IBLOCK_SECTION_ID']][] = ['ID' => (int)$row['ID'], 'NAME' => $row['NAME']];
            }
        }

        foreach ($groups as $id => &$group) {
            $group['ITEMS'] = $children[$id] ?? [];
        }
        unset($group);

        if ($partnerId > 0) {
            $used = self::getPartnerSectionIds($iblockId, $partnerId);
            foreach ($groups as $id => &$group) {
                $group['ITEMS'] = array_values(array_filter($group['ITEMS'], static fn($item) => isset($used[$item['ID']])));
            }
            unset($group);
            $groups = array_filter($groups, static fn($group) => isset($used[$group['ID']]) || $group['ITEMS']);
        }

        return array_values($groups);
    }

    /** @return array<int, true> разделы с активными товарами партнёра и их родители */
    private static function getPartnerSectionIds(int $iblockId, int $partnerId): array
    {
        $productIds = [];
        $res = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'PROPERTY_PARTNER_ID' => $partnerId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID']
        );
        while ($row = $res->Fetch()) {
            $productIds[] = (int)$row['ID'];
        }
        if (!$productIds) {
            return [];
        }

        $sectionIds = [];
        $res = \CIBlockElement::GetElementGroups($productIds, true, ['ID', 'IBLOCK_SECTION_ID']);
        while ($row = $res->Fetch()) {
            $sectionIds[(int)$row['ID']] = true;
            if ((int)$row['IBLOCK_SECTION_ID']) {
                $sectionIds[(int)$row['IBLOCK_SECTION_ID']] = true;
            }
        }

        return $sectionIds;
    }
}
