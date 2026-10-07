<?php
/**
 * «Скачать счёт» в «Ваших заказах» (formaro:personal.orders): PDF «Счёт на
 * оплату» заказа (OrderInvoiceService).
 *
 * GET id — покупателю: только свой заказ, по которому площадка выставила
 * действующий счёт (OrderInvoiceFlowService), не оплаченный и не
 * отменённый; иначе 404. Администратору — счёт любого заказа, в том числе
 * ещё не выставленный (предпросмотр); inline=1 — открыть в браузере.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\OrderRepository;
use Formaro\Cabinet\Service\OrderInvoiceService;

$fail = static function (int $status, string $message): void {
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    die();
};

if (!Loader::includeModule('formaro.cabinet')) {
    $fail(500, 'formaro.cabinet module not found');
}

global $USER;
$userId = (is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0;
if (!$userId) {
    $fail(401, 'Войдите, чтобы скачать счёт');
}

$order = (new OrderRepository())->get((int)($_GET['id'] ?? 0));
$isAdmin = $USER->IsAdmin();
if (!$order) {
    $fail(404, 'Счёт не найден');
}
if (!$isAdmin && ($order['user_id'] !== $userId || $order['status'] === 'cancelled'
    || $order['payment_status'] === 'paid' || $order['invoice_status'] !== 'issued')) {
    $fail(404, 'Счёт не найден');
}

try {
    $pdf = OrderInvoiceService::pdf($order);
} catch (\Throwable $e) {
    $fail(500, 'Не удалось сформировать счёт');
}

$file = 'schet-' . preg_replace('/[^A-Za-z0-9-]/', '', $order['order_number']) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($isAdmin && !empty($_GET['inline']) ? 'inline' : 'attachment') . '; filename="' . $file . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, no-store');
echo $pdf;
die();
