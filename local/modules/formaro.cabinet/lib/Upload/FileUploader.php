<?php

namespace Formaro\Cabinet\Upload;

use CFile;

/**
 * Реальные файлы (CFile) вместо base64 data:-URL в localStorage — то, что
 * cabinet-html делал только из-за отсутствия бэкенда (см.
 * cabinet-html/CLAUDE.md, "Чего в проекте официально НЕТ"). Фронтенд
 * по-прежнему шлёт превью как data:-URL (FileReader.readAsDataURL — эта
 * часть JS не менялась), сюда прилетает уже готовая строка вида
 * "data:image/png;base64,....".
 */
class FileUploader
{
    /**
     * @param string $dataUrl "data:image/png;base64,...."
     * @param string $subdir подпапка в /upload/ (например "cabinet/catalog")
     * @return int|null ID файла (b_file), null если строка не похожа на data:-URL
     */
    public static function saveFromDataUrl(string $dataUrl, string $subdir): ?int
    {
        if (!preg_match('#^data:([a-z0-9/+.\-]+);base64,(.+)$#is', $dataUrl, $m)) {
            return null;
        }

        $mime = $m[1];
        $binary = base64_decode($m[2]);
        if ($binary === false) {
            return null;
        }

        $ext = self::extensionFromMime($mime);
        $fileArray = [
            'name' => uniqid('img_', true) . '.' . $ext,
            'type' => $mime,
            'content' => $binary,
        ];

        $fileId = CFile::SaveFile($fileArray, $subdir);

        return $fileId ?: null;
    }

    /** @return string|null публичный относительный путь ("/upload/..."), null если файла нет */
    public static function getPath(?int $fileId): ?string
    {
        if (!$fileId) {
            return null;
        }
        $path = CFile::GetPath($fileId);

        return $path ?: null;
    }

    public static function delete(?int $fileId): void
    {
        if ($fileId) {
            CFile::Delete($fileId);
        }
    }

    private static function extensionFromMime(string $mime): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
        ];

        return $map[$mime] ?? 'bin';
    }
}
