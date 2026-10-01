<?php

namespace Formaro\Cabinet\Seo;

use Bitrix\Main\Context;

/**
 * Мета-теги Open Graph (og:*) и Twitter Card в <head> — превью ссылки в
 * мессенджерах и соцсетях. Шапка (header.php) ставит отложенный вывод
 * render(): заголовок, описание и картинка страницы к тому моменту уже
 * заданы компонентами.
 *
 * Источники (свойства страницы, затем раздела):
 *   og:title       — 'og:title', иначе 'title' (заголовок окна), иначе
 *                    заголовок страницы;
 *   og:description — 'og:description', иначе 'description';
 *   og:image       — 'og:image' (товар, партнёр, новость ставят свою
 *                    картинку), иначе общая картинка сайта DEFAULT_IMAGE;
 *   og:type        — 'og:type' (product, article), иначе website;
 *   og:url         — адрес страницы без параметров.
 * Относительные адреса картинок дополняются схемой и доменом.
 */
final class OpenGraph
{
    public const SITE_NAME = 'Formaro';
    private const DEFAULT_IMAGE = '/web-app-manifest-512x512.png';
    private const DESCRIPTION_LENGTH = 200;

    /** Поставить картинку, тип и описание страницы (для компонентов). */
    public static function set(string $image = '', string $type = '', string $description = ''): void
    {
        global $APPLICATION;
        if ($image !== '') {
            $APPLICATION->SetPageProperty('og:image', $image);
        }
        if ($type !== '') {
            $APPLICATION->SetPageProperty('og:type', $type);
        }
        $description = self::plain($description);
        if ($description !== '') {
            $APPLICATION->SetPageProperty('og:description', $description);
        }
    }

    /** Отложенная функция Битрикса — возвращает HTML тегов. */
    public static function render(): string
    {
        global $APPLICATION;
        $prop = static fn(string $name): string => trim((string)$APPLICATION->GetProperty($name));

        $title = self::plain($prop('og:title') ?: ($prop('title') ?: (string)$APPLICATION->GetTitle(false)));
        $description = self::plain($prop('og:description') ?: $prop('description'));
        $image = $prop('og:image') ?: self::DEFAULT_IMAGE;
        $type = $prop('og:type') ?: 'website';

        $tags = [
            'og:site_name' => self::SITE_NAME,
            'og:locale' => 'ru_RU',
            'og:type' => $type,
            'og:title' => $title !== '' ? $title : self::SITE_NAME,
            'og:description' => $description,
            'og:url' => self::absolute((string)$APPLICATION->GetCurPage(false)),
            'og:image' => self::absolute($image),
        ];
        $html = '';
        foreach ($tags as $property => $content) {
            if ($content !== '') {
                $html .= '<meta property="' . $property . '" content="' . htmlspecialcharsbx($content) . '" />' . "\n";
            }
        }
        $html .= '<meta name="twitter:card" content="' . ($prop('og:image') !== '' ? 'summary_large_image' : 'summary') . '" />' . "\n";

        return $html;
    }

    /** Текст без тегов и лишних пробелов, не длиннее DESCRIPTION_LENGTH. */
    private static function plain(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8')));
        if (mb_strlen($text) > self::DESCRIPTION_LENGTH) {
            $text = rtrim(mb_substr($text, 0, self::DESCRIPTION_LENGTH - 1)) . '…';
        }

        return $text;
    }

    private static function absolute(string $url): string
    {
        if ($url === '' || preg_match('#^https?://#i', $url)) {
            return $url;
        }
        $request = Context::getCurrent()->getRequest();
        $host = (string)$request->getHttpHost();

        return ($request->isHttps() ? 'https' : 'http') . '://' . $host . '/' . ltrim($url, '/');
    }
}
