<?php

namespace Formaro\Cabinet\Catalog;

use CIBlockElement;
use Bitrix\Main\Loader;

/**
 * Корень адресов публичного каталога на текущей странице.
 *
 * Обычно — /catalog/ (адреса товаров и категорий — ЧПУ инфоблока
 * cabinet_catalog). У партнёра свой каталог /partners/<код>/catalog/
 * (только его товары и категории): страницы партнёра ставят корень
 * setRoot(), и все ссылки на товары/категории, которые строятся за этот
 * запрос (ProductRepository public_url, разделы, цепочка), переводятся
 * в него через localize(). Компоненты, кэширующие ссылки, добавляют
 * root() в ключ кэша.
 *
 * AJAX-подгрузка (catalog_grid.php, product_carousel.php) получает корень
 * параметром root и принимает только корень каталога переданного
 * партнёра (applyRequestRoot()).
 */
final class CatalogUrl
{
    public const ROOT = '/catalog/';
    private const PARTNERS_IBLOCK_CODE = 'cabinet_partners';

    private static string $root = self::ROOT;
    /** @var array<int, string> id партнёра → корень его каталога ('' — нет) */
    private static array $partnerRoots = [];

    public static function root(): string
    {
        return self::$root;
    }

    public static function setRoot(string $root): void
    {
        self::$root = $root !== '' ? rtrim($root, '/') . '/' : self::ROOT;
    }

    /** Адрес каталога (/catalog/…) → тот же адрес от текущего корня. */
    public static function localize(string $url): string
    {
        if (self::$root === self::ROOT || strpos($url, self::ROOT) !== 0) {
            return $url;
        }

        return self::$root . substr($url, strlen(self::ROOT));
    }

    /** Главная партнёра: /partners/<код>/ ('' — партнёр не найден или неактивен). */
    public static function partnerHome(int $partnerId): string
    {
        $root = self::partnerRoot($partnerId);

        return $root !== '' ? substr($root, 0, -strlen('catalog/')) : '';
    }

    /** Каталог партнёра: /partners/<код>/catalog/ ('' — партнёр не найден или неактивен). */
    public static function partnerRoot(int $partnerId): string
    {
        if ($partnerId <= 0) {
            return '';
        }
        if (!isset(self::$partnerRoots[$partnerId])) {
            $code = '';
            if (Loader::includeModule('iblock')) {
                $row = CIBlockElement::GetList(
                    [],
                    ['IBLOCK_CODE' => self::PARTNERS_IBLOCK_CODE, 'ID' => $partnerId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
                    false,
                    ['nTopCount' => 1],
                    ['ID', 'CODE']
                )->Fetch();
                $code = $row ? (string)$row['CODE'] : '';
            }
            self::$partnerRoots[$partnerId] = $code !== '' ? '/partners/' . $code . '/catalog/' : '';
        }

        return self::$partnerRoots[$partnerId];
    }

    /** Корень из AJAX-запроса: только каталог этого партнёра, иначе /catalog/. */
    public static function applyRequestRoot(string $root, int $partnerId): void
    {
        $partnerRoot = self::partnerRoot($partnerId);
        self::setRoot($partnerRoot !== '' && $root === $partnerRoot ? $partnerRoot : self::ROOT);
    }
}
