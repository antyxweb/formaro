<?php

namespace Formaro\Cabinet\Import;

/**
 * Чтение первого листа XLSX без сторонних библиотек: файл — zip с XML внутри
 * (xl/workbook.xml → лист, xl/sharedStrings.xml — общие строки). Лист читается
 * потоком через XMLReader, поэтому большие файлы не грузятся в память целиком.
 */
class XlsxReader
{
    /** Ограничения на распакованный размер — защита от zip-бомб. */
    private const MAX_SHEET_BYTES = 300 * 1024 * 1024;
    private const MAX_STRINGS_BYTES = 100 * 1024 * 1024;

    /**
     * Строки листа: массивы значений по номеру колонки (A = 0), пропуски заполнены ''.
     *
     * @return \Generator<array<int, string>>
     */
    public static function rows(string $path): \Generator
    {
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::RDONLY) !== true) {
            throw new \RuntimeException('Не удалось открыть XLSX-файл');
        }
        try {
            $sheet = self::firstSheet($zip);
            $stat = $zip->statName($sheet);
            if (!$stat) {
                throw new \RuntimeException('В XLSX-файле не найден лист с данными');
            }
            if ($stat['size'] > self::MAX_SHEET_BYTES) {
                throw new \RuntimeException('Лист XLSX слишком большой — разделите файл на несколько');
            }
            $strings = self::sharedStrings($zip);
        } finally {
            $zip->close();
        }

        $reader = new \XMLReader();
        if (!$reader->open('zip://' . $path . '#' . $sheet, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new \RuntimeException('Не удалось прочитать лист XLSX');
        }
        $prev = libxml_use_internal_errors(true);
        try {
            // После next() ридер уже стоит на следующей <row> — Excel пишет строки
            // без пробелов между ними, и read() в условии пропустил бы каждую вторую.
            $advanced = false;
            while ($advanced || @$reader->read()) {
                $advanced = false;
                if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }
                $node = @$reader->expand(new \DOMDocument());
                if ($node) {
                    yield self::row($node, $strings);
                }
                $advanced = @$reader->next();
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            $reader->close();
        }
    }

    /** Путь к первому листу книги внутри архива. */
    private static function firstSheet(\ZipArchive $zip): string
    {
        $fallback = 'xl/worksheets/sheet1.xml';
        $workbook = self::xml($zip->getFromName('xl/workbook.xml'));
        $rels = self::xml($zip->getFromName('xl/_rels/workbook.xml.rels'));
        if (!$workbook || !$rels) {
            return $fallback;
        }

        $rId = '';
        foreach ($workbook->getElementsByTagName('sheet') as $sheet) {
            $rId = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id')
                ?: $sheet->getAttribute('r:id');
            break;
        }
        foreach ($rels->getElementsByTagName('Relationship') as $rel) {
            if ($rel->getAttribute('Id') === $rId) {
                $target = $rel->getAttribute('Target');
                $target = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . $target;

                return str_contains($target, '..') ? $fallback : $target;
            }
        }

        return $fallback;
    }

    /** @return string[] */
    private static function sharedStrings(\ZipArchive $zip): array
    {
        $stat = $zip->statName('xl/sharedStrings.xml');
        if (!$stat) {
            return [];
        }
        if ($stat['size'] > self::MAX_STRINGS_BYTES) {
            throw new \RuntimeException('XLSX-файл слишком большой — разделите его на несколько');
        }
        $doc = self::xml($zip->getFromName('xl/sharedStrings.xml'));
        if (!$doc) {
            return [];
        }

        $strings = [];
        foreach ($doc->getElementsByTagName('si') as $si) {
            $strings[] = self::text($si);
        }

        return $strings;
    }

    private static function row(\DOMNode $row, array $strings): array
    {
        $values = [];
        $next = 0;
        foreach ($row->childNodes as $cell) {
            if (!$cell instanceof \DOMElement || $cell->localName !== 'c') {
                continue;
            }
            $ref = $cell->getAttribute('r');
            $col = $ref !== '' ? self::column($ref) : $next;
            $next = $col + 1;

            $type = $cell->getAttribute('t');
            $v = '';
            foreach ($cell->childNodes as $child) {
                if ($child->localName === 'v') {
                    $v = $child->textContent;
                } elseif ($child->localName === 'is') {
                    $v = self::text($child);
                }
            }

            switch ($type) {
                case 's':
                    $value = $strings[(int)$v] ?? '';
                    break;
                case 'b':
                    $value = $v === '1' ? 'да' : 'нет';
                    break;
                case 'e':
                    $value = '';
                    break;
                case 'str':
                case 'inlineStr':
                    $value = $v;
                    break;
                default:
                    $value = self::number($v);
            }
            $values[$col] = $value;
        }
        if (!$values) {
            return [];
        }

        return array_replace(array_fill(0, max(array_keys($values)) + 1, ''), $values);
    }

    /** «AB12» → 27 */
    private static function column(string $ref): int
    {
        $letters = strtoupper(preg_replace('/\d+/', '', $ref));
        $index = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    /**
     * Числа хранятся как «4.6000000100150E12» или «449.99999999999994» —
     * приводим к виду, как в ячейке: штрихкоды целиком, без хвостов округления.
     */
    private static function number(string $v): string
    {
        $v = trim($v);
        if ($v === '' || !is_numeric($v)) {
            return $v;
        }
        $f = (float)$v;
        if (abs($f) < 1e15 && $f == round($f)) {
            return sprintf('%.0f', $f);
        }

        return rtrim(rtrim(sprintf('%.6F', $f), '0'), '.');
    }

    /** Текст строки без фонетических подсказок (<rPh>), с учётом форматированных кусков (<r>). */
    private static function text(\DOMNode $node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->localName === 't') {
                $text .= $child->textContent;
            } elseif ($child->localName === 'r') {
                $text .= self::text($child);
            }
        }

        return $text;
    }

    private static function xml($content): ?\DOMDocument
    {
        if (!is_string($content) || $content === '') {
            return null;
        }
        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $doc->loadXML($content, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return $ok ? $doc : null;
    }
}
