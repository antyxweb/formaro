<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Type\DateTime;
use CUser;
use Formaro\Cabinet\Repository\ChatRepository;
use Formaro\Cabinet\Repository\NotificationRepository;

/**
 * Уведомления о сообщениях чата покупателя с продавцом — только если
 * сообщение не прочитали в течение часа: продавцу (в кабинет, ссылка
 * chat/?thread=ID) — о сообщениях покупателя, покупателю (/personal/notify/,
 * ссылка на диалог) — об ответах продавца. Одно уведомление на диалог за
 * раз: все непрочитанные сообщения отправителя в диалоге помечаются
 * UF_NOTIFIED. Прочитал в течение часа — уведомления нет.
 *
 * Проверяет агент раз в 5 минут (миграция Version20261001180001).
 */
class ChatNotificationService
{
    public const DELAY_SECONDS = 3600;

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

    /** @return int сколько уведомлений отправлено */
    public static function run(?DateTime $now = null): int
    {
        $before = $now ? clone $now : new DateTime();
        $before->add('-' . self::DELAY_SECONDS . ' seconds');

        $repo = new ChatRepository();
        $groups = [];
        foreach ($repo->listUnnotifiedUnread($before) as $row) {
            $groups[$row['thread_id'] . ':' . $row['sender']][] = $row;
        }

        $sent = 0;
        foreach ($groups as $messages) {
            $thread = $repo->get($messages[0]['thread_id']);
            if ($thread) {
                $sent += self::notify($thread, $messages) ? 1 : 0;
            }
            // Все непрочитанные этого отправителя в диалоге — уже «уведомлены»,
            // чтобы следующий запуск не прислал второе уведомление о них же.
            $repo->markNotified($messages[0]['thread_id'], $messages[0]['sender']);
        }

        return $sent;
    }

    private static function notify(array $thread, array $messages): bool
    {
        $last = end($messages);
        $text = self::preview((string)$last['text'], $last['attachments']);
        if (count($messages) > 1) {
            $text = $text . ' (ещё ' . (count($messages) - 1) . ' ' . self::plural(count($messages) - 1) . ')';
        }
        $about = self::about($thread);

        if ($messages[0]['sender'] === 'client') {
            (new NotificationRepository())->add(
                $thread['partner_id'],
                'chat',
                'Сообщение от покупателя ' . $thread['client_name'] . $about,
                $text,
                'chat/?thread=' . $thread['thread_id']
            );

            return true;
        }

        if ($messages[0]['sender'] === 'partner' && !empty($thread['user_id'])) {
            $partner = CartCheckoutService::partners([$thread['partner_id']])[$thread['partner_id']] ?? ['name' => 'Продавец'];
            NotificationRepository::forBuyer()->addForBuyer(
                $thread['user_id'],
                'chat',
                'Новое сообщение от продавца ' . $partner['name'] . $about,
                $text,
                '/personal/messages/?thread=' . $thread['thread_id']
            );

            return true;
        }

        return false;
    }

    private static function about(array $thread): string
    {
        if ($thread['order_id'] !== '') {
            return ' по заказу ' . $thread['order_id'];
        }

        return $thread['product_name'] !== '' ? ' по товару «' . $thread['product_name'] . '»' : '';
    }

    private static function preview(string $text, array $attachments): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return $attachments ? 'Файл: ' . ($attachments[0]['name'] ?? '') : '';
        }

        return mb_strlen($text) > 120 ? mb_substr($text, 0, 117) . '…' : $text;
    }

    private static function plural(int $n): string
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;
        if ($mod10 === 1 && $mod100 !== 11) {
            return 'сообщение';
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return 'сообщения';
        }

        return 'сообщений';
    }
}
