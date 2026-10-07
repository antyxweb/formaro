<?php

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loader::includeModule('formaro.cabinet');

use Formaro\Cabinet\Repository\CategoryRepository;
use Formaro\Cabinet\Repository\ChatRepository;
use Formaro\Cabinet\Repository\NewsRepository;
use Formaro\Cabinet\Repository\NotificationRepository;
use Formaro\Cabinet\Repository\OrderRepository;
use Formaro\Cabinet\Repository\PartnerRepository;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Repository\TicketRepository;
use Formaro\Cabinet\Repository\TransactionRepository;
use Formaro\Cabinet\Security\AdminContext;
use Formaro\Cabinet\Service\BuyerNotificationService;
use Formaro\Cabinet\Service\MarkupService;
use Formaro\Cabinet\Service\OrderInvoiceFlowService;
use Formaro\Cabinet\Service\PayoutService;

/**
 * Кабинет администратора площадки (/admin/) — по образцу кабинета партнёра
 * (formaro:cabinet.partner): один комплексный компонент, SEF-роутинг и свой
 * JSON API (ajax_action), те же стили и common.js. Отличие — данные всех
 * партнёров сразу (в каждой строке partner_id, во всех фильтрах — партнёр)
 * и «другая сторона» процессов партнёра: модерация категорий и данных
 * партнёров, ответы в техподдержке, выплаты поставщикам по трек-номеру.
 *
 * Доступ — только роль «Администратор» (AdminContext).
 */
class CabinetAdminComponent extends CBitrixComponent
{
    private const SEF_URL_TEMPLATES = [
        'login' => 'login/',
        'logout' => 'logout/',
        'index' => '',
        'moderation' => 'moderation/',
        'categories' => 'categories/',
        'category_edit' => 'categories/edit/#ID#/',
        'partners' => 'partners/',
        'partner_detail' => 'partners/edit/#ID#/',
        'orders' => 'orders/',
        'order_detail' => 'orders/edit/#ID#/',
        'finance' => 'finance/',
        'news' => 'news/',
        'news_edit' => 'news/edit/#ID#/',
        'support' => 'support/',
        'support_detail' => 'support/#ID#/',
        'chat' => 'chat/',
        'notifications' => 'notifications/',
    ];

    /** Только чтение — без проверки sessid; всё остальное меняет данные. */
    private const READ_ACTIONS = ['list', 'partner_card'];

    private string $action = 'index';
    private array $variables = [];

    public function onPrepareComponentParams($arParams)
    {
        $folder = $arParams['SEF_FOLDER'] ?? '/admin/';
        $arParams['SEF_FOLDER'] = '/' . trim($folder, '/') . '/';
        $arParams['SEF_URL_TEMPLATES'] = self::SEF_URL_TEMPLATES;

        return $arParams;
    }

    public function executeComponent()
    {
        Loader::includeModule('iblock');

        $variables = [];
        $page = CComponentEngine::ParseComponentPath(
            $this->arParams['SEF_FOLDER'],
            $this->arParams['SEF_URL_TEMPLATES'],
            $variables
        );
        $this->action = $page ?: 'index';
        $this->variables = $variables;

        if (isset($_REQUEST['ajax_action'])) {
            $this->handleAjax();

            return;
        }

        $this->guardAccess();

        $this->arResult['ACTION'] = $this->action;
        $this->arResult['VARIABLES'] = $this->variables;
        $this->arResult['SEF_FOLDER'] = $this->arParams['SEF_FOLDER'];

        $this->includeComponentTemplate($this->action);
    }

    private function guardAccess(): void
    {
        if ($this->action === 'logout') {
            global $USER;
            $USER->Logout();
            LocalRedirect($this->arParams['SEF_FOLDER'] . 'login/');
            die();
        }

        $isLogin = $this->action === 'login';
        $hasAccess = AdminContext::hasAccess();
        if (!$isLogin && !$hasAccess) {
            LocalRedirect($this->arParams['SEF_FOLDER'] . 'login/');
            die();
        }
        if ($isLogin && $hasAccess) {
            LocalRedirect($this->arParams['SEF_FOLDER']);
            die();
        }
    }

    /**
     * JSON API: READ_ACTIONS — чтение; остальное меняет данные и требует
     * sessid (common.js::cabinetAjax шлёт его в каждом запросе).
     */
    private function handleAjax(): void
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        header('Content-Type: application/json; charset=utf-8');

        if (!AdminContext::hasAccess()) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $action = (string)$_REQUEST['ajax_action'];
        if (!in_array($action, self::READ_ACTIONS, true) && !check_bitrix_sessid()) {
            echo json_encode(['success' => false, 'error' => 'Истекла сессия, обновите страницу'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $id = (int)($_REQUEST['id'] ?? 0);
        $text = (string)($_REQUEST['text'] ?? '');
        $notifications = NotificationRepository::forAdmin();
        $owner = NotificationRepository::ADMIN_OWNER;

        try {
            $result = match ($action) {
                'list' => $this->ajaxList((string)($_REQUEST['entity'] ?? '')),
                'save' => $this->ajaxSave((string)($_REQUEST['entity'] ?? '')),
                'delete' => $this->ajaxDelete((string)($_REQUEST['entity'] ?? ''), $id),
                'partner_card' => (new PartnerRepository())->getForModeration($id),
                'approve_partner' => (new PartnerRepository())->approve($id),
                'reject_partner' => (new PartnerRepository())->reject($id, (string)($_REQUEST['reason'] ?? '')),
                'set_partner_active' => (new PartnerRepository())->setActive($id, ($_REQUEST['active'] ?? '') === 'Y'),
                'approve_category' => (new CategoryRepository())->approve($id),
                'reject_category' => (new CategoryRepository())->reject($id, (string)($_REQUEST['reason'] ?? '')),
                'reply_ticket' => (new TicketRepository())->replyAsSupport(
                    (int)($_REQUEST['ticket_id'] ?? 0),
                    $text,
                    json_decode((string)($_REQUEST['attachments'] ?? '[]'), true) ?: []
                ),
                'set_ticket_status' => (new TicketRepository())->setStatus((int)($_REQUEST['ticket_id'] ?? 0), (string)($_REQUEST['status'] ?? '')),
                'delete_ticket_message' => (new TicketRepository())->deleteSupportMessage(
                    (int)($_REQUEST['ticket_id'] ?? 0),
                    (int)($_REQUEST['message_id'] ?? 0)
                ),
                'add_order_comment' => (new OrderRepository())->addHistory($id, $text, 'Площадка'),
                // Согласование и счёт: состав по согласованию с покупателем, выставление счёта.
                'update_order_items' => OrderInvoiceFlowService::updateItems($id, json_decode((string)($_REQUEST['items'] ?? '[]'), true) ?: []),
                'issue_invoice' => OrderInvoiceFlowService::issueInvoice($id),
                'save_markup' => MarkupService::save((float)str_replace(',', '.', (string)($_REQUEST['rate'] ?? '')), (float)($_REQUEST['min_order'] ?? 0)),
                'set_payment_status' => $this->setPaymentStatus($id, (string)($_REQUEST['status'] ?? '')),
                'mark_payout' => PayoutService::markPaid($id, (string)($_REQUEST['comment'] ?? '')),
                'mark_notification_read' => $notifications->markRead($owner, $id),
                'mark_all_notifications_read' => $notifications->markAllRead($owner),
                'mark_notifications_read' => $notifications->markReadMany($owner, (array)($_REQUEST['ids'] ?? [])),
                'delete_notifications' => $notifications->deleteMany($owner, (array)($_REQUEST['ids'] ?? [])),
                default => throw new \RuntimeException('Неизвестное действие: ' . $action),
            };

            echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    /**
     * Отметка оплаты заказа: статус и запись в истории (OrderRepository),
     * покупателю — уведомление (BuyerNotificationService), партнёру — в его
     * кабинет со ссылкой на заказ.
     */
    private function setPaymentStatus(int $id, string $status): array
    {
        [$before, $after] = (new OrderRepository())->setPaymentStatus($id, $status);
        if ($before['payment_status'] !== $after['payment_status']) {
            PayoutService::onPaymentChanged($before, $after);
            $after = (new OrderRepository())->get($id);
            BuyerNotificationService::orderUpdated($before, $after);
            $label = OrderRepository::PAYMENT_STATUSES[$status];
            (new NotificationRepository())->add(
                $after['partner_id'],
                'order',
                'Заказ ' . $after['order_number'] . ': ' . mb_strtolower($label),
                match ($status) {
                    'paid' => 'Площадка подтвердила оплату: ' . number_format($after['supplier_total'], 2, ',', ' ') . ' руб. зачислено на ваш текущий баланс. Соберите заказ, передайте в транспортную компанию и укажите трек-номер — выплатим в тот же день.',
                    'refunded' => 'Площадка оформила возврат оплаты покупателю — сумма заказа списана с вашего баланса.',
                    default => 'Площадка сняла отметку об оплате заказа — сумма заказа списана с вашего баланса.',
                },
                'orders/edit/' . $after['id'] . '/'
            );
        }

        return $after;
    }

    private function ajaxList(string $entity)
    {
        return match ($entity) {
            'partners' => (new PartnerRepository())->listAll(),
            'categories' => (new CategoryRepository())->listAll(),
            'products' => (new ProductRepository())->listAll(),
            'orders' => (new OrderRepository())->listAll(),
            'news' => (new NewsRepository())->listAll(),
            'news_sections' => (new NewsRepository())->listSections(),
            'tickets' => (new TicketRepository())->listAll(),
            'chat' => (new ChatRepository())->listAll(),
            'transactions' => (new TransactionRepository())->listAll(),
            'payouts' => PayoutService::rows((new OrderRepository())->listAll(), false),
            'notifications' => NotificationRepository::forAdmin()->listOwn(NotificationRepository::ADMIN_OWNER),
            'markup' => ['rate' => MarkupService::rate(), 'min_rate' => MarkupService::MIN_RATE, 'min_order' => MarkupService::minOrder()],
            default => [],
        };
    }

    private function ajaxSave(string $entity)
    {
        $row = json_decode((string)($_REQUEST['row'] ?? '{}'), true) ?: [];

        return match ($entity) {
            'categories' => (new CategoryRepository())->adminSave($row),
            'news' => (new NewsRepository())->adminSave($row),
            default => throw new \RuntimeException('Сохранение "' . $entity . '" не поддерживается'),
        };
    }

    private function ajaxDelete(string $entity, int $id)
    {
        return match ($entity) {
            'categories' => (new CategoryRepository())->adminDelete($id),
            'news' => (new NewsRepository())->adminDelete($id),
            default => throw new \RuntimeException('Удаление "' . $entity . '" не поддерживается'),
        };
    }
}
