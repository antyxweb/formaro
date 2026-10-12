<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\DateTime;
use Formaro\Cabinet\Settings\MarketplaceRequisites;
use Formaro\Cabinet\Upload\FileUploader;

/**
 * Обращения в техподдержку — HL-блоки CabinetTickets (сам тикет) +
 * CabinetTicketMessages (переписка, отдельные строки — см. миграцию
 * Version20260912130001). Формат возвращаемых тикетов совпадает с
 * cabinet-html/data/tickets.json ({id, subject, status, created_at,
 * messages: [{sender, text, date, attachments}]}), чтобы support.js/
 * support-detail.js работали почти без переделки — отличие только в том,
 * что вложения теперь настоящие файлы (CFile), а не base64/заглушки.
 *
 * marketplace_contacts из прототипа — статичные реквизиты площадки, не
 * партнёрские данные и не отдельная сущность с CRUD — вынесены константой
 * (см. getMarketplaceContacts()), реальные значения перенесены из
 * cabinet-html/data/tickets.json как есть.
 */
class TicketRepository
{
    private const TICKETS_HLBLOCK = 'CabinetTickets';
    private const MESSAGES_HLBLOCK = 'CabinetTicketMessages';
    private const UPLOAD_SUBDIR = 'cabinet/tickets';

    public function listOwn(int $partnerId): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::TICKETS_HLBLOCK);
        $rows = $dataClass::getList([
            'filter' => ['=UF_PARTNER_ID' => $partnerId],
            'order' => ['ID' => 'DESC'],
        ])->fetchAll();

        return array_map(fn(array $row) => $this->toArray($row), $rows);
    }

    /** Обращения всех партнёров — кабинет администратора (техподдержка площадки). */
    public function listAll(): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::TICKETS_HLBLOCK);
        $rows = $dataClass::getList(['order' => ['ID' => 'DESC']])->fetchAll();

        return array_map(fn(array $row) => $this->toArray($row), $rows);
    }

    /**
     * Ответ техподдержки площадки (sender = support): обращение — «Отвечен»,
     * партнёру — уведомление со ссылкой на обращение.
     *
     * @param array $attachments [{name, type, data}]
     */
    public function replyAsSupport(int $ticketId, string $text, array $attachments = []): array
    {
        $ticket = $this->get($ticketId);
        if (!$ticket) {
            throw new \RuntimeException('Обращение не найдено');
        }
        if (trim($text) === '' && !$attachments) {
            throw new \RuntimeException('Введите сообщение');
        }

        $this->addMessage($ticketId, 'support', $text, $attachments);
        $dataClass = HlblockEntityFactory::getDataClass(self::TICKETS_HLBLOCK);
        $dataClass::update($ticketId, ['UF_STATUS' => 'answered']);

        (new NotificationRepository())->add(
            $ticket['partner_id'],
            'support',
            'Ответ техподдержки',
            'По обращению «' . $ticket['subject'] . '»: ' . mb_strimwidth(trim($text) ?: 'вложение', 0, 150, '…'),
            'support/' . $ticketId . '/'
        );

        return $this->get($ticketId);
    }

    /** Статус обращения из кабинета администратора: open / answered / closed. */
    public function setStatus(int $ticketId, string $status): array
    {
        if (!in_array($status, ['open', 'answered', 'closed'], true)) {
            throw new \RuntimeException('Неизвестный статус');
        }
        $ticket = $this->get($ticketId);
        if (!$ticket) {
            throw new \RuntimeException('Обращение не найдено');
        }
        $dataClass = HlblockEntityFactory::getDataClass(self::TICKETS_HLBLOCK);
        $dataClass::update($ticketId, ['UF_STATUS' => $status]);
        if ($status === 'closed' && $ticket['status'] !== 'closed') {
            (new NotificationRepository())->add(
                $ticket['partner_id'],
                'support',
                'Обращение закрыто',
                'Обращение «' . $ticket['subject'] . '» закрыто. Если вопрос остался — ответьте в нём, и оно откроется снова.',
                'support/' . $ticketId . '/'
            );
        }

        return $this->get($ticketId);
    }

    /** Удалить своё сообщение техподдержки (sender = support). */
    public function deleteSupportMessage(int $ticketId, int $messageId): array
    {
        $messagesClass = HlblockEntityFactory::getDataClass(self::MESSAGES_HLBLOCK);
        $row = $messagesClass::getList(['filter' => ['=ID' => $messageId, '=UF_TICKET_ID' => $ticketId, '=UF_SENDER' => 'support']])->fetch();
        if (!$row) {
            throw new \RuntimeException('Сообщение не найдено');
        }
        foreach ((array)($row['UF_ATTACHMENTS'] ?? []) as $fileId) {
            FileUploader::delete((int)$fileId);
        }
        $messagesClass::delete($messageId);

        return $this->get($ticketId);
    }

    public function get(int $id): ?array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::TICKETS_HLBLOCK);
        $row = $dataClass::getById($id)->fetch();

        return $row ? $this->toArray($row) : null;
    }

    public function canEdit(int $partnerId, array $ticket): bool
    {
        return $ticket['partner_id'] === $partnerId;
    }

    /** @param array $attachments [{name, type, data}] — data:-URL, см. attachmentFromFile() в common.js */
    public function create(int $partnerId, string $subject, string $text, array $attachments = []): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::TICKETS_HLBLOCK);
        $result = $dataClass::add([
            'UF_SUBJECT' => $subject,
            'UF_STATUS' => 'open',
            'UF_PARTNER_ID' => $partnerId,
            'UF_CREATED_AT' => new DateTime(),
        ]);
        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }
        $ticketId = $result->getId();

        $this->addMessage($ticketId, 'partner', $text, $attachments);
        NotificationRepository::notifyAdmin(
            'support',
            'Новое обращение в техподдержку',
            PartnerRepository::nameOf($partnerId) . ' — «' . $subject . '»: ' . mb_strimwidth(trim($text), 0, 150, '…'),
            'support/' . $ticketId . '/'
        );

        return $this->get($ticketId);
    }

    /** @param array $attachments [{name, type, data}] */
    public function reply(int $partnerId, int $ticketId, string $text, array $attachments = []): array
    {
        $ticket = $this->get($ticketId);
        if (!$ticket || !$this->canEdit($partnerId, $ticket)) {
            throw new \RuntimeException('Обращение не найдено или недоступно');
        }

        $this->addMessage($ticketId, 'partner', $text, $attachments);

        // Ответ партнёра — снова ждёт техподдержку (в т.ч. «Отвечен» и «Закрыт»).
        if ($ticket['status'] !== 'open') {
            $dataClass = HlblockEntityFactory::getDataClass(self::TICKETS_HLBLOCK);
            $dataClass::update($ticketId, ['UF_STATUS' => 'open']);
        }
        NotificationRepository::notifyAdmin(
            'support',
            'Ответ партнёра в обращении',
            PartnerRepository::nameOf($partnerId) . ' — «' . $ticket['subject'] . '»: ' . mb_strimwidth(trim($text) ?: 'вложение', 0, 150, '…'),
            'support/' . $ticketId . '/'
        );

        return $this->get($ticketId);
    }

    /** @throws \Exception если тикет чужой/не найден, или сообщение чужого тикета */
    public function deleteMessage(int $partnerId, int $ticketId, int $messageId): array
    {
        $ticket = $this->get($ticketId);
        if (!$ticket || !$this->canEdit($partnerId, $ticket)) {
            throw new \RuntimeException('Обращение не найдено или недоступно');
        }

        $messagesClass = HlblockEntityFactory::getDataClass(self::MESSAGES_HLBLOCK);
        $row = $messagesClass::getList(['filter' => ['=ID' => $messageId, '=UF_TICKET_ID' => $ticketId]])->fetch();
        if (!$row) {
            throw new \RuntimeException('Сообщение не найдено');
        }

        foreach ((array)($row['UF_ATTACHMENTS'] ?? []) as $fileId) {
            FileUploader::delete((int)$fileId);
        }
        $messagesClass::delete($messageId);

        return $this->get($ticketId);
    }

    public function getMarketplaceContacts(): array
    {
        $requisites = MarketplaceRequisites::get();

        return [
            'support_phone' => '8 800 550-38-40',
            'phone_note' => 'бесплатно по РФ',
            'support_email' => 'partner@formaro.ru',
            'email_note' => 'ответ в течение 2 часов',
            'help_url' => 'help.reppaopt.ru',
            'help_note' => 'инструкции и регламенты',
            'work_hours' => 'Поддержка партнёров: пн–пт, 9:00–19:00 МСК',
            // Реквизиты — из настроек модуля (MarketplaceRequisites).
            'legal_name' => $requisites['name_short'] ?: $requisites['name_full'],
            'inn' => $requisites['inn'],
            'kpp' => $requisites['kpp'],
            'ogrn' => $requisites['ogrn'],
            'legal_address' => $requisites['legal_address'] ?: $requisites['actual_address'],
            'bank_name' => $requisites['bank_name'],
            'account' => $requisites['account'],
        ];
    }

    private function addMessage(int $ticketId, string $sender, string $text, array $attachments): void
    {
        $messagesClass = HlblockEntityFactory::getDataClass(self::MESSAGES_HLBLOCK);
        $result = $messagesClass::add([
            'UF_TICKET_ID' => $ticketId,
            'UF_SENDER' => $sender,
            'UF_TEXT' => $text,
            'UF_DATE' => new DateTime(),
            'UF_ATTACHMENTS' => $this->saveAttachments($attachments),
        ]);
        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }
    }

    /** @return array[] файловые массивы для UF_ATTACHMENTS (см. FileUploader::fileArraysFromDataUrls()) */
    private function saveAttachments(array $attachments): array
    {
        return FileUploader::fileArraysFromDataUrls($attachments, self::UPLOAD_SUBDIR);
    }

    private function toArray(array $ticket): array
    {
        $messagesClass = HlblockEntityFactory::getDataClass(self::MESSAGES_HLBLOCK);
        $messageRows = $messagesClass::getList([
            'filter' => ['=UF_TICKET_ID' => $ticket['ID']],
            'order' => ['UF_DATE' => 'ASC', 'ID' => 'ASC'],
        ])->fetchAll();

        return [
            'id' => (int)$ticket['ID'],
            'subject' => $ticket['UF_SUBJECT'],
            'status' => $ticket['UF_STATUS'],
            'partner_id' => (int)$ticket['UF_PARTNER_ID'],
            'created_at' => $this->dateToString($ticket['UF_CREATED_AT']),
            'messages' => array_map(function (array $m) {
                return [
                    'id' => (int)$m['ID'],
                    'sender' => $m['UF_SENDER'],
                    'text' => $m['UF_TEXT'],
                    'date' => $this->dateToString($m['UF_DATE']),
                    'attachments' => array_values(array_filter(array_map(
                        static fn($fid) => FileUploader::getAttachment((int)$fid),
                        (array)($m['UF_ATTACHMENTS'] ?? [])
                    ))),
                ];
            }, $messageRows),
        ];
    }

    private function dateToString($value): ?string
    {
        return $value instanceof DateTime ? $value->format('c') : ($value ? (string)$value : null);
    }
}
