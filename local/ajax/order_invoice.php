<?php
/**
 * «Скачать счёт» в «Ваших заказах» (formaro:personal.orders): PDF «Счёт на
 * оплату» заказа (OrderInvoiceService).
 *
 * GET id — только свой заказ, не оплаченный и не отменённый; иначе 404.
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
if (!$order || $order['user_id'] !== $userId || $order['status'] === 'cancelled' || $order['payment_status'] === 'paid') {
    $fail(404, 'Счёт не найден');
}

try {
    $pdf = OrderInvoiceService::pdf($order);
} catch (\Throwable $e) {
    $fail(500, 'Не удалось сформировать счёт');
}

$file = 'schet-' . preg_replace('/[^A-Za-z0-9-]/', '', $order['order_number']) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, no-store');
echo $pdf;
die();
