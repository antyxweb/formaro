<?php

namespace Formaro\Cabinet\Import;

/**
 * Разбор файла товаров партнёра в общий вид — YML (Яндекс.Маркет и всё, что
 * выгружает в этом формате: 1С, CMS, Ozon/WB-выгрузки) и CSV из Excel.
 *
 * Товар: ext_id, sku, name, price, stock (null — не указан), available,
 * category (id категории файла), description (HTML), pictures[], color, size,
 * vendor, params [{name, value}], group_id (варианты одного товара).
 * Категории: [{id, parent_id, name}].
 */
class FeedParser
{
    public const MAX_ITEMS = 10000;

    private const COLOR_NAMES = ['цвет', 'color', 'colour'];
    private const SIZE_NAMES = ['размер', 'size'];

    /** Колонки CSV: ключ товара => варианты заголовка (без регистра). */
    private const CSV_COLUMNS = [
        'sku' => ['артикул', 'sku', 'vendorcode', 'код товара', 'код'],
        'name' => ['название', 'наименование', 'name', 'товар', 'title'],
        'price' => ['цена', 'price', 'цена поставщика', 'оптовая цена'],
        'stock' => ['остаток', 'количество', 'кол-во', 'stock', 'qty', 'quantity', 'count'],
        'category' => ['категория', 'раздел', 'category', 'группа'],
        'description' => ['описание', 'description'],
        'pictures' => ['картинка', 'картинки', 'изображение', 'изображения', 'фото', 'picture', 'pictures', 'image', 'images'],
        'color' => self::COLOR_NAMES,
        'size' => self::SIZE_NAMES,
        'vendor' => ['бренд', 'производитель', 'vendor', 'brand'],
        'group_id' => ['группа вариантов', 'group_id', 'id группы'],
    ];

    /** @return array{format: string, categories: array, items: array} */
    public static function parse(string $path): array
    {
        $head = (string)file_get_contents($path, false, null, 0, 2048);
        $head = preg_replace('/^\xEF\xBB\xBF/', '', $head);

        if (preg_match('/^\s*</', $head)) {
            // Не только <yml_catalog>: многие «свои» XML повторяют его <offer>.
            $result = self::parseYml($path);
            if (!$result['items']) {
                throw new \RuntimeException('В XML-файле не найдено ни одного товара (<offer>) — нужен формат YML');
            }

            return $result;
        }

        return self::parseCsv($path);
    }

    private static function parseYml(string $path): array
    {
        $reader = new \XMLReader();
        // LIBXML_NONET — никаких внешних DTD/сущностей по сети (XXE).
        if (!$reader->open($path, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new \RuntimeException('Не удалось открыть XML-файл');
        }

        $categories = [];
        $items = [];
        $prev = libxml_use_internal_errors(true);
        // После expand() узел пропускаем через next() — он уже стоит на следующем
        // узле, и read() в условии цикла перескочил бы соседний <offer>
        // (если между ними нет пробелов/переносов).
        $advanced = false;
        while ($advanced || @$reader->read()) {
            $advanced = false;
            if ($reader->nodeType !== \XMLReader::ELEMENT) {
                continue;
            }
            if ($reader->name === 'category') {
                $id = (string)$reader->getAttribute('id');
                $parent = (string)$reader->getAttribute('parentId');
                $name = trim($reader->readString());
                if ($id !== '' && $name !== '') {
                    $categories[$id] = ['id' => $id, 'parent_id' => $parent !== '0' ? $parent : '', 'name' => $name];
                }
                continue;
            }
            if ($reader->name !== 'offer') {
                continue;
            }
            if (count($items) >= self::MAX_ITEMS) {
                break;
            }
            $node = @$reader->expand(new \DOMDocument());
            if ($node) {
                $item = self::ymlOffer(simplexml_import_dom($node));
                if ($item) {
                    $items[] = $item;
                }
            }
            $advanced = @$reader->next();
        }
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $reader->close();

        if (!$items && $errors) {
            throw new \RuntimeException('Ошибка в XML (строка ' . $errors[0]->line . '): ' . trim($errors[0]->message));
        }

        return ['format' => 'yml', 'categories' => array_values($categories), 'items' => $items];
    }

    private static function ymlOffer(\SimpleXMLElement $o): ?array
    {
        $text = static fn($node) => trim((string)$node);

        $name = $text($o->name);
        if ($name === '') {
            // type="vendor.model": название собирается из частей
            $name = trim(preg_replace('/\s+/', ' ', $text($o->typePrefix) . ' ' . $text($o->vendor) . ' ' . $text($o->model)));
        }
        if ($name === '') {
            return null;
        }

        $color = '';
        $size = '';
        $params = [];
        foreach ($o->param as $param) {
            $pName = trim((string)$param['name']);
            $value = trim((string)$param);
            if ($pName === '' || $value === '') {
                continue;
            }
            $unit = trim((string)$param['unit']);
            if ($unit !== '') {
                $value .= ' ' . $unit;
            }
            $lower = mb_strtolower($pName);
            if ($color === '' && in_array($lower, self::COLOR_NAMES, true)) {
                $color = $value;
            } elseif ($size === '' && in_array($lower, self::SIZE_NAMES, true)) {
                $size = $value;
            } else {
                $params[] = ['name' => $pName, 'value' => $value];
            }
        }

        $stock = null;
        foreach (['count', 'quantity', 'stock_quantity', 'stock'] as $tag) {
            if (isset($o->$tag) && $text($o->$tag) !== '') {
                $stock = max(0, (int)$text($o->$tag));
                break;
            }
        }
        if ($stock === null && isset($o->outlets->outlet)) {
            $stock = 0;
            foreach ($o->outlets->outlet as $outlet) {
                $stock += max(0, (int)$outlet['instock']);
            }
        }

        $pictures = [];
        foreach ($o->picture as $picture) {
            $url = $text($picture);
            if ($url !== '') {
                $pictures[] = $url;
            }
        }

        $extId = trim((string)$o['id']);
        $vendorCode = $text($o->vendorCode);

        return [
            'ext_id' => $extId,
            'sku' => $vendorCode !== '' ? $vendorCode : $extId,
            'name' => $name,
            'price' => self::number($text($o->price)),
            'stock' => $stock,
            'available' => strtolower((string)$o['available']) !== 'false',
            'category' => $text($o->categoryId),
            'description' => $text($o->description),
            'pictures' => array_values(array_unique($pictures)),
            'color' => $color,
            'size' => $size,
            'vendor' => $text($o->vendor),
            'params' => $params,
            'group_id' => trim((string)$o['group_id']),
        ];
    }

    private static function parseCsv(string $path): array
    {
        $content = (string)file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1251');
        }

        $firstLine = strtok($content, "\n");
        $delimiter = ';';
        $best = 0;
        foreach ([';', ',', "\t"] as $candidate) {
            $count = substr_count((string)$firstLine, $candidate);
            if ($count > $best) {
                $best = $count;
                $delimiter = $candidate;
            }
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $header = fgetcsv($stream, 0, $delimiter, '"', '');
        if (!$header || count($header) < 2) {
            throw new \RuntimeException('Не удалось прочитать CSV: в первой строке должны быть названия колонок');
        }

        $map = [];
        foreach ($header as $i => $title) {
            $title = mb_strtolower(trim((string)$title));
            foreach (self::CSV_COLUMNS as $key => $variants) {
                if (!isset($map[$key]) && in_array($title, $variants, true)) {
                    $map[$key] = $i;
                    continue 2;
                }
            }
            if ($title !== '') {
                $map['param:' . trim((string)$header[$i])] = $i;
            }
        }
        if (!isset($map['name'])) {
            throw new \RuntimeException('В CSV нет колонки «Название»');
        }

        $categories = [];
        $items = [];
        $line = 1;
        while (($row = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $line++;
            if (count($items) >= self::MAX_ITEMS) {
                break;
            }
            $get = static fn(string $key) => isset($map[$key]) ? trim((string)($row[$map[$key]] ?? '')) : '';
            $name = $get('name');
            if ($name === '') {
                continue;
            }

            $category = $get('category');
            if ($category !== '') {
                $categories[$category] = ['id' => $category, 'parent_id' => '', 'name' => $category];
            }
            $params = [];
            foreach ($map as $key => $i) {
                if (str_starts_with($key, 'param:') && trim((string)($row[$i] ?? '')) !== '') {
                    $params[] = ['name' => substr($key, 6), 'value' => trim((string)$row[$i])];
                }
            }
            $stock = $get('stock');
            $sku = $get('sku');

            $items[] = [
                'ext_id' => $sku !== '' ? $sku : 'row' . $line,
                'sku' => $sku,
                'name' => $name,
                'price' => self::number($get('price')),
                'stock' => $stock !== '' ? max(0, (int)self::number($stock)) : null,
                'available' => true,
                'category' => $category,
                'description' => $get('description'),
                'pictures' => array_values(array_filter(array_map('trim', preg_split('/[\s,;|]+/', $get('pictures'))))),
                'color' => $get('color'),
                'size' => $get('size'),
                'vendor' => $get('vendor'),
                'params' => $params,
                'group_id' => $get('group_id'),
            ];
        }
        fclose($stream);

        return ['format' => 'csv', 'categories' => array_values($categories), 'items' => $items];
    }

    /** «1 299,90», «1299.9 руб.» → 1299.9 */
    private static function number(string $value): float
    {
        $value = str_replace([' ', "\xC2\xA0", ','], ['', '', '.'], $value);

        return (float)preg_replace('/[^\d.]/', '', $value);
    }
}
