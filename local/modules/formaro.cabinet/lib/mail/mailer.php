<?php

namespace Formaro\Cabinet\Mail;

use Formaro\Cabinet\Service\MailingService;
use Formaro\Cabinet\Settings\MailSettings;

/**
 * Письма сайта — только свои почтовые события FORMARO_* (стандартные
 * NEW_USER, USER_PASS_REQUEST и т.п. не используются). Тексты писем —
 * почтовые шаблоны этих событий (Настройки → Почтовые события, миграция
 * Version20261008130001), оформление — шаблон сайта mail_formaro: шапка с
 * логотипом и подвал с реквизитами площадки (local/templates/mail_formaro).
 *
 * Уведомления кабинетов дублируются письмом (notification(), вызывает
 * NotificationRepository при каждой записи в ленту): событие
 * FORMARO_<КОМУ>_<ТИП> — например FORMARO_PARTNER_ORDER. Отдельные письма
 * со своим текстом — регистрация, восстановление пароля, заказ оформлен,
 * счёт выставлен / аннулирован (EVENTS).
 *
 * Во всех письмах есть поля #EMAIL#, #NAME#, #GREETING# («Здравствуйте,
 * Анна!») и #SITE_URL#; в HTML-шаблоне текст — через #HTML_ПОЛЕ#
 * (экранированный, переносы строк — <br>).
 *
 * Уведомления покупателю и поставщику — с #UNSUBSCRIBE_URL# («Управление
 * рассылками», MailingService): ссылка в подвале письма и заголовок
 * List-Unsubscribe. Отключённые получателем категории не отправляются.
 */
final class Mailer
{
    public const SITE_ID = 's1';

    /**
     * Уведомления кабинетов: кому => [тип уведомления => название события].
     * Тип без своего события — письмо SYSTEM.
     */
    public const NOTIFICATIONS = [
        'buyer' => [
            'order' => 'Заказы',
            'chat' => 'Сообщения от продавцов',
            'system' => 'Прочие уведомления',
        ],
        'partner' => [
            'order' => 'Заказы',
            'chat' => 'Сообщения от покупателей',
            'support' => 'Техподдержка',
            'finance' => 'Финансы и выплаты',
            'product' => 'Товары и категории',
            'system' => 'Профиль и прочие уведомления',
        ],
        'admin' => [
            'order' => 'Заказы',
            'chat' => 'Сообщения в чатах',
            'support' => 'Техподдержка',
            'finance' => 'Финансы и выплаты',
            'moderation' => 'Модерация',
            'system' => 'Прочие уведомления',
        ],
    ];

    public const AUDIENCES = [
        'buyer' => 'покупателю',
        'partner' => 'поставщику',
        'admin' => 'администраторам площадки',
    ];

    /** Подпись кнопки в письме по типу уведомления. */
    private const BUTTONS = [
        'order' => 'Открыть заказ',
        'chat' => 'Перейти к переписке',
        'support' => 'Открыть обращение',
        'finance' => 'Открыть финансы',
        'product' => 'Открыть в кабинете',
        'moderation' => 'Перейти к проверке',
    ];

    private static bool $muted = false;

    /** Не отправлять письма (демо-данные, тесты). */
    public static function mute(bool $muted = true): void
    {
        self::$muted = $muted;
    }

    public static function notificationEvent(string $audience, string $type): string
    {
        $type = isset(self::NOTIFICATIONS[$audience][$type]) ? $type : 'system';

        return 'FORMARO_' . strtoupper($audience) . '_' . strtoupper($type);
    }

    /**
     * Уведомление из ленты кабинета — письмом. $ownerId — id пользователя
     * (buyer) или партнёра (partner), для admin не используется. $link —
     * как в ленте: адрес на сайте (/personal/…) или путь от корня
     * кабинета (orders/edit/5/).
     */
    public static function notification(string $audience, string $type, int $ownerId, string $title, string $message, string $link = ''): void
    {
        if (self::$muted || !isset(self::AUDIENCES[$audience])) {
            return;
        }
        try {
            $recipients = match ($audience) {
                'buyer' => self::users(['=ID' => $ownerId]),
                'partner' => $ownerId > 0 ? self::users(['=UF_CABINET_PARTNER_ID' => $ownerId]) : [],
                'admin' => array_fill_keys(MailSettings::adminEmails(), ''),
            };
            $fields = [
                'TITLE' => $title,
                'MESSAGE' => $message,
                'LINK_URL' => self::url(self::cabinetLink($audience, $link)),
                'LINK_TEXT' => self::BUTTONS[$type] ?? 'Подробнее',
            ];
            $event = self::notificationEvent($audience, $type);
            // Покупатель и поставщик могут отказаться от категории писем
            // (MailingService). Каждое такое письмо — со ссылкой на настройки.
            $optional = in_array($audience, MailingService::AUDIENCES, true);
            $channel = MailingService::channel($audience, isset(self::NOTIFICATIONS[$audience][$type]) ? $type : 'system');
            $disabled = $optional ? MailingService::disabledFor(array_keys($recipients)) : [];
            foreach ($recipients as $email => $name) {
                if (in_array($channel, $disabled[MailingService::normalize($email)] ?? [], true)) {
                    continue;
                }
                $extra = $optional ? ['UNSUBSCRIBE_URL' => MailingService::manageUrl($email, $channel)] : [];
                self::send($event, $email, $fields + $extra + ['NAME' => $name]);
            }
        } catch (\Throwable $e) {
            // Письмо не должно мешать действию, из-за которого уведомляем.
        }
    }

    /**
     * Письмо события $event на $email. Ошибка отправки не прерывает
     * действие пользователя — вернётся false.
     */
    public static function send(string $event, string $email, array $fields = []): bool
    {
        $email = trim($email);
        if (self::$muted || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $name = trim((string)($fields['NAME'] ?? ''));
        $fields = array_map(static fn($value) => (string)$value, $fields);
        $fields['EMAIL'] = $email;
        $fields['NAME'] = $name;
        $fields['GREETING'] = $name !== '' ? 'Здравствуйте, ' . $name . '!' : 'Здравствуйте!';
        $fields['SITE_URL'] = MailSettings::siteUrl();
        try {
            return (bool)\CEvent::Send($event, self::SITE_ID, $fields);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Письмо пользователю сайта по id — e-mail и имя из учётной записи. */
    public static function sendToUser(string $event, int $userId, array $fields = []): bool
    {
        foreach (self::users(['=ID' => $userId]) as $email => $name) {
            return self::send($event, $email, $fields + ['NAME' => $name]);
        }

        return false;
    }

    /**
     * Ссылка на смену пароля: новое контрольное слово пользователя и письмо
     * FORMARO_USER_PASS_REQUEST (вместо стандартного USER_PASS_REQUEST).
     * Ссылка ведёт на форму смены пароля в профиле (formaro:auth.form).
     */
    public static function passwordRequest(int $userId): bool
    {
        $user = \CUser::GetByID($userId)->Fetch();
        if (!$user || $user['ACTIVE'] !== 'Y' || !empty($user['EXTERNAL_AUTH_ID'])) {
            return false;
        }
        $checkword = \Bitrix\Main\Security\Random::getString(32);
        if (!(new \CUser())->Update($userId, ['CHECKWORD' => $checkword])) {
            return false;
        }
        $query = http_build_query([
            'change_password' => 'yes',
            'USER_LOGIN' => $user['LOGIN'],
            'USER_CHECKWORD' => $checkword,
        ]);

        return self::send('FORMARO_USER_PASS_REQUEST', (string)$user['EMAIL'], [
            'NAME' => (string)$user['NAME'],
            'LOGIN' => (string)$user['LOGIN'],
            'CHANGE_URL' => self::url('/personal/profile/?' . $query),
        ]);
    }

    /** Абсолютный адрес на сайте для письма: /path → https://сайт/path. */
    public static function url(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return MailSettings::siteUrl() . '/' . ltrim($path, '/');
    }

    /** Ссылка уведомления → адрес на сайте; пусто — лента уведомлений. */
    private static function cabinetLink(string $audience, string $link): string
    {
        $root = match ($audience) {
            'partner' => '/cabinet/',
            'admin' => '/admin/',
            default => '/personal/',
        };
        if ($link === '') {
            return $root . ($audience === 'buyer' ? 'notify/' : 'notifications/');
        }

        return $link[0] === '/' || preg_match('#^https?://#i', $link) ? $link : $root . $link;
    }

    /** @return array<string, string> e-mail => имя активных пользователей по фильтру */
    private static function users(array $filter): array
    {
        $rows = \Bitrix\Main\UserTable::getList([
            'select' => ['EMAIL', 'NAME'],
            'filter' => $filter + ['=ACTIVE' => 'Y'],
        ]);
        $users = [];
        while ($row = $rows->fetch()) {
            $email = trim((string)$row['EMAIL']);
            if ($email !== '' && !isset($users[$email])) {
                $users[$email] = trim((string)$row['NAME']);
            }
        }

        return $users;
    }
}
