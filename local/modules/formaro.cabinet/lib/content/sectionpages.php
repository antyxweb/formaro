<?php

namespace Formaro\Cabinet\Content;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use CIBlock;
use CIBlockElement;
use CIBlockSection;

/**
 * Пункты раздела сайта («Покупателям», «Партнерам», «О нас», «Поддержка»)
 * из инфоблока «Контентные страницы» (content_pages): активные элементы
 * раздела инфоблока с тем же кодом, по сортировке. Один источник для
 * плиток на главной раздела, меню справа и меню в подвале — все они
 * читают меню типа left папки раздела, а его наполняет .left.menu_ext.php
 * через menu().
 *
 * Пункт: страница — /<раздел>/<код>/ (код = код раздела — главная
 * раздела, /<раздел>/), элемент со свойством LINK — ссылка; ICON — иконка
 * плитки. Кэш тегированный по инфоблоку — правка в админке видна сразу.
 */
class SectionPages
{
    public const IBLOCK_CODE = 'content_pages';
    private const CACHE_TIME = 36000000;

    /**
     * @return array<int, array{code: string, name: string, url: string, icon: string, link: string}>
     */
    public static function items(string $sectionCode): array
    {
        return self::section($sectionCode)['items'];
    }

    /** Название раздела инфоблока (заголовок главной раздела). */
    public static function name(string $sectionCode): string
    {
        return self::section($sectionCode)['name'];
    }

    /** Описание раздела инфоблока (HTML) — вступление над плитками главной раздела. */
    public static function description(string $sectionCode): string
    {
        return self::section($sectionCode)['description'] ?? '';
    }

    /** @return array{name: string, description: string, items: array} */
    private static function section(string $sectionCode): array
    {
        if ($sectionCode === '' || !Loader::includeModule('iblock')) {
            return ['name' => '', 'description' => '', 'items' => []];
        }
        $cache = Cache::createInstance();
        $dir = '/formaro/content_section';
        if ($cache->initCache(self::CACHE_TIME, 'section_' . $sectionCode, $dir)) {
            return $cache->getVars();
        }
        $cache->startDataCache();
        $iblockId = self::iblockId();
        $data = $iblockId ? self::load($iblockId, $sectionCode) : ['name' => '', 'description' => '', 'items' => []];
        if ($iblockId) {
            $tagged = Application::getInstance()->getTaggedCache();
            $tagged->startTagCache($dir);
            $tagged->registerTag('iblock_id_' . $iblockId);
            $tagged->endTagCache();
        }
        $cache->endDataCache($data);

        return $data;
    }

    /** Пункты в формате меню Bitrix (для .left.menu_ext.php). */
    public static function menu(string $sectionCode): array
    {
        return array_map(static fn(array $item) => [
            $item['name'],
            $item['url'],
            [],
            $item['icon'] !== '' ? ['ICON' => $item['icon']] : [],
            '',
        ], self::items($sectionCode));
    }

    public static function iblockId(): int
    {
        if (!Loader::includeModule('iblock')) {
            return 0;
        }
        $row = CIBlock::GetList([], ['=CODE' => self::IBLOCK_CODE, 'TYPE' => 'content', 'CHECK_PERMISSIONS' => 'N'])->Fetch();

        return $row ? (int)$row['ID'] : 0;
    }

    /** @return array{name: string, description: string, items: array} */
    private static function load(int $iblockId, string $sectionCode): array
    {
        $section = CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => $sectionCode, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'], false, ['ID', 'NAME', 'DESCRIPTION', 'DESCRIPTION_TYPE'])->Fetch();
        if (!$section) {
            return ['name' => '', 'description' => '', 'items' => []];
        }
        $items = [];
        $res = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'SECTION_ID' => (int)$section['ID'], 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID', 'NAME', 'CODE', 'PROPERTY_LINK', 'PROPERTY_ICON']
        );
        while ($row = $res->Fetch()) {
            $code = (string)$row['CODE'];
            $link = trim((string)$row['PROPERTY_LINK_VALUE']);
            $items[] = [
                'code' => $code,
                'name' => (string)$row['NAME'],
                'url' => $link !== '' ? $link : '/' . $sectionCode . '/' . ($code === $sectionCode ? '' : $code . '/'),
                'icon' => trim((string)$row['PROPERTY_ICON_VALUE']),
                'link' => $link,
            ];
        }

        $description = (string)$section['DESCRIPTION'];
        if ($description !== '' && $section['DESCRIPTION_TYPE'] !== 'html') {
            $description = nl2br(htmlspecialcharsbx($description));
        }

        return ['name' => (string)$section['NAME'], 'description' => $description, 'items' => $items];
    }
}
