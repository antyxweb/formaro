<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CIBlock;
use CIBlockElement;
use Formaro\Cabinet\Mail\Mailer;
use Formaro\Cabinet\Repository\HlblockEntityFactory;

/**
 * Письма подписчикам о новой новости (инфоблок cabinet_news):
 *   новость площадки (без партнёра) — всем общим подписчикам: «Будьте в
 *   курсе» на общих страницах (любая тема) и «Будь всегда в форме!» в подвале;
 *   новость партнёра — только подписавшимся на этого партнёра.
 * Каждый адрес получает письмо один раз, со ссылкой «Управление рассылками».
 *
 * Отправляет агент раз в 10 минут (миграция Version20261013150001). Новость
 * уходит, когда она активна, дата публикации наступила и её не правили
 * PAUSE_MINUTES — чтобы успеть исправить опечатку. Новости с датой
 * публикации старше STALE_DAYS (задним числом, архив) не рассылаются.
 * Разосланная или пропущенная новость помечается свойством MAILED_AT —
 * повторно, в том числе после правки, её не шлём.
 */
final class NewsMailingService
{
    public const EVENT = 'FORMARO_NEWS';
    public const PROPERTY = 'MAILED_AT';
    private const IBLOCK_CODE = 'cabinet_news';
    private const HLBLOCK = 'FormaroSubscriptions';
    private const PAUSE_MINUTES = 15;
    private const STALE_DAYS = 3;
    /** Новостей за один запуск агента — остальные в следующий. */
    private const BATCH = 5;

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
        $editedBefore = (clone $now)->add('-' . self::PAUSE_MINUTES . ' minutes');
        $staleBefore = (clone $now)->add('-' . self::STALE_DAYS . ' days');

        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
                '<=TIMESTAMP_X' => $editedBefore->toString(),
                'PROPERTY_' . self::PROPERTY => false,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            ['nTopCount' => self::BATCH],
            ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'ACTIVE_FROM', 'DATE_CREATE', 'DETAIL_PAGE_URL', 'PROPERTY_PARTNER_ID']
        );
        $news = [];
        while ($row = $res->GetNext(true, false)) {
            $news[] = $row;
        }

        $sent = 0;
        foreach ($news as $row) {
            // Сначала отметка — сбой посреди отправки не приведёт к повторной рассылке.
            CIBlockElement::SetPropertyValuesEx((int)$row['ID'], $iblockId, [self::PROPERTY => $now->toString()]);
            $published = (int)MakeTimeStamp((string)($row['ACTIVE_FROM'] ?: $row['DATE_CREATE']));
            if ($published && $published < $staleBefore->getTimestamp()) {
                continue;
            }
            $sent += self::send($row);
        }

        return $sent;
    }

    /** Письма о новости её подписчикам; @return int сколько поставлено в очередь */
    public static function send(array $row): int
    {
        $partnerId = (int)($row['PROPERTY_PARTNER_ID_VALUE'] ?? 0);
        $partnerName = $partnerId ? SubscriptionService::partnerName($partnerId) : '';
        if ($partnerId && $partnerName === '') {
            return 0; // партнёр скрыт с витрины
        }

        $url = (string)($row['DETAIL_PAGE_URL'] ?? '');
        $picture = (int)($row['PREVIEW_PICTURE'] ?: $row['DETAIL_PICTURE']);
        $imagePath = $picture ? (string)\CFile::GetPath($picture) : '';
        $fields = [
            'TITLE' => (string)$row['NAME'],
            'TEXT' => self::text($row),
            'FROM' => $partnerName !== '' ? 'Новость поставщика «' . $partnerName . '»' : 'Новости Reppa',
            'IMAGE' => $imagePath !== ''
                ? '<img src="' . htmlspecialcharsbx(Mailer::url($imagePath)) . '" alt="" width="520" style="display:block;width:100%;max-width:520px;height:auto;margin:0 0 20px;border:0;border-radius:6px;">'
                : '',
            'NEWS_URL' => $url !== '' ? Mailer::url($url) : Mailer::url('/news/'),
        ];

        $count = 0;
        foreach (self::recipients($partnerId) as $email) {
            $count += (int)Mailer::send(self::EVENT, $email, $fields + ['UNSUBSCRIBE_URL' => MailingService::manageUrl($email)]);
        }

        return $count;
    }

    /**
     * Адреса подписчиков: партнёра — его подписка, площадки — все общие.
     *
     * @return string[]
     */
    public static function recipients(int $partnerId): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK);
        $rows = $dataClass::getList([
            'select' => ['UF_EMAIL'],
            // У подписок до привязки к партнёрам поле пустое (NULL).
            'filter' => $partnerId > 0
                ? ['=UF_PARTNER_ID' => $partnerId]
                : ['LOGIC' => 'OR', ['=UF_PARTNER_ID' => 0], ['=UF_PARTNER_ID' => null]],
        ]);
        $emails = [];
        while ($row = $rows->fetch()) {
            $email = MailingService::normalize((string)$row['UF_EMAIL']);
            if ($email !== '') {
                $emails[$email] = true;
            }
        }

        return array_keys($emails);
    }

    /** Анонс: краткое описание, без него — начало полного текста. */
    private static function text(array $row): string
    {
        $text = trim((string)$row['PREVIEW_TEXT']);
        if ($text === '') {
            $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string)$row['DETAIL_TEXT']), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            if (mb_strlen($text) > 300) {
                $text = rtrim(mb_substr($text, 0, 300)) . '…';
            }
        }

        return $text;
    }

    private static function iblockId(): int
    {
        $iblock = CIBlock::GetList([], ['=CODE' => self::IBLOCK_CODE, 'CHECK_PERMISSIONS' => 'N'])->Fetch();

        return $iblock ? (int)$iblock['ID'] : 0;
    }
}
