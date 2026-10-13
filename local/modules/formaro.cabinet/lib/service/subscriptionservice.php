<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CIBlock;
use CIBlockSection;
use Formaro\Cabinet\Mail\Mailer;
use Formaro\Cabinet\Repository\HlblockEntityFactory;

/**
 * Подписка на рассылку из форм сайта (HL-блок FormaroSubscriptions,
 * миграция Version20261002100001; /local/ajax/subscribe.php):
 *   news   — «Будьте в курсе» (e-mail, тема — раздел каталога, согласие);
 *            в разделе партнёра — без темы, на обновления этого партнёра
 *            (UF_PARTNER_ID, миграция Version20261002120001);
 *   footer — «Будь всегда в форме!» в подвале (только e-mail).
 * Письма о новых новостях подписчикам — NewsMailingService. Повторная подписка того
 * же e-mail на ту же тему в той же форме новую запись не создаёт.
 * Отписка — «Управление рассылками» (MailingService, /subscriptions/,
 * блок в профиле и вкладка в кабинете партнёра) и кнопка «Отписаться» в
 * форме «Будьте в курсе» (unsubscribeFromForm()).
 */
class SubscriptionService
{
    public const FORMS = ['news', 'footer'];
    private const HLBLOCK = 'FormaroSubscriptions';
    private const CATALOG_IBLOCK = 'cabinet_catalog';
    private const PARTNERS_IBLOCK = 'cabinet_partners';

    /**
     * Темы — разделы каталога верхнего уровня (общие, не партнёрские).
     *
     * @return array<int, string> id => название
     */
    public static function topics(): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }
        $iblock = CIBlock::GetList([], ['=CODE' => self::CATALOG_IBLOCK, 'CHECK_PERMISSIONS' => 'N'])->Fetch();
        if (!$iblock) {
            return [];
        }
        $topics = [];
        $res = CIBlockSection::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            ['IBLOCK_ID' => $iblock['ID'], 'DEPTH_LEVEL' => 1, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'UF_PARTNER_ID' => false, 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['ID', 'NAME']
        );
        while ($row = $res->Fetch()) {
            $topics[(int)$row['ID']] = (string)$row['NAME'];
        }

        return $topics;
    }

    /**
     * Название партнёра для формы и подписки: краткое (NAME_SHORT), если
     * заполнено, иначе полное; '' — партнёра нет или он неактивен.
     */
    public static function partnerName(int $partnerId): string
    {
        if ($partnerId <= 0 || !Loader::includeModule('iblock')) {
            return '';
        }
        $row = \CIBlockElement::GetList(
            [],
            ['IBLOCK_CODE' => self::PARTNERS_IBLOCK, 'ID' => $partnerId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME', 'PROPERTY_NAME_SHORT']
        )->Fetch();
        if (!$row) {
            return '';
        }
        $short = trim((string)$row['PROPERTY_NAME_SHORT_VALUE']);

        return $short !== '' ? $short : (string)$row['NAME'];
    }

    /**
     * $partnerId — подписка на обновления партнёра (тема не выбирается).
     *
     * @return bool true — новая подписка, false — уже была
     * @throws \RuntimeException понятная пользователю ошибка
     */
    public static function subscribe(string $form, string $email, int $topicId = 0, bool $consent = false, string $page = '', int $partnerId = 0): bool
    {
        global $USER;
        $email = mb_strtolower(trim($email));
        if (!in_array($form, self::FORMS, true)) {
            throw new \RuntimeException('Неизвестная форма');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Проверьте e-mail');
        }
        if ($form === 'news' && !$consent) {
            throw new \RuntimeException('Нужно согласие с политикой обработки персональных данных');
        }
        $topicName = '';
        if ($partnerId) {
            $partnerName = self::partnerName($partnerId);
            if ($form !== 'news' || $partnerName === '') {
                throw new \RuntimeException('Не удалось оформить подписку, обновите страницу');
            }
            $topicId = 0;
            $topicName = 'Партнёр: ' . $partnerName;
        } elseif ($topicId) {
            $topics = self::topics();
            if (!isset($topics[$topicId])) {
                throw new \RuntimeException('Выберите тему из списка');
            }
            $topicName = $topics[$topicId];
        }

        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK);
        $exists = $dataClass::getList([
            'select' => ['ID'],
            'filter' => [
                '=UF_EMAIL' => $email,
                '=UF_FORM' => $form,
                '=UF_TOPIC_ID' => $topicId,
                // У подписок до привязки к партнёрам поле пустое (NULL).
                $partnerId ? ['=UF_PARTNER_ID' => $partnerId] : ['LOGIC' => 'OR', ['=UF_PARTNER_ID' => 0], ['=UF_PARTNER_ID' => null]],
            ],
            'limit' => 1,
        ])->fetch();
        if ($exists) {
            return false;
        }

        $result = $dataClass::add([
            'UF_EMAIL' => $email,
            'UF_FORM' => $form,
            'UF_TOPIC_ID' => $topicId,
            'UF_TOPIC_NAME' => $topicName,
            'UF_PARTNER_ID' => $partnerId,
            'UF_USER_ID' => (is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0,
            'UF_PAGE' => mb_substr($page, 0, 255),
            'UF_IP' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            'UF_CREATED_AT' => new DateTime(),
        ]);
        if (!$result->isSuccess()) {
            throw new \RuntimeException('Не удалось оформить подписку, попробуйте ещё раз');
        }

        return true;
    }

    /**
     * Подписки адреса для «Управления рассылками».
     *
     * @return array<array{id: int, title: string, created: string}>
     */
    public static function listFor(string $email): array
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') {
            return [];
        }
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK);
        $rows = $dataClass::getList([
            'select' => ['ID', 'UF_FORM', 'UF_TOPIC_ID', 'UF_TOPIC_NAME', 'UF_PARTNER_ID', 'UF_CREATED_AT'],
            'filter' => ['=UF_EMAIL' => $email],
            'order' => ['ID' => 'ASC'],
        ]);
        $list = [];
        while ($row = $rows->fetch()) {
            $list[] = [
                'id' => (int)$row['ID'],
                'title' => self::title($row),
                'created' => $row['UF_CREATED_AT'] instanceof DateTime ? $row['UF_CREATED_AT']->format('d.m.Y') : '',
            ];
        }

        return $list;
    }

    /** Отписка от одной подписки адреса; false — такой подписки нет. */
    public static function unsubscribe(string $email, int $id): bool
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK);
        $row = $dataClass::getList([
            'select' => ['ID'],
            'filter' => ['=ID' => $id, '=UF_EMAIL' => mb_strtolower(trim($email))],
            'limit' => 1,
        ])->fetch();
        if (!$row) {
            return false;
        }

        return $dataClass::delete($row['ID'])->isSuccess();
    }

    /** @return int сколько подписок удалено */
    public static function unsubscribeAll(string $email): int
    {
        $count = 0;
        foreach (self::listFor($email) as $item) {
            $count += (int)self::unsubscribe($email, $item['id']);
        }

        return $count;
    }

    /**
     * «Отписаться» в форме «Будьте в курсе»: на странице партнёра — от его
     * обновлений, на общих страницах — от общих подписок этой формы (все
     * темы). Вошедшего с этим же e-mail отписываем сразу. Иначе шлём на
     * адрес ссылку на «Управление рассылками», чтобы нельзя было отписать
     * чужой адрес. Ответ не выдаёт, подписан ли адрес.
     *
     * @return string сообщение для пользователя
     * @throws \RuntimeException понятная пользователю ошибка
     */
    public static function unsubscribeFromForm(string $form, string $email, int $partnerId = 0): string
    {
        global $USER;
        $email = mb_strtolower(trim($email));
        if (!in_array($form, self::FORMS, true)) {
            throw new \RuntimeException('Неизвестная форма');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Проверьте e-mail');
        }

        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK);
        $rows = $dataClass::getList([
            'select' => ['ID'],
            'filter' => [
                '=UF_EMAIL' => $email,
                '=UF_FORM' => $form,
                $partnerId > 0 ? ['=UF_PARTNER_ID' => $partnerId] : ['LOGIC' => 'OR', ['=UF_PARTNER_ID' => 0], ['=UF_PARTNER_ID' => null]],
            ],
        ]);
        $ids = [];
        while ($row = $rows->fetch()) {
            $ids[] = (int)$row['ID'];
        }

        $userEmail = (is_object($USER) && $USER->IsAuthorized()) ? mb_strtolower(trim((string)$USER->GetEmail())) : '';
        if ($userEmail !== '' && $userEmail === $email) {
            foreach ($ids as $id) {
                $dataClass::delete($id);
            }

            return $ids ? 'Вы отписались от рассылки.' : 'Этот адрес не подписан на рассылку.';
        }

        if ($ids && self::canSendManageLink($email)) {
            Mailer::send('FORMARO_SUBSCRIPTION_MANAGE', $email, [
                'MANAGE_URL' => MailingService::manageUrl($email),
            ]);
        }

        return 'Если адрес подписан, мы отправили на него письмо со ссылкой для отписки.';
    }

    /** Не чаще раза в 10 минут на адрес — чтобы формой не засыпать чужой ящик. */
    private static function canSendManageLink(string $email): bool
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache(600, 'manage_link_' . md5($email), '/formaro/subscription_manage')) {
            return false;
        }
        $cache->startDataCache();
        $cache->endDataCache(['sent' => time()]);

        return true;
    }

    /** Название подписки в списке. */
    private static function title(array $row): string
    {
        if ((string)$row['UF_FORM'] === 'footer') {
            return 'Новости и акции Reppa';
        }
        $partnerId = (int)$row['UF_PARTNER_ID'];
        if ($partnerId > 0) {
            $name = self::partnerName($partnerId)
                ?: preg_replace('/^Партнёр:\s*/u', '', (string)$row['UF_TOPIC_NAME']);

            return 'Новые предложения, акции и скидки поставщика ' . ($name !== '' ? '«' . $name . '»' : '#' . $partnerId);
        }
        $topic = trim((string)$row['UF_TOPIC_NAME']);

        return 'Будьте в курсе: ' . ($topic !== '' ? $topic : 'все темы');
    }
}
