<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CIBlock;
use CIBlockSection;
use Formaro\Cabinet\Repository\HlblockEntityFactory;

/**
 * Подписка на рассылку из форм сайта (HL-блок FormaroSubscriptions,
 * миграция Version20261002100001; /local/ajax/subscribe.php):
 *   news   — «Будьте в курсе» (e-mail, тема — раздел каталога, согласие);
 *   footer — «Будь всегда в форме!» в подвале (только e-mail).
 * Рассылки пока нет — подписки копятся в HL-блоке. Повторная подписка того
 * же e-mail на ту же тему в той же форме новую запись не создаёт.
 */
class SubscriptionService
{
    public const FORMS = ['news', 'footer'];
    private const HLBLOCK = 'FormaroSubscriptions';
    private const CATALOG_IBLOCK = 'cabinet_catalog';

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
     * @return bool true — новая подписка, false — уже была
     * @throws \RuntimeException понятная пользователю ошибка
     */
    public static function subscribe(string $form, string $email, int $topicId = 0, bool $consent = false, string $page = ''): bool
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
        if ($topicId) {
            $topics = self::topics();
            if (!isset($topics[$topicId])) {
                throw new \RuntimeException('Выберите тему из списка');
            }
            $topicName = $topics[$topicId];
        }

        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK);
        $exists = $dataClass::getList([
            'select' => ['ID'],
            'filter' => ['=UF_EMAIL' => $email, '=UF_FORM' => $form, '=UF_TOPIC_ID' => $topicId],
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
}
