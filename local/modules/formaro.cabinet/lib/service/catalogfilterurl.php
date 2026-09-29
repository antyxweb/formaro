<?php

namespace Formaro\Cabinet\Service;

use CUtil;

/**
 * ЧПУ фильтра категории каталога:
 *   <категория>/filter/section-<код>-or-<код>/color-<цвет>/size-<размер>/price-from-<N>-to-<M>/
 * Сегменты — в этом порядке, пустые не пишутся; значения внутри сегмента —
 * через «-or-». Категория — символьный код раздела, цвет и размер —
 * транслитерация значения (slug()). Сортировка и страница остаются в
 * query (?sort=…&page=…).
 *
 * Используется formaro:catalog.section (разбор и ссылки) и её script.js
 * (сборка адреса при «Применить» — data-slug у чекбоксов).
 */
class CatalogFilterUrl
{
    private const KEYS = ['section', 'color', 'size'];
    private const SEPARATOR = '-or-';

    /** «Тёмно-синий» → temno-siniy, «XL» → xl, «110-116» → 110-116.
     *  «Чёрный» и «Черный» дают один slug — по ссылке выберутся оба. */
    public static function slug(string $value): string
    {
        // «ё» транслит Битрикса пишет как «ye» — приводим к «е».
        $value = str_replace(['ё', 'Ё'], ['е', 'Е'], trim($value));
        $slug = CUtil::translit($value, 'ru', [
            'max_len' => 100,
            'change_case' => 'L',
            'replace_space' => '-',
            'replace_other' => '-',
            'delete_repeat_replace' => true,
        ]);
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : substr(md5($value), 0, 8);
    }

    /**
     * @return array{section: string[], color: string[], size: string[], price_from: string, price_to: string}|null
     *         null — адрес не по формату (→ 404)
     */
    public static function parse(string $path): ?array
    {
        $result = ['section' => [], 'color' => [], 'size' => [], 'price_from' => '', 'price_to' => ''];
        $seen = [];

        foreach (array_filter(explode('/', trim($path, '/')), 'strlen') as $segment) {
            if (!preg_match('/^([a-z]+)-(.+)$/', $segment, $m) || isset($seen[$m[1]])) {
                return null;
            }
            $seen[$m[1]] = true;

            if ($m[1] === 'price') {
                if (!preg_match('/^(?:from-(\d+))?-?(?:to-(\d+))?$/', $m[2], $price) || ($price[1] ?? '') . ($price[2] ?? '') === '') {
                    return null;
                }
                $result['price_from'] = $price[1] ?? '';
                $result['price_to'] = $price[2] ?? '';
                continue;
            }
            if (!in_array($m[1], self::KEYS, true)) {
                return null;
            }
            $result[$m[1]] = array_values(array_unique(explode(self::SEPARATOR, $m[2])));
        }

        return $result;
    }

    /**
     * @param array $filter section/color/size — списки slug'ов, price_from/price_to
     * @param array $query  sort, page — в query-строку
     */
    public static function build(string $baseUrl, array $filter, array $query = []): string
    {
        $segments = [];
        foreach (self::KEYS as $key) {
            $values = array_values(array_unique(array_filter((array)($filter[$key] ?? []), 'strlen')));
            if ($values) {
                $segments[] = $key . '-' . implode(self::SEPARATOR, $values);
            }
        }
        $price = [];
        if ((string)($filter['price_from'] ?? '') !== '') {
            $price[] = 'from-' . (int)$filter['price_from'];
        }
        if ((string)($filter['price_to'] ?? '') !== '') {
            $price[] = 'to-' . (int)$filter['price_to'];
        }
        if ($price) {
            $segments[] = 'price-' . implode('-', $price);
        }

        $url = rtrim($baseUrl, '/') . '/' . ($segments ? 'filter/' . implode('/', $segments) . '/' : '');
        $query = array_filter($query, static fn($v) => $v !== '' && $v !== null);

        return $url . ($query ? '?' . http_build_query($query) : '');
    }
}
