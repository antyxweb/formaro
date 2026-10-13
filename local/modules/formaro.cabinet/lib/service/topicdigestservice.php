<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CIBlock;
use CIBlockSection;
use Formaro\Cabinet\Mail\Mailer;
use Formaro\Cabinet\Repository\DiscountRepository;
use Formaro\Cabinet\Repository\HlblockEntityFactory;
use Formaro\Cabinet\Repository\ProductRepository;

/**
 * Еженедельный дайджест «Новинки и скидки» подписчикам тем «Будьте в курсе»
 * (тема — раздел каталога верхнего уровня, SubscriptionService::topics()):
 *   новинки — товары темы, добавленные с прошлого дайджеста;
 *   скидки  — товары темы под акциями, которые начались с прошлого дайджеста
 *             (действующие давно каждую неделю не повторяем).
 * Каждый адрес — одно письмо со всеми своими темами: до MAX_TOPICS тем —
 * блоками по темам, больше или «все темы» — общим списком без разбивки
 * (FLAT_LIMIT строк), чтобы письмо не растягивалось. Варианты одной модели
 * — одна строка. Нечего показать — не пишем. Подписки на партнёров и в подвале сюда не входят (им —
 * NewsMailingService).
 *
 * Агент раз в неделю, по понедельникам в 10:00 (миграция Version20261013170001).
 */
final class TopicDigestService
{
    public const EVENT = 'FORMARO_TOPIC_DIGEST';
    private const OPTION = 'topic_digest_sent_at';
    private const MODULE = 'formaro.cabinet';
    private const HLBLOCK = 'FormaroSubscriptions';
    private const PERIOD_DAYS = 7;
    /** Если агент долго не запускался — не тащим в письмо больше двух недель. */
    private const MAX_PERIOD_DAYS = 14;
    /** Строк в «Новинках» / «Скидках» одной темы. */
    private const LIMIT = 4;
    /** Больше тем с новым — письмо одним списком без разбивки по темам. */
    private const MAX_TOPICS = 3;
    /** Строк в «Новинках» / «Скидках» общего списка. */
    private const FLAT_LIMIT = 8;
    /** Сколько новинок выбирать, чтобы после склейки вариантов хватило строк. */
    private const CANDIDATES = 200;

    /** Агент Bitrix: возвращает себя — запускается снова. */
    public static function agent(): string
    {
        try {
            self::run();
        } catch (\Throwable $e) {
            // Агент не должен выпадать из очереди из-за одной ошибки.
        }

        return '\\' . self::class . '::agent();';
    }

    /** @return int сколько писем поставлено в очередь */
    public static function run(?DateTime $now = null): int
    {
        if (!Loader::includeModule('iblock')) {
            return 0;
        }
        $now = $now ? clone $now : new DateTime();
        $since = self::since($now);
        $count = self::send($since);
        Option::set(self::MODULE, self::OPTION, (string)$now->getTimestamp());

        return $count;
    }

    /**
     * Письма за период с $since; без отметки о дате отправки — для run() и проверки.
     *
     * @return int сколько писем поставлено в очередь
     */
    public static function send(DateTime $since): int
    {
        $subscribers = self::subscribers();
        if (!$subscribers) {
            return 0;
        }
        $topics = self::topics();
        $active = (new DiscountRepository())->listActive();
        $blocks = [];
        foreach ($topics as $id => $topic) {
            $html = self::blockHtml([$id], $since, $active, self::LIMIT, $topic);
            if ($html !== '') {
                $blocks[$id] = $html;
            }
        }
        if (!$blocks) {
            return 0;
        }

        $count = 0;
        $flat = [];
        foreach ($subscribers as $email => $topicIds) {
            $ids = isset($topicIds[0]) ? array_keys($blocks) : array_keys(array_intersect_key($blocks, $topicIds));
            if (!$ids) {
                continue;
            }
            if (count($ids) > self::MAX_TOPICS) {
                // Общий список по темам подписчика — один на одинаковый набор тем.
                $key = implode(',', $ids);
                $items = $flat[$key] ??= self::blockHtml($ids, $since, $active, self::FLAT_LIMIT);
            } else {
                $items = implode("\n", array_map(static fn(int $id) => $blocks[$id], $ids));
            }
            $count += (int)Mailer::send(self::EVENT, $email, [
                'SUBJECT' => count($ids) === 1
                    ? 'Новинки и скидки недели: ' . $topics[$ids[0]]['name']
                    : 'Новинки и скидки недели в Reppa',
                'ITEMS' => $items,
                'CATALOG_URL' => Mailer::url('/catalog/'),
                'UNSUBSCRIBE_URL' => MailingService::manageUrl($email),
            ]);
        }

        return $count;
    }

    /** Начало периода: прошлый дайджест, но не раньше MAX_PERIOD_DAYS; первый раз — неделя. */
    private static function since(DateTime $now): DateTime
    {
        $last = (int)Option::get(self::MODULE, self::OPTION, '0');
        $min = $now->getTimestamp() - self::MAX_PERIOD_DAYS * 86400;
        $ts = $last > 0 ? max($last, $min) : $now->getTimestamp() - self::PERIOD_DAYS * 86400;

        return DateTime::createFromTimestamp($ts);
    }

    /**
     * Подписчики тем: e-mail => [id темы => true]; тема 0 — «все темы».
     *
     * @return array<string, array<int, bool>>
     */
    private static function subscribers(): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK);
        $rows = $dataClass::getList([
            'select' => ['UF_EMAIL', 'UF_TOPIC_ID'],
            'filter' => [
                '=UF_FORM' => 'news',
                // У подписок до привязки к партнёрам поле пустое (NULL).
                ['LOGIC' => 'OR', ['=UF_PARTNER_ID' => 0], ['=UF_PARTNER_ID' => null]],
            ],
        ]);
        $list = [];
        while ($row = $rows->fetch()) {
            $email = MailingService::normalize((string)$row['UF_EMAIL']);
            if ($email !== '') {
                $list[$email][(int)$row['UF_TOPIC_ID']] = true;
            }
        }

        return $list;
    }

    /** @return array<int, array{name: string, url: string}> */
    private static function topics(): array
    {
        $names = SubscriptionService::topics();
        if (!$names) {
            return [];
        }
        $topics = [];
        $res = CIBlockSection::GetList([], ['ID' => array_keys($names), 'CHECK_PERMISSIONS' => 'N'], false, ['ID', 'IBLOCK_ID', 'CODE', 'SECTION_PAGE_URL']);
        $urls = [];
        while ($row = $res->GetNext(true, false)) {
            $urls[(int)$row['ID']] = (string)$row['SECTION_PAGE_URL'];
        }
        foreach ($names as $id => $name) {
            $topics[$id] = ['name' => $name, 'url' => Mailer::url($urls[$id] ?? '/catalog/')];
        }

        return $topics;
    }

    /**
     * Новинки и скидки разделов $sectionIds; $topic — блок одной темы (с
     * заголовком и ссылкой на раздел), без него — общий список.
     * '' — за период ничего нового.
     */
    private static function blockHtml(array $sectionIds, DateTime $since, array $active, int $limit, ?array $topic = null): string
    {
        $scope = ['SECTION_ID' => $sectionIds, 'INCLUDE_SUBSECTIONS' => 'Y'];

        // С запасом: варианты одной группы склеиваются в одну строку.
        $products = (new ProductRepository())->findPublic(
            $scope + ['>=DATE_CREATE' => $since->toString()],
            ['DATE_CREATE' => 'DESC', 'ID' => 'DESC'],
            self::CANDIDATES
        );
        $new = self::collapse(array_map(
            static fn(array $p) => ProductCardService::toItem($p, ProductPricingService::computeDisplay($p, $active)),
            $products
        ), $limit);

        $fresh = array_values(array_filter($active, static function (array $d) use ($since): bool {
            $started = max(
                $d['date_from'] ? (int)strtotime($d['date_from']) : 0,
                $d['created_at'] ? (int)strtotime($d['created_at']) : 0
            );

            return $started >= $since->getTimestamp();
        }));
        // Товар (или его группа) уже в «Новинках» — в «Скидках» не повторяем.
        $shown = array_flip(array_map([self::class, 'groupKey'], $new));
        $discounted = $fresh
            ? self::collapse(array_values(array_filter(
                ProductCarouselService::loadDiscounted($active, $scope, $fresh),
                static fn(array $item) => !isset($shown[self::groupKey($item)])
            )), $limit)
            : [];

        if (!$new && !$discounted) {
            return '';
        }

        $e = static fn($s) => htmlspecialcharsbx((string)$s);
        $html = $topic
            ? '<h2 style="margin:28px 0 4px;font-size:20px;line-height:26px;font-weight:bold;color:#111111;">' . $e($topic['name']) . '</h2>'
            : '<div style="height:12px;"></div>';
        if ($new) {
            $html .= self::listHtml('Новинки', $new);
        }
        if ($discounted) {
            $html .= self::listHtml('Скидки', $discounted);
        }
        if ($topic) {
            $html .= '<p style="margin:12px 0 0;"><a href="' . $e($topic['url']) . '" target="_blank" style="color:#1976d2;">Все товары раздела →</a></p>';
        }

        return $html;
    }

    /**
     * Варианты одной группы (VARIANT_GROUP_ID — цвета и размеры одной модели)
     * — одна строка: первый по порядку списка, VARIANTS — сколько всего
     * вариантов группы попало в список. Не больше $limit строк.
     */
    private static function collapse(array $items, int $limit): array
    {
        $rows = [];
        foreach ($items as $item) {
            $key = self::groupKey($item);
            if (isset($rows[$key])) {
                $rows[$key]['VARIANTS']++;
                continue;
            }
            if (count($rows) < $limit) {
                $rows[$key] = $item + ['VARIANTS' => 1];
            }
        }

        return array_values($rows);
    }

    private static function groupKey(array $item): string
    {
        return $item['VARIANT_GROUP_ID'] ? 'g' . $item['VARIANT_GROUP_ID'] : 'p' . $item['ID'];
    }

    /** Список товаров: картинка, название, цена (старая — зачёркнута). */
    private static function listHtml(string $title, array $items): string
    {
        $e = static fn($s) => htmlspecialcharsbx((string)$s);
        $fmt = static fn($n) => number_format((float)$n, 0, '', ' ') . ' ₽';
        $html = '<p style="margin:16px 0 8px;font-size:13px;line-height:20px;font-weight:bold;text-transform:uppercase;color:#6b7280;">' . $e($title) . '</p>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">';
        foreach ($items as $item) {
            $url = $e(Mailer::url($item['URL'] !== '#' ? $item['URL'] : '/catalog/'));
            $image = $item['IMAGE']
                ? '<a href="' . $url . '" target="_blank"><img src="' . $e(Mailer::url($item['IMAGE'])) . '" alt="" width="72" height="72" style="display:block;width:72px;height:72px;object-fit:cover;border:0;border-radius:6px;"></a>'
                : '';
            $price = ($item['OLD_PRICE'] ? '<s style="color:#9ca3af;">' . $fmt($item['OLD_PRICE']) . '</s> ' : '')
                . '<b style="color:#dc3545;">' . $fmt($item['PRICE']) . '</b>';
            $html .= '<tr>'
                . '<td width="84" valign="top" style="padding:0 12px 12px 0;">' . $image . '</td>'
                . '<td valign="top" style="padding:0 0 12px;">'
                . '<a href="' . $url . '" target="_blank" style="color:#111111;text-decoration:none;">' . $e($item['NAME']) . '</a>'
                . '<div style="margin-top:4px;">' . $price . '</div>'
                . ($item['VARIANTS'] > 1 ? '<div style="margin-top:2px;font-size:13px;color:#6b7280;">' . $e(self::variantsLabel($item['VARIANTS'])) . '</div>' : '')
                . '</td></tr>';
        }

        return $html . '</table>';
    }

    /** «3 варианта», «5 вариантов» — цвета и размеры одной модели. */
    private static function variantsLabel(int $n): string
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;
        $word = $mod10 === 1 && $mod100 !== 11 ? 'вариант'
            : ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) ? 'варианта' : 'вариантов');

        return $n . ' ' . $word . ' цвета и размера';
    }
}
