<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Loader;
use CSaleTfpdf;

/**
 * «Счёт на оплату» заказа в PDF («Скачать счёт» в «Ваших заказах»,
 * /local/ajax/order_invoice.php) — по обычной форме: банковские реквизиты
 * получателя, поставщик и покупатель, товары, итог с промокодом, сумма
 * прописью, подписи.
 *
 * Счёт всегда выставляет юрлицо самого маркетплейса, а не продавец:
 * получатель платежа и поставщик — маркетплейс. Пока бланковый — его
 * название и реквизиты (банк, БИК, счета, ИНН, КПП, ОГРН, адрес,
 * руководитель) пустые: источник реквизитов ещё не определён. НДС не
 * указывается.
 *
 * PDF — генератором модуля «Интернет-магазин» (tFPDF, sale/general/pdf.php)
 * со шрифтом PT Sans из /bitrix/fonts/ (кириллица).
 */
class OrderInvoiceService
{
    private const MARGIN = 15;
    private const WIDTH = 180; // A4 минус поля

    /** @var \tFPDF */
    private $pdf;

    /** @return string содержимое PDF */
    public static function pdf(array $order): string
    {
        if (!Loader::includeModule('sale')) {
            throw new \RuntimeException('Модуль sale не установлен');
        }
        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sale/general/pdf.php';

        return (new self())->render($order);
    }

    private function render(array $order): string
    {
        // Бланк: название и реквизиты маркетплейса — пустые поля (источник
        // появится позже; тогда — сюда, в $sellerName/$legal/$contacts).
        $sellerName = '';
        $legal = [];
        $contacts = [];

        $this->pdf = new CSaleTfpdf('P', 'mm', 'A4');
        $pdf = $this->pdf;
        $pdf->AddFont('Font', '', 'pt_sans-regular.ttf', true);
        $pdf->AddFont('Font', 'B', 'pt_sans-bold.ttf', true);
        $pdf->SetMargins(self::MARGIN, self::MARGIN, self::MARGIN);
        $pdf->SetAutoPageBreak(true, self::MARGIN);
        $pdf->SetTitle('Счёт на оплату ' . $order['order_number'], true);
        $pdf->AddPage();

        $this->bankTable($sellerName, $legal);

        $date = strtotime((string)$order['created_at']) ?: time();
        $pdf->Ln(8);
        $pdf->SetFont('Font', 'B', 15);
        $pdf->MultiCell(self::WIDTH, 7, 'Счёт на оплату № ' . $order['order_number'] . ' от ' . FormatDate('j F Y', $date) . ' г.');
        $pdf->Ln(1);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(self::MARGIN, $pdf->GetY(), self::MARGIN + self::WIDTH, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(4);

        $seller = [$sellerName];
        $seller[] = $legal['inn'] ?? '' ? 'ИНН ' . $legal['inn'] : '';
        $seller[] = $legal['ogrn'] ?? '' ? 'ОГРН ' . $legal['ogrn'] : '';
        $seller[] = $legal['legal_address'] ?? '';
        $seller[] = $contacts['phone'] ?? '' ? 'тел. ' . $contacts['phone'] : '';
        $this->party('Поставщик:', $seller);

        $customer = $order['customer'] ?? [];
        $this->party('Покупатель:', [
            $customer['name'] ?? '',
            $customer['phone'] ?? '' ? 'тел. ' . $customer['phone'] : '',
            $customer['email'] ?? '',
            $customer['address'] ?? '',
        ]);
        $pdf->Ln(3);

        $this->itemsTable($order['items']);
        $this->totals($order);

        $count = count($order['items']);
        $pdf->Ln(4);
        $pdf->SetFont('Font', '', 10);
        $pdf->MultiCell(self::WIDTH, 5, 'Всего наименований ' . $count . ', на сумму ' . $this->money($order['total']) . ' руб.');
        $pdf->SetFont('Font', 'B', 10);
        $pdf->MultiCell(self::WIDTH, 5, Number2Word_Rus((float)$order['total']));
        $pdf->Ln(2);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(self::MARGIN, $pdf->GetY(), self::MARGIN + self::WIDTH, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(10);

        $this->signature('Руководитель', (string)($legal['ceo_name'] ?? ''));
        $pdf->Ln(8);
        $this->signature('Бухгалтер', '');

        return $pdf->Output('', 'S');
    }

    /** Банковские реквизиты получателя — таблица в шапке счёта. */
    private function bankTable(string $sellerName, array $legal): void
    {
        $pdf = $this->pdf;
        $x = self::MARGIN;
        $y = $pdf->GetY();
        $left = 105;
        $label = 20;
        $right = self::WIDTH - $left - $label;
        $h = 6;

        $pdf->SetFont('Font', '', 10);
        // Банк получателя (2 строки) | БИК / Сч. №
        $pdf->Rect($x, $y, $left, $h * 2);
        $pdf->SetXY($x + 1, $y + 1);
        $pdf->MultiCell($left - 2, 4.5, $this->fit((string)($legal['bank_name'] ?? ''), $left - 2, 1));
        $pdf->SetXY($x + 1, $y + $h * 2 - 4.5);
        $pdf->SetFont('Font', '', 8);
        $pdf->Cell($left - 2, 4, 'Банк получателя');
        $pdf->SetFont('Font', '', 10);
        $pdf->SetXY($x + $left, $y);
        $pdf->Cell($label, $h, 'БИК', 1);
        $pdf->Cell($right, $h, (string)($legal['bik'] ?? ''), 'LTR');
        $pdf->SetXY($x + $left, $y + $h);
        $pdf->Cell($label, $h, 'Сч. №', 1);
        $pdf->Cell($right, $h, (string)($legal['corr_account'] ?? ''), 'LRB');

        // ИНН | КПП || Сч. № ; Получатель
        $y += $h * 2;
        $half = $left / 2;
        $pdf->SetXY($x, $y);
        $pdf->Cell($half, $h, 'ИНН ' . ($legal['inn'] ?? ''), 1);
        $pdf->Cell($half, $h, 'КПП', 1);
        $pdf->Cell($label, $h * 3, '', 1);
        $pdf->Cell($right, $h * 3, '', 1);
        $pdf->SetXY($x + $left, $y);
        $pdf->Cell($label, $h, 'Сч. №');
        $pdf->Cell($right, $h, (string)($legal['account'] ?? ''));
        $pdf->Rect($x, $y + $h, $left, $h * 2);
        $pdf->SetXY($x + 1, $y + $h + 1);
        $pdf->MultiCell($left - 2, 4.5, $this->fit($sellerName, $left - 2, 1));
        $pdf->SetXY($x + 1, $y + $h * 3 - 4.5);
        $pdf->SetFont('Font', '', 8);
        $pdf->Cell($left - 2, 4, 'Получатель');
        $pdf->SetFont('Font', '', 10);
        $pdf->SetXY($x, $y + $h * 3);
    }

    /** «Поставщик:» / «Покупатель:» — подпись слева, данные через запятую. */
    private function party(string $title, array $lines): void
    {
        $pdf = $this->pdf;
        $pdf->SetFont('Font', '', 10);
        $pdf->Cell(28, 5, $title);
        $pdf->SetFont('Font', 'B', 10);
        $pdf->MultiCell(self::WIDTH - 28, 5, implode(', ', array_filter(array_map('trim', $lines), 'strlen')));
        $pdf->Ln(2);
    }

    private function itemsTable(array $items): void
    {
        $pdf = $this->pdf;
        $cols = [['№', 9, 'C'], ['Товары (работы, услуги)', 94, 'L'], ['Кол-во', 17, 'R'], ['Ед.', 12, 'C'], ['Цена', 24, 'R'], ['Сумма', 24, 'R']];

        $pdf->SetFont('Font', 'B', 10);
        foreach ($cols as [$title, $w]) {
            $pdf->Cell($w, 7, $title, 1, 0, 'C');
        }
        $pdf->Ln();

        $pdf->SetFont('Font', '', 9);
        foreach (array_values($items) as $i => $item) {
            $name = (string)($item['name'] ?? '');
            $details = array_filter([$item['sku'] ?? '' ? 'арт. ' . $item['sku'] : '', $item['color'] ?? '', $item['size'] ?? '']);
            if ($details) {
                $name .= ' (' . implode(', ', $details) . ')';
            }
            $lines = $this->wrap($name, $cols[1][1] - 3); // минус внутренние поля Cell
            $h = max(1, count($lines)) * 4.5 + 1.5;
            if ($pdf->GetY() + $h > $pdf->GetPageHeight() - self::MARGIN) {
                $pdf->AddPage();
            }
            $qty = (int)($item['qty'] ?? 0);
            $price = (float)($item['price'] ?? 0);
            $values = [(string)($i + 1), '', (string)$qty, 'шт', $this->money($price), $this->money($price * $qty)];

            $x = $pdf->GetX();
            $y = $pdf->GetY();
            foreach ($cols as $c => [, $w, $align]) {
                $pdf->Rect($x, $y, $w, $h);
                if ($c === 1) {
                    // Строки — уже разбитые wrap(), без переноса MultiCell
                    // (он считает ширину иначе, и высота строки не сходится).
                    foreach ($lines as $n => $line) {
                        $pdf->SetXY($x, $y + 0.75 + $n * 4.5);
                        $pdf->Cell($w, 4.5, $line);
                    }
                } else {
                    $pdf->SetXY($x, $y);
                    $pdf->Cell($w, $h, $values[$c], 0, 0, $align);
                }
                $x += $w;
            }
            $pdf->SetXY(self::MARGIN, $y + $h);
        }
    }

    private function totals(array $order): void
    {
        $pdf = $this->pdf;
        $rows = [['Итого:', $order['subtotal']]];
        if ((float)$order['discount_amount'] > 0) {
            $rows[] = ['Скидка' . ($order['coupon_code'] ? ' (промокод ' . $order['coupon_code'] . ')' : '') . ':', -$order['discount_amount']];
        }
        $rows[] = ['Всего к оплате:', $order['total']];

        $pdf->Ln(1);
        foreach ($rows as [$label, $sum]) {
            $pdf->SetFont('Font', 'B', 10);
            $pdf->Cell(self::WIDTH - 24, 6, $label, 0, 0, 'R');
            $pdf->Cell(24, 6, $this->money($sum), 0, 1, 'R');
        }
    }

    private function signature(string $role, string $name): void
    {
        $pdf = $this->pdf;
        $pdf->SetFont('Font', 'B', 10);
        $pdf->Cell(30, 6, $role);
        $x = $pdf->GetX();
        $pdf->Line($x, $pdf->GetY() + 5, $x + 60, $pdf->GetY() + 5);
        $pdf->SetX($x + 64);
        $pdf->SetFont('Font', '', 10);
        $pdf->Cell(80, 6, $name);
        $pdf->Ln();
    }

    private function money($value): string
    {
        return number_format((float)$value, 2, ',', ' ');
    }

    /** Текст по словам в строки шириной $width мм. */
    private function wrap(string $text, float $width): array
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/u', trim($text)) as $word) {
            $try = $line === '' ? $word : $line . ' ' . $word;
            if ($line !== '' && $this->pdf->GetStringWidth($try) > $width) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    /** Не больше $maxLines строк — длинное название банка/продавца не ломает таблицу. */
    private function fit(string $text, float $width, int $maxLines): string
    {
        return implode("\n", array_slice($this->wrap($text, $width), 0, $maxLines + 1));
    }
}
