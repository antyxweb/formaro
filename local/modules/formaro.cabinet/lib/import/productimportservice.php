<?php

namespace Formaro\Cabinet\Import;

use Bitrix\Main\Config\Option;
use Formaro\Cabinet\Repository\CategoryRepository;
use Formaro\Cabinet\Repository\ProductRepository;

/**
 * Импорт товаров партнёра из файла (YML/CSV) — раздел «Импорт» кабинета.
 *
 * 1. prepare(): файл (загрузка или ссылка на фид) разбирается FeedParser'ом,
 *    результат кладётся во временный JSON по токену, клиенту — сводка:
 *    категории файла для сопоставления с категориями площадки, пример товаров.
 * 2. run(): товары записываются порциями (offset/limit) — каждая порция
 *    отдельным запросом, чтобы не упираться в таймаут на тысячах товаров и
 *    скачивании картинок. Товар ищется среди своих по артикулу: есть —
 *    обновляем, нет — создаём.
 * 3. finish(): временный файл удаляется, сопоставление категорий
 *    запоминается для следующего импорта этого партнёра.
 */
class ProductImportService
{
    private const MODULE_ID = 'formaro.cabinet';
    private const MAX_FILE_BYTES = 50 * 1048576;
    private const MAX_IMAGE_BYTES = 10 * 1048576;
    private const MAX_PICTURES = 6;
    private const MAX_BATCH = 50;
    private const TTL = 86400;
    private const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const ALLOWED_TAGS = '<p><br><b><strong><i><em><u><ul><ol><li><h2><h3><h4><table><thead><tbody><tr><td><th>';

    /** Категория «не импортировать» в сопоставлении */
    public const SKIP = -1;

    public static function prepare(int $partnerId, ?array $upload, string $url): array
    {
        self::cleanup();
        $dir = self::dir();
        $token = bin2hex(random_bytes(16));
        $source = $dir . '/' . $token . '.src';

        if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
                throw new \RuntimeException('Файл не загрузился, попробуйте ещё раз');
            }
            if ($upload['size'] > self::MAX_FILE_BYTES) {
                throw new \RuntimeException('Файл больше 50 МБ');
            }
            move_uploaded_file($upload['tmp_name'], $source);
            $fileName = (string)$upload['name'];
        } elseif (trim($url) !== '') {
            $response = RemoteFetcher::fetch(trim($url), self::MAX_FILE_BYTES, 60);
            file_put_contents($source, $response['body']);
            $fileName = trim($url);
        } else {
            throw new \RuntimeException('Выберите файл или укажите ссылку на фид');
        }

        try {
            $feed = FeedParser::parse($source);
        } finally {
            @unlink($source);
        }
        if (!$feed['items']) {
            throw new \RuntimeException('В файле не найдено ни одного товара');
        }

        // Сколько товаров в каждой категории файла — пустые в сопоставлении не нужны.
        $counts = [];
        foreach ($feed['items'] as $item) {
            $counts[$item['category']] = ($counts[$item['category']] ?? 0) + 1;
        }
        $byId = array_column($feed['categories'], null, 'id');
        $categories = [];
        foreach ($counts as $id => $count) {
            $id = (string)$id;
            $categories[] = [
                'id' => $id,
                'name' => $id === '' ? 'Без категории' : ($byId[$id]['name'] ?? 'Категория ' . $id),
                'path' => $id === '' ? '' : self::categoryPath($byId, $id),
                'count' => $count,
            ];
        }

        // Полный список для просмотра на клиенте (с постраничным выводом) —
        // только то, что показывается в таблице, без описаний и параметров.
        $index = (new ProductRepository())->skuIndex($partnerId);
        $names = array_column($categories, 'name', 'id');
        $existing = 0;
        $preview = [];
        $props = [];
        foreach ($feed['items'] as $item) {
            foreach ($item['params'] as $param) {
                $props[$param['name']] = ($props[$param['name']] ?? 0) + 1;
            }
            if ($item['vendor'] !== '') {
                $props['Бренд'] = ($props['Бренд'] ?? 0) + 1;
            }
            $exists = $item['sku'] !== '' && isset($index[mb_strtolower($item['sku'])]);
            $existing += (int)$exists;
            $preview[] = [
                'sku' => $item['sku'], 'name' => $item['name'], 'price' => $item['price'], 'stock' => $item['stock'],
                'picture' => $item['pictures'][0] ?? '', 'category' => $names[$item['category']] ?? '', 'exists' => $exists,
            ];
        }

        self::write($token, [
            'partner_id' => $partnerId,
            'items' => $feed['items'],
            'groups' => [],
        ]);

        return [
            'token' => $token,
            'file' => $fileName,
            'format' => $feed['format'],
            'total' => count($feed['items']),
            'existing' => $existing,
            'limited' => count($feed['items']) >= FeedParser::MAX_ITEMS,
            'categories' => $categories,
            'items' => $preview,
            'props' => $props,
            'saved' => self::savedSettings($partnerId),
            'max_batch' => self::MAX_BATCH,
        ];
    }

    /**
     * @param array $options mapping {категория файла: ID категории | -1}, update_existing,
     *                       images, status (active|hidden), default_stock
     */
    public static function run(int $partnerId, string $token, int $offset, int $limit, array $options): array
    {
        $state = self::read($partnerId, $token);
        $items = array_slice($state['items'], max(0, $offset), max(1, min(self::MAX_BATCH, $limit)));

        $repo = new ProductRepository();
        $index = $repo->skuIndex($partnerId);
        $allowedCategories = array_column((new CategoryRepository())->listVisible($partnerId), 'id');
        $mapping = (array)($options['mapping'] ?? []);
        $updateExisting = !empty($options['update_existing']);
        $withImages = !empty($options['images']);
        $status = ($options['status'] ?? 'active') === 'hidden' ? 'hidden' : 'active';
        $defaultStock = max(0, (int)($options['default_stock'] ?? 0));

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [], 'warnings' => []];
        foreach ($items as $item) {
            try {
                $categoryId = (int)($mapping[$item['category']] ?? 0);
                if ($categoryId === self::SKIP) {
                    $stats['skipped']++;
                    continue;
                }
                if ($categoryId && !in_array($categoryId, $allowedCategories, true)) {
                    $categoryId = 0;
                }

                $skuKey = mb_strtolower($item['sku']);
                $existingId = $skuKey !== '' ? ($index[$skuKey] ?? 0) : 0;
                $existing = $existingId ? $repo->get($existingId) : null;
                if ($existing && !$updateExisting) {
                    $stats['skipped']++;
                    continue;
                }

                $stock = !$item['available'] ? 0 : ($item['stock'] ?? $defaultStock);
                $props = $item['params'];
                if ($item['vendor'] !== '') {
                    array_unshift($props, ['name' => 'Бренд', 'value' => $item['vendor']]);
                }
                $description = self::cleanHtml($item['description']);

                if ($existing) {
                    // Обновляем то, что пришло в файле; остальное (статус, теги,
                    // варианты, картинки) — как было у товара.
                    $payload = array_merge($existing, [
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'stock' => $stock,
                    ]);
                    if ($description !== '') {
                        $payload['full_desc'] = $description;
                    }
                    if ($categoryId) {
                        $payload['category_ids'] = [$categoryId];
                    }
                    foreach (['color', 'size'] as $key) {
                        if ($item[$key] !== '') {
                            $payload[$key] = $item[$key];
                        }
                    }
                    if ($props) {
                        $payload['custom_props'] = self::mergeProps((array)($existing['custom_props'] ?? []), $props);
                    }
                    $needImages = $withImages && empty($existing['preview_image']);
                } else {
                    $payload = [
                        'name' => $item['name'],
                        'slug' => $repo->uniqueSlug($item['name'], $item['sku']),
                        'sku' => $item['sku'],
                        'price' => $item['price'],
                        'stock' => $stock,
                        'status' => $status,
                        'short_desc' => '',
                        'full_desc' => $description,
                        'category_ids' => $categoryId ? [$categoryId] : [],
                        'color' => $item['color'],
                        'size' => $item['size'],
                        'custom_props' => self::mergeProps([], $props),
                        'gallery' => [],
                        'tags' => [],
                    ];
                    $needImages = $withImages;
                }

                if ($needImages && $item['pictures']) {
                    $images = self::downloadImages($item['pictures'], $stats['warnings'], $item);
                    if ($images) {
                        $payload['preview_image'] = array_shift($images);
                        $payload['gallery'] = $images;
                    }
                }

                $saved = $repo->save($partnerId, $payload);
                $existing ? $stats['updated']++ : $stats['created']++;
                if ($skuKey !== '') {
                    $index[$skuKey] = $saved['id'];
                }

                // group_id файла — варианты одного товара: группа = ID первого из них.
                if ($item['group_id'] !== '') {
                    $groupId = (int)($state['groups'][$item['group_id']] ?? $saved['id']);
                    $state['groups'][$item['group_id']] = $groupId;
                    if ($saved['variant_group_id'] !== $groupId) {
                        $repo->setVariantGroup($saved['id'], $groupId);
                    }
                }
            } catch (\Throwable $e) {
                $stats['errors'][] = ['sku' => $item['sku'], 'name' => $item['name'], 'error' => $e->getMessage()];
            }
        }

        if ($state['groups']) {
            self::write($token, $state);
        }

        $next = $offset + count($items);

        return $stats + ['next_offset' => $next, 'total' => count($state['items']), 'done' => $next >= count($state['items'])];
    }

    public static function finish(int $partnerId, string $token, array $options): bool
    {
        self::read($partnerId, $token);
        @unlink(self::dir() . '/' . $token . '.json');

        $saved = self::savedSettings($partnerId);
        // «+», не array_merge: ключи — ID категорий файла, array_merge перенумеровал бы числовые.
        $saved['mapping'] = (array)($options['mapping'] ?? []) + (array)($saved['mapping'] ?? []);
        foreach (['update_existing', 'images', 'status', 'default_stock', 'url'] as $key) {
            if (array_key_exists($key, $options)) {
                $saved[$key] = $options[$key];
            }
        }
        Option::set(self::MODULE_ID, 'import_settings_' . $partnerId, json_encode($saved, JSON_UNESCAPED_UNICODE));

        return true;
    }

    public static function savedSettings(int $partnerId): array
    {
        return json_decode((string)Option::get(self::MODULE_ID, 'import_settings_' . $partnerId, ''), true) ?: [];
    }

    /** @return string[] data:-URL'ы — так их принимает ProductRepository::save() */
    private static function downloadImages(array $urls, array &$warnings, array $item): array
    {
        $result = [];
        foreach (array_slice($urls, 0, self::MAX_PICTURES) as $url) {
            if (str_starts_with($url, '//')) {
                $url = 'https:' . $url;
            }
            try {
                $file = RemoteFetcher::fetch($url, self::MAX_IMAGE_BYTES, 20);
                $info = @getimagesizefromstring($file['body']);
                $mime = $info['mime'] ?? '';
                if (!in_array($mime, self::IMAGE_TYPES, true)) {
                    throw new \RuntimeException('не картинка');
                }
                $result[] = 'data:' . $mime . ';base64,' . base64_encode($file['body']);
            } catch (\Throwable $e) {
                if (count($warnings) < 100) {
                    $warnings[] = ['sku' => $item['sku'], 'name' => $item['name'], 'error' => 'Картинка ' . $url . ': ' . $e->getMessage()];
                }
            }
        }

        return $result;
    }

    /**
     * Дополнительные свойства товара + свойства из файла: совпадающие по названию
     * (без регистра) обновляются, новые добавляются, остальные у товара остаются.
     */
    private static function mergeProps(array $current, array $fromFile): array
    {
        $result = [];
        foreach (array_merge($current, $fromFile) as $prop) {
            $name = trim((string)($prop['name'] ?? ''));
            if ($name !== '') {
                $result[mb_strtolower($name)] = ['name' => $name, 'value' => (string)($prop['value'] ?? '')];
            }
        }

        return array_values($result);
    }

    /** Описание из чужого файла: только простая разметка, без атрибутов и скриптов. */
    private static function cleanHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe)\b[^>]*>.*?</\1>#is', '', $html);
        $html = strip_tags((string)$html, self::ALLOWED_TAGS);
        $html = preg_replace('#<(/?)(\w+)[^>]*>#', '<$1$2>', (string)$html);

        return trim((string)$html);
    }

    private static function categoryPath(array $byId, string $id): string
    {
        $names = [];
        $guard = 0;
        while ($id !== '' && isset($byId[$id]) && $guard++ < 10) {
            array_unshift($names, $byId[$id]['name']);
            $id = (string)$byId[$id]['parent_id'];
        }

        return implode(' / ', $names);
    }

    private static function dir(): string
    {
        $dir = rtrim(sys_get_temp_dir(), '/') . '/reppa_import';
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        return $dir;
    }

    private static function write(string $token, array $state): void
    {
        file_put_contents(self::dir() . '/' . $token . '.json', json_encode($state, JSON_UNESCAPED_UNICODE));
    }

    private static function read(int $partnerId, string $token): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            throw new \RuntimeException('Неверный идентификатор импорта');
        }
        $state = json_decode((string)@file_get_contents(self::dir() . '/' . $token . '.json'), true);
        if (!$state || (int)$state['partner_id'] !== $partnerId) {
            throw new \RuntimeException('Данные импорта устарели — загрузите файл заново');
        }

        return $state;
    }

    /** Брошенные импорты (закрыли вкладку на середине) — старше суток. */
    private static function cleanup(): void
    {
        foreach (glob(self::dir() . '/*') ?: [] as $file) {
            if (filemtime($file) < time() - self::TTL) {
                @unlink($file);
            }
        }
    }
}
