<?php

namespace Formaro\Cabinet\Settings;

use Bitrix\Main\Config\Option;

/**
 * Настройки писем — модуль formaro.cabinet, вкладка «Почта» (options.php):
 *   site_url     — адрес сайта для ссылок и логотипа в письмах
 *                  (https://reppaopt.ru). Письма уходят и из агентов, где
 *                  нет запроса, — поэтому адрес задаётся явно; пусто —
 *                  «Адрес сервера» главного модуля по https, затем адрес
 *                  текущего запроса.
 *   admin_emails — кому уходят письма администраторам площадки (через
 *                  запятую); пусто — «E-mail адрес по умолчанию» главного
 *                  модуля.
 */
final class MailSettings
{
    private const MODULE_ID = 'formaro.cabinet';

    public static function siteUrl(): string
    {
        $url = rtrim(trim((string)Option::get(self::MODULE_ID, 'mail_site_url', '')), '/');
        if ($url !== '') {
            return preg_match('#^https?://#i', $url) ? $url : 'https://' . $url;
        }
        $server = trim((string)Option::get('main', 'server_name', ''));
        if ($server !== '') {
            return 'https://' . $server;
        }
        $context = \Bitrix\Main\Context::getCurrent();
        $host = $context ? (string)$context->getServer()->getHttpHost() : '';

        return $host !== '' ? ($context->getRequest()->isHttps() ? 'https://' : 'http://') . $host : '';
    }

    /** @return string[] */
    public static function adminEmails(): array
    {
        $list = (string)Option::get(self::MODULE_ID, 'mail_admin_emails', '');
        if (trim($list) === '') {
            $list = (string)Option::get('main', 'email_from', '');
        }

        return self::parseEmails($list);
    }

    public static function get(): array
    {
        return [
            'site_url' => (string)Option::get(self::MODULE_ID, 'mail_site_url', ''),
            'admin_emails' => (string)Option::get(self::MODULE_ID, 'mail_admin_emails', ''),
        ];
    }

    public static function set(array $values): void
    {
        if (array_key_exists('site_url', $values)) {
            Option::set(self::MODULE_ID, 'mail_site_url', rtrim(trim((string)$values['site_url']), '/'));
        }
        if (array_key_exists('admin_emails', $values)) {
            Option::set(self::MODULE_ID, 'mail_admin_emails', implode(', ', self::parseEmails((string)$values['admin_emails'])));
        }
    }

    /** @return string[] корректные адреса из строки «a@b.ru, c@d.ru; …» без повторов */
    public static function parseEmails(string $list): array
    {
        $emails = array_filter(
            array_map('trim', preg_split('/[\s,;]+/', $list)),
            static fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
        );

        return array_values(array_unique($emails));
    }
}
