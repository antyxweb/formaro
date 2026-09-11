<?php

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loader::includeModule('formaro.cabinet');

use Formaro\Cabinet\Repository\CategoryRepository;
use Formaro\Cabinet\Repository\ChatRepository;
use Formaro\Cabinet\Repository\CouponRepository;
use Formaro\Cabinet\Repository\DiscountRepository;
use Formaro\Cabinet\Repository\NewsRepository;
use Formaro\Cabinet\Repository\OrderRepository;
use Formaro\Cabinet\Repository\PartnerDocumentRepository;
use Formaro\Cabinet\Repository\PartnerRepository;
use Formaro\Cabinet\Repository\ProductColorRepository;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Repository\TicketRepository;
use Formaro\Cabinet\Repository\TransactionRepository;
use Formaro\Cabinet\Security\PartnerContext;

/**
 * Комплексный компонент кабинета партнёра — один компонент, один физический
 * файл /cabinet/index.php, весь роутинг (обычные страницы + свой AJAX API)
 * разбирается здесь через CComponentEngine::ParseComponentPath.
 *
 * SEF_URL_TEMPLATES ниже — действия фаз 1-5 (см. план: модуль, вход,
 * профиль, категории, товары, заказы, скидки/купоны/финансы, новости,
 * поддержка, чат с клиентами). Остальные действия из целевой карты роутов
 * (уведомления) появятся в следующей фазе — их шаблоны ещё не существуют,
 * поэтому регистрировать их сейчас означало бы плодить страницы, которые
 * сразу 404.
 */
class CabinetPartnerComponent extends CBitrixComponent
{
    private const SEF_URL_TEMPLATES = [
        'login' => 'login/',
        'forgot_password' => 'forgot-password/',
        'logout' => 'logout/',
        'index' => '',
        'profile' => 'profile/',
        'categories' => 'categories/',
        'category_edit' => 'categories/edit/#ID#/',
        'products' => 'products/',
        'product_edit' => 'products/edit/#ID#/',
        'orders' => 'orders/',
        'order_detail' => 'orders/edit/#ID#/',
        'discounts' => 'discounts/',
        'discount_edit' => 'discounts/edit/#ID#/',
        'coupon_edit' => 'discounts/coupons/edit/#ID#/',
        'finance' => 'finance/',
        'news' => 'news/',
        'news_edit' => 'news/edit/#ID#/',
        'support' => 'support/',
        'support_detail' => 'support/#ID#/',
        'chat' => 'chat/',
    ];

    /** Действия, доступные БЕЗ авторизации (экраны входа) */
    private const PUBLIC_ACTIONS = ['login', 'forgot_password'];

    private string $action = 'index';
    private array $variables = [];

    public function onPrepareComponentParams($arParams)
    {
        $arParams['SEF_MODE'] = ($arParams['SEF_MODE'] ?? 'Y') === 'N' ? 'N' : 'Y';
        $folder = $arParams['SEF_FOLDER'] ?? '/cabinet/';
        $arParams['SEF_FOLDER'] = '/' . trim($folder, '/') . '/';
        $arParams['SEF_URL_TEMPLATES'] = self::SEF_URL_TEMPLATES;

        return $arParams;
    }

    public function executeComponent()
    {
        Loader::includeModule('iblock');

        $this->resolveRoute();

        if (isset($_REQUEST['ajax_action'])) {
            $this->handleAjax();

            return;
        }

        $this->guardAccess();

        $this->arResult['ACTION'] = $this->action;
        $this->arResult['VARIABLES'] = $this->variables;
        $this->arResult['PARTNER_ID'] = PartnerContext::getPartnerId();
        $this->arResult['SEF_FOLDER'] = $this->arParams['SEF_FOLDER'];

        $this->includeComponentTemplate($this->action);
    }

    private function resolveRoute(): void
    {
        if ($this->arParams['SEF_MODE'] !== 'Y') {
            $this->action = $_REQUEST['action'] ?? 'index';

            return;
        }

        $variables = [];
        $page = CComponentEngine::ParseComponentPath(
            $this->arParams['SEF_FOLDER'],
            $this->arParams['SEF_URL_TEMPLATES'],
            $variables
        );

        $this->action = $page ?: 'index';
        $this->variables = $variables;
    }

    private function guardAccess(): void
    {
        $isPublic = in_array($this->action, self::PUBLIC_ACTIONS, true);
        $hasAccess = PartnerContext::hasAccess();

        if (!$isPublic && !$hasAccess) {
            LocalRedirect($this->url('login'));
            die();
        }

        if ($isPublic && $hasAccess) {
            LocalRedirect($this->url('index'));
            die();
        }

        if ($this->action === 'logout') {
            global $USER;
            $USER->Logout();
            LocalRedirect($this->url('login'));
            die();
        }
    }

    public function url(string $action, array $vars = []): string
    {
        $template = self::SEF_URL_TEMPLATES[$action] ?? '';
        foreach ($vars as $key => $value) {
            $template = str_replace('#' . $key . '#', (string)$value, $template);
        }

        return rtrim($this->arParams['SEF_FOLDER'], '/') . '/' . ltrim($template, '/');
    }

    /**
     * Простой JSON API поверх репозиториев — контракт: ajax_action=list|save|delete
     * (+ entity), список/один/сохранить/удалить, тот же смысл, что был у
     * dsLoad/dsSaveOne/dsDeleteOne в common.js. Сущности, ещё не переведённые
     * на реальную БД (заказы/скидки/новости и т.д., см. фазы 2-6 плана) —
     * отдают пустой список, чтобы счётчики в шапке/сайдбаре не падали.
     */
    private function handleAjax(): void
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        header('Content-Type: application/json; charset=utf-8');

        if (!PartnerContext::hasAccess()) {
            echo json_encode(['success' => false, 'error' => 'Не авторизован'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $partnerId = PartnerContext::getPartnerId();
        $action = $_REQUEST['ajax_action'];
        $entity = $_REQUEST['entity'] ?? '';

        // list — только чтение, без CSRF-проверки; всё, что меняет данные,
        // должно прийти с валидным sessid (common.js::cabinetAjax шлёт его
        // в каждом запросе, см. BX.bitrix_sessid()).
        if ($action !== 'list' && !check_bitrix_sessid()) {
            echo json_encode(['success' => false, 'error' => 'Истекла сессия, обновите страницу'], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $result = match ($action) {
                'list' => $this->ajaxList($entity, $partnerId),
                'save' => $this->ajaxSave($entity, $partnerId),
                'delete' => $this->ajaxDelete($entity, $partnerId),
                'add_document' => (new PartnerDocumentRepository())->add(
                    $partnerId,
                    (string)($_REQUEST['name'] ?? ''),
                    (string)($_REQUEST['file'] ?? '')
                ),
                'delete_document' => (new PartnerDocumentRepository())->delete($partnerId, (int)($_REQUEST['id'] ?? 0)),
                'request_withdrawal' => (new TransactionRepository())->requestWithdrawal(
                    $partnerId,
                    (float)($_REQUEST['amount'] ?? 0)
                ),
                'create_ticket' => (new TicketRepository())->create(
                    $partnerId,
                    (string)($_REQUEST['subject'] ?? ''),
                    (string)($_REQUEST['text'] ?? ''),
                    $this->decodeAttachments()
                ),
                'reply_ticket' => (new TicketRepository())->reply(
                    $partnerId,
                    (int)($_REQUEST['ticket_id'] ?? 0),
                    (string)($_REQUEST['text'] ?? ''),
                    $this->decodeAttachments()
                ),
                'delete_ticket_message' => (new TicketRepository())->deleteMessage(
                    $partnerId,
                    (int)($_REQUEST['ticket_id'] ?? 0),
                    (int)($_REQUEST['message_id'] ?? 0)
                ),
                'send_chat_message' => (new ChatRepository())->sendMessage(
                    $partnerId,
                    (int)($_REQUEST['thread_id'] ?? 0),
                    (string)($_REQUEST['text'] ?? ''),
                    $this->decodeAttachments()
                ),
                'mark_thread_read' => (new ChatRepository())->markRead($partnerId, (int)($_REQUEST['thread_id'] ?? 0)),
                'delete_chat_message' => (new ChatRepository())->deleteMessage(
                    $partnerId,
                    (int)($_REQUEST['thread_id'] ?? 0),
                    (int)($_REQUEST['message_id'] ?? 0)
                ),
                default => throw new \RuntimeException('Неизвестное действие: ' . $action),
            };

            echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    private function ajaxList(string $entity, int $partnerId)
    {
        return match ($entity) {
            'categories' => (new CategoryRepository())->listVisible($partnerId),
            'products' => (new ProductRepository())->listOwn($partnerId),
            'orders' => (new OrderRepository())->listOwn($partnerId),
            'discounts' => (new DiscountRepository())->listOwn($partnerId),
            'coupons' => (new CouponRepository())->listOwn($partnerId),
            'finance' => (new TransactionRepository())->getSummary($partnerId),
            'news' => (new NewsRepository())->listOwn($partnerId),
            'tickets' => (new TicketRepository())->listOwn($partnerId),
            'marketplace_contacts' => (new TicketRepository())->getMarketplaceContacts(),
            'chat' => (new ChatRepository())->listOwn($partnerId),
            'partner' => (new PartnerRepository())->get($partnerId),
            'partner_documents' => (new PartnerDocumentRepository())->listByPartner($partnerId),
            'product_colors' => (new ProductColorRepository())->listAll(),
            default => [],
        };
    }

    /** @return array [{name, type, data}] — общий разбор для create_ticket/reply_ticket/send_chat_message */
    private function decodeAttachments(): array
    {
        return json_decode((string)($_REQUEST['attachments'] ?? '[]'), true) ?: [];
    }

    private function ajaxSave(string $entity, int $partnerId)
    {
        $row = json_decode((string)($_REQUEST['row'] ?? '{}'), true) ?: [];

        return match ($entity) {
            'categories' => (new CategoryRepository())->save($partnerId, $row),
            'products' => (new ProductRepository())->save($partnerId, $row),
            'orders' => (new OrderRepository())->save($partnerId, $row),
            'discounts' => (new DiscountRepository())->save($partnerId, $row),
            'coupons' => (new CouponRepository())->save($partnerId, $row),
            'news' => (new NewsRepository())->save($partnerId, $row),
            'partner' => (new PartnerRepository())->save($partnerId, $row),
            default => throw new \RuntimeException('Сохранение "' . $entity . '" пока не реализовано'),
        };
    }

    private function ajaxDelete(string $entity, int $partnerId)
    {
        $id = (int)($_REQUEST['id'] ?? 0);

        return match ($entity) {
            'categories' => (new CategoryRepository())->delete($partnerId, $id),
            'products' => (new ProductRepository())->delete($partnerId, $id),
            'orders' => (new OrderRepository())->delete($partnerId, $id),
            'discounts' => (new DiscountRepository())->delete($partnerId, $id),
            'coupons' => (new CouponRepository())->delete($partnerId, $id),
            'news' => (new NewsRepository())->delete($partnerId, $id),
            default => throw new \RuntimeException('Удаление "' . $entity . '" пока не реализовано'),
        };
    }
}
