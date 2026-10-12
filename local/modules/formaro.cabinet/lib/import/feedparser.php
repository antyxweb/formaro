<?php

namespace Formaro\Cabinet\Import;

/**
 * Разбор файла товаров партнёра в общий вид — YML (Яндекс.Маркет и всё, что
 * выгружает в этом формате: 1С, CMS, Ozon/WB-выгрузки), таблицы Excel (XLSX) и CSV.
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

    /** Теги <offer>, которые уже разобраны в поля товара или служебные для Маркета. */
    private const YML_SKIP_TAGS = [
        'name', 'typePrefix', 'vendor', 'vendorCode', 'price', 'oldprice', 'purchase_price', 'currencyId',
        'categoryId', 'picture', 'description', 'param', 'count', 'quantity', 'stock_quantity', 'stock',
        'outlets', 'url', 'delivery', 'pickup', 'store', 'delivery-options', 'pickup-options',
        'enable_auto_discounts', 'cpa', 'bid', 'cbid', 'fee', 'available', 'downloadable', 'group_id',
        'shop-sku', 'market-sku', 'disabled',
    ];

    /** Понятные названия для стандартных тегов YML, которые идут в дополнительные свойства. */
    private const YML_TAG_LABELS = [
        'model' => 'Модель',
        'barcode' => 'Штрихкод',
        'weight' => 'Вес (кг)',
        'dimensions' => 'Габариты Д/Ш/В (см)',
        'country_of_origin' => 'Страна производства',
        'manufacturer' => 'Изготовитель',
        'manufacturer_warranty' => 'Гарантия производителя',
        'warranty-days' => 'Гарантия',
        'period-of-validity-days' => 'Срок годности',
        'service-life-days' => 'Срок службы',
        'comment-validity-days' => 'Условия хранения',
        'comment-warranty' => 'Условия гарантии',
        'sales_notes' => 'Условия продажи',
        'min-quantity' => 'Минимальная партия',
        'step-quantity' => 'Кратность заказа',
        'age' => 'Возраст',
        'adult' => 'Для взрослых',
        'expiry' => 'Срок годности',
        'tn-ved-code' => 'Код ТН ВЭД',
        'material' => 'Материал',
        'composition' => 'Состав',
    ];

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

        if (str_starts_with($head, "\xD0\xCF\x11\xE0")) {
            throw new \RuntimeException('Старый формат Excel (.xls) не поддерживается — сохраните файл как .xlsx или .csv');
        }
        if (str_starts_with($head, "PK\x03\x04")) {
            return self::parseTable(XlsxReader::rows($path), 'xlsx');
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

        // Остальные теги предложения (вес, штрихкод, страна, гарантия, свои теги
        // выгрузки) — в дополнительные свойства, если такого <param> ещё нет.
        $known = array_flip(array_map('mb_strtolower', array_column($params, 'name')));
        $yesNo = ['true' => 'да', 'false' => 'нет'];
        foreach ($o->children() as $tag => $child) {
            if (in_array($tag, self::YML_SKIP_TAGS, true) || $child->count() > 0) {
                continue;
            }
            $value = trim(preg_replace('/\s+/u', ' ', (string)$child));
            if ($value === '') {
                continue;
            }
            $value = $yesNo[strtolower($value)] ?? $value;
            if ($tag === 'age' && (string)$child['unit'] !== '') {
                $value .= (string)$child['unit'] === 'month' ? ' мес.' : '+';
            }
            $pName = self::YML_TAG_LABELS[$tag] ?? $tag;
            if (!isset($known[mb_strtolower($pName)])) {
                $known[mb_strtolower($pName)] = true;
                $params[] = ['name' => $pName, 'value' => mb_substr($value, 0, 255)];
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
        $rows = (static function () use ($stream, $delimiter) {
            while (($row = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
                yield $row;
            }
            fclose($stream);
        })();

        return self::parseTable($rows, 'csv');
    }

    /**
     * Таблица (CSV, лист XLSX): первая строка — заголовки колонок, остальные — товары.
     *
     * @param iterable<array> $rows
     */
    private static function parseTable(iterable $rows, string $format): array
    {
        $header = null;
        $map = [];
        $categories = [];
        $items = [];
        $line = 0;
        foreach ($rows as $row) {
            $line++;
            if ($header === null) {
                // Пустые строки над заголовком (бывают в выгрузках из Excel) пропускаем.
                if (!array_filter($row, static fn($v) => trim((string)$v) !== '')) {
                    continue;
                }
                $header = $row;
                $map = self::mapColumns($header);
                continue;
            }
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
        if ($header === null) {
            throw new \RuntimeException('Файл пустой: в первой строке должны быть названия колонок');
        }

        return ['format' => $format, 'categories' => array_values($categories), 'items' => $items];
    }

    private static function mapColumns(array $header): array
    {
        if (count(array_filter($header, static fn($v) => trim((string)$v) !== '')) < 2) {
            throw new \RuntimeException('Не удалось прочитать таблицу: в первой строке должны быть названия колонок');
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
            throw new \RuntimeException('В таблице нет колонки «Название»');
        }

        return $map;
    }

    /** «1 299,90», «1299.9 руб.» → 1299.9 */
    private static function number(string $value): float
    {
        $value = str_replace([' ', "\xC2\xA0", ','], ['', '', '.'], $value);

        return (float)preg_replace('/[^\d.]/', '', $value);
    }
}
