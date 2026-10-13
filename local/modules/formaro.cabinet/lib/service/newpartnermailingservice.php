<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CIBlock;
use CIBlockElement;
use Formaro\Cabinet\Catalog\CatalogUrl;
use Formaro\Cabinet\Mail\Mailer;
use Formaro\Cabinet\Repository\DiscountRepository;
use Formaro\Cabinet\Repository\ProductRepository;

/**
 * Письмо «На Reppa новый поставщик» всем общим подписчикам (те же, что
 * получают новости площадки — NewsMailingService::recipients(0)): когда
 * поставщик виден на витрине и загрузил первые товары.
 *
 * Агент раз в час (миграция Version20261013190001). Ждём PAUSE_HOURS после
 * первого товара — чтобы загрузка каталога успела закончиться и в письме
 * были товары. Если первый товар старше STALE_DAYS (поставщик давно
 * торгует, например его включили обратно) — не рассылаем. Разосланный или
 * пропущенный поставщик помечается свойством ANNOUNCED_AT — повторно не шлём.
 */
final class NewPartnerMailingService
{
    public const EVENT = 'FORMARO_NEW_PARTNER';
    public const PROPERTY = 'ANNOUNCED_AT';
    private const IBLOCK_CODE = 'cabinet_partners';
    private const PAUSE_HOURS = 3;
    private const STALE_DAYS = 14;
    /** Строк товаров в письме (варианты одной модели — одна строка). */
    private const LIMIT = 6;
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
        $iblockId = self::iblockId();
        if (!$iblockId) {
            return 0;
        }
        $now = $now ? clone $now : new DateTime();
        $readyBefore = $now->getTimestamp() - self::PAUSE_HOURS * 3600;
        $staleBefore = $now->getTimestamp() - self::STALE_DAYS * 86400;

        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'PROPERTY_' . self::PROPERTY => false, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID', 'IBLOCK_ID']
        );
        $partnerIds = [];
        while ($row = $res->Fetch()) {
            $partnerIds[] = (int)$row['ID'];
        }

        $repo = new ProductRepository();
        $sent = 0;
        foreach ($partnerIds as $partnerId) {
            $first = $repo->findPublic(['PROPERTY_PARTNER_ID' => $partnerId], ['DATE_CREATE' => 'ASC', 'ID' => 'ASC'], 1)[0] ?? null;
            $created = $first ? (int)MakeTimeStamp((string)$first['created_at']) : 0;
            if (!$first || $created > $readyBefore) {
                continue; // товаров ещё нет или загрузка идёт
            }
            // Сначала отметка — сбой посреди отправки не приведёт к повторной рассылке.
            CIBlockElement::SetPropertyValuesEx($partnerId, $iblockId, [self::PROPERTY => $now->toString()]);
            if ($created < $staleBefore) {
                continue;
            }
            $sent += self::send($partnerId);
        }

        return $sent;
    }

    /** Письма о поставщике общим подписчикам; @return int сколько поставлено в очередь */
    public static function send(int $partnerId): int
    {
        $name = SubscriptionService::partnerName($partnerId);
        $home = CatalogUrl::partnerHome($partnerId);
        if ($name === '' || $home === '') {
            return 0; // поставщик скрыт с витрины
        }
        $partner = CIBlockElement::GetList(
            [],
            ['ID' => $partnerId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['nTopCount' => 1],
            ['ID', 'IBLOCK_ID', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PREVIEW_PICTURE']
        )->Fetch();

        $active = (new DiscountRepository())->listActive();
        $products = (new ProductRepository())->findPublic(['PROPERTY_PARTNER_ID' => $partnerId], ['DATE_CREATE' => 'DESC', 'ID' => 'DESC'], self::CANDIDATES);
        $items = TopicDigestService::collapse(array_map(
            static fn(array $p) => ProductCardService::toItem($p, ProductPricingService::computeDisplay($p, $active)),
            $products
        ), self::LIMIT);
        if (!$items) {
            return 0;
        }

        $logoPath = !empty($partner['PREVIEW_PICTURE']) ? (string)\CFile::GetPath((int)$partner['PREVIEW_PICTURE']) : '';
        $fields = [
            'PARTNER_NAME' => $name,
            'TEXT' => self::text($partner ?: []),
            'LOGO' => $logoPath !== ''
                ? '<img src="' . htmlspecialcharsbx(Mailer::url($logoPath)) . '" alt="" width="96" style="display:block;width:96px;max-width:96px;height:auto;margin:0 0 16px;border:0;">'
                : '',
            'ITEMS' => TopicDigestService::listHtml('Первые товары', $items),
            'PARTNER_URL' => Mailer::url($home),
        ];

        $count = 0;
        foreach (NewsMailingService::recipients(0) as $email) {
            $count += (int)Mailer::send(self::EVENT, $email, $fields + ['UNSUBSCRIBE_URL' => MailingService::manageUrl($email)]);
        }

        return $count;
    }

    /** Описание поставщика: краткое, без него — начало полного. */
    private static function text(array $partner): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string)($partner['PREVIEW_TEXT'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($text === '') {
            $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string)($partner['DETAIL_TEXT'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        }
        if (mb_strlen($text) > 300) {
            $text = rtrim(mb_substr($text, 0, 300)) . '…';
        }

        return $text;
    }

    private static function iblockId(): int
    {
        $iblock = CIBlock::GetList([], ['=CODE' => self::IBLOCK_CODE, 'CHECK_PERMISSIONS' => 'N'])->Fetch();

        return $iblock ? (int)$iblock['ID'] : 0;
    }
}
