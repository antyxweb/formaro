<?php
/**
 * Демо-данные: партнёр «Спецодежда Плюс» и покупатель «Тестовый Тест
 * Тестович» — категории, товары, скидки, купон, новости, обращения в
 * техподдержку, заказы, переписка, финансы, уведомления.
 *
 * Запуск (в контейнере php):  php /opt/www/local/tools/demo-data/seed.php
 * Удаление:                   php /opt/www/local/tools/demo-data/clean.php
 * Учётные записи — README.md рядом.
 */

$_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'] ?? '';
if ($_SERVER['DOCUMENT_ROOT'] === '') {
    $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../..');
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Formaro\Cabinet\Repository as R;

Loader::includeModule('iblock');
Loader::includeModule('formaro.cabinet');
// Уведомления демо-данных — без писем (Mailer дублирует ленту письмами).
\Formaro\Cabinet\Mail\Mailer::mute();

require __DIR__ . '/config.php';

/** @var array $DEMO */
$partnerIblock = (int)CIBlock::GetList([], ['=CODE' => 'cabinet_partners', 'CHECK_PERMISSIONS' => 'N'])->Fetch()['ID'];
if (CIBlockElement::GetList([], ['IBLOCK_ID' => $partnerIblock, '=CODE' => $DEMO['partner_code']], false, ['nTopCount' => 1], ['ID'])->Fetch()) {
    exit("Демо-партнёр уже есть — сначала clean.php\n");
}

$out = static function (string $s) { echo $s, "\n"; };
$fileFromUpload = static function (string $rel): ?string {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $rel;
    if (!is_file($path)) {
        return null;
    }
    $mime = mime_content_type($path) ?: 'image/jpeg';

    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
};
$ago = static fn(int $days, int $hours = 0) => DateTime::createFromTimestamp(time() - $days * 86400 - $hours * 3600);
$agoIso = static fn(int $days, int $hours = 0) => date('c', time() - $days * 86400 - $hours * 3600);

// ---------- Партнёр ----------
$logoPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/tmp/demo-logo.png';
@mkdir(dirname($logoPath), 0775, true);
$im = imagecreatetruecolor(400, 400);
imagefill($im, 0, 0, imagecolorallocate($im, 5, 150, 105));
$font = $_SERVER['DOCUMENT_ROOT'] . '/app/fonts/Ubuntu-Bold.ttf';
if (is_file($font)) {
    imagettftext($im, 150, 0, 70, 260, imagecolorallocate($im, 255, 255, 255), $font, 'СП');
}
imagepng($im, $logoPath);
imagedestroy($im);

$el = new CIBlockElement();
$partnerId = (int)$el->Add([
    'IBLOCK_ID' => $partnerIblock,
    'NAME' => 'ООО «Спецодежда Плюс»',
    'CODE' => $DEMO['partner_code'],
    'ACTIVE' => 'Y',
    'SORT' => 500,
    'PREVIEW_TEXT' => 'Рабочая одежда, обувь и средства защиты для производств, складов и строек. Доставка по всей России.',
    'DETAIL_TEXT' => '<p>«Спецодежда Плюс» — поставщик спецодежды и СИЗ с 2012 года. Шьём на собственном производстве и поставляем продукцию проверенных брендов.</p><p>Работаем с юридическими лицами по безналичному расчёту, наносим логотипы, подбираем размеры для всего штата.</p>',
    'DETAIL_TEXT_TYPE' => 'html',
    'PREVIEW_PICTURE' => CFile::MakeFileArray($logoPath),
    'DETAIL_PICTURE' => CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'] . '/upload/iblock/b50/hpdeczmtu6yszc3k21owec2hlsaanivr.jpg'),
]);
if (!$partnerId) {
    exit('Партнёр: ' . $el->LAST_ERROR . "\n");
}
@unlink($logoPath);
$verified = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $partnerIblock, 'CODE' => 'VERIFICATION_STATUS', 'XML_ID' => 'verified'])->Fetch();
CIBlockElement::SetPropertyValuesEx($partnerId, $partnerIblock, [
    'NAME_SHORT' => 'Спецодежда Плюс',
    'VERIFICATION_STATUS' => (int)$verified['ID'],
    'LEGAL_INN' => '7700000000',
    'LEGAL_OGRN' => '1127700000000',
    'LEGAL_ADDRESS' => '125000, г. Москва, ул. Демонстрационная, д. 1, офис 10',
    'LEGAL_BANK_NAME' => 'АО «Демо-Банк»',
    'LEGAL_BIK' => '044500000',
    'LEGAL_ACCOUNT' => '40702810000000000001',
    'LEGAL_CORR_ACCOUNT' => '30101810000000000000',
    'LEGAL_CEO_NAME' => 'Петров Иван Сергеевич',
    'CONTACT_PHONE' => '+7 900 000-00-02',
    'CONTACT_EMAIL' => $DEMO['partner_email'],
    'CONTACT_PERSON' => 'Петров Иван',
    'CONTACT_POSITION' => 'Генеральный директор',
]);
$out("Партнёр: $partnerId");

// ---------- Пользователи ----------
$partnerGroup = (int)CGroup::GetList($by = 'id', $order = 'asc', ['STRING_ID' => 'CABINET_PARTNERS'])->Fetch()['ID'];
$addUser = static function (array $fields) {
    $u = new CUser();
    $id = (int)$u->Add($fields + ['ACTIVE' => 'Y', 'LID' => 's1']);
    if (!$id) {
        exit('Пользователь ' . $fields['LOGIN'] . ': ' . strip_tags((string)$u->LAST_ERROR) . "\n");
    }

    return $id;
};
$partnerUserId = $addUser([
    'LOGIN' => $DEMO['partner_email'], 'EMAIL' => $DEMO['partner_email'],
    'NAME' => 'Иван', 'LAST_NAME' => 'Петров', 'SECOND_NAME' => 'Сергеевич',
    'WORK_PHONE' => '+7 900 000-00-02', 'WORK_COMPANY' => 'ООО «Спецодежда Плюс»', 'WORK_POSITION' => 'Генеральный директор',
    'PASSWORD' => $DEMO['partner_password'], 'CONFIRM_PASSWORD' => $DEMO['partner_password'],
    'GROUP_ID' => [2, $partnerGroup], 'UF_CABINET_PARTNER_ID' => $partnerId,
]);
$buyerId = $addUser([
    'LOGIN' => $DEMO['buyer_email'], 'EMAIL' => $DEMO['buyer_email'],
    'NAME' => 'Тест', 'LAST_NAME' => 'Тестовый', 'SECOND_NAME' => 'Тестович',
    'PERSONAL_PHONE' => '+7 900 000-00-01', 'PERSONAL_CITY' => 'Москва',
    'PASSWORD' => $DEMO['buyer_password'], 'CONFIRM_PASSWORD' => $DEMO['buyer_password'],
    'GROUP_ID' => [2],
    'UF_BUYER_COMPANY' => 'ООО «Тестовая компания»', 'UF_BUYER_INN' => '7700000001', 'UF_BUYER_KPP' => '770001001',
    'UF_BUYER_ADDRESS' => 'г. Москва, ул. Тестовая, д. 5', 'UF_BUYER_BANK' => 'АО «Демо-Банк»', 'UF_BUYER_BIK' => '044500000',
    'UF_BUYER_ACCOUNT' => '40702810000000000002', 'UF_BUYER_CORR_ACCOUNT' => '30101810000000000000', 'UF_BUYER_CEO' => 'Тестовый Т. Т.',
]);
$out("Пользователи: партнёр $partnerUserId, покупатель $buyerId");

// ---------- Категории ----------
$catRepo = new R\CategoryRepository();
$catFood = $catRepo->save($partnerId, ['parent_id' => 211, 'name' => 'Одежда для пищевых производств', 'slug' => 'odezhda-dlya-pishchevykh-proizvodstv-sp', 'short_desc' => 'Белые костюмы, халаты, поло и блузы для пищевых цехов и общепита']);
$catSignal = $catRepo->save($partnerId, ['parent_id' => 211, 'name' => 'Утеплённые жилеты', 'slug' => 'uteplennye-zhilety-sp', 'short_desc' => 'Жилеты для работы на холоде, в том числе с подогревом']);
$catClean = $catRepo->save($partnerId, ['parent_id' => 211, 'name' => 'Одежда для клининга', 'slug' => 'odezhda-dlya-klininga-sp', 'short_desc' => 'Костюмы и фартуки для уборки помещений']);
$catRepo->approve($catFood['id']);
$catRepo->approve($catSignal['id']);
$out('Категории: ' . implode(', ', [$catFood['id'], $catSignal['id'], $catClean['id']]) . ' (последняя — на проверке)');

// ---------- Товары ----------
$prodRepo = new R\ProductRepository();
$products = [];
$productDefs = [
    ['winter', 'Костюм зимний «Дорожник Плюс», оранжевый/чёрный', 'SP-1001', 8900, 25, 'Оранжевый', '52-54', [214], 'iblock/b50/hpdeczmtu6yszc3k21owec2hlsaanivr.jpg', 'Утеплённый костюм с сигнальными полосами: куртка и полукомбинезон, ткань Оксфорд, утеплитель 300 г/м².'],
    ['rain', 'Дождевик «Шквал», жёлтый', 'SP-1002', 1450, 60, 'Жёлтый', 'L', [213], 'iblock/c50/r7uuzradra21jvs3t550qtepusgr6m9w.jpg', 'Плащ-дождевик с капюшоном, проклеенные швы, светоотражающие полосы.'],
    ['vest', 'Жилет утеплённый с подогревом «Термо», чёрный', 'SP-1003', 6200, 12, 'Чёрный', 'XL', [$catSignal['id'], 214], 'iblock/69d/b3i22tf6ys0xfs8v18nwjx0pig8tx11t.jpg', 'Три зоны подогрева, питание от power bank, три режима мощности.'],
    ['gloves-chem', 'Перчатки химзащитные нитриловые, 45 см', 'SP-1004', 390, 300, 'Зелёный', '9', [245], 'iblock/087/uo13rgy4bjar7jhiptsokhq88h1dh8xi.jpg', 'Удлинённые нитриловые перчатки для работы с маслами, кислотами и щелочами.'],
    ['gloves-winter', 'Перчатки утеплённые «Зима» с нитриловым покрытием', 'SP-1005', 280, 500, 'Серый', '10', [245], 'iblock/dc4/hid8dlwsisl70yy7k7ymmayv2xila6vg.jpg', 'Акриловый утеплитель, нитриловое покрытие ладони, до −30 °C.'],
    ['mask', 'Маска сварщика «Кристалл» с креплением на каску', 'SP-1006', 4700, 8, 'Чёрный', '', [240], 'iblock/bbf/w8qpn45u8vbhgudtqlugojqmvaqry339.jpg', 'Автоматический светофильтр DIN 9–13, крепление на защитную каску.'],
    ['polo', 'Рубашка поло для персонала, белая', 'SP-1007', 990, 120, 'Белый', 'M', [$catFood['id'], 216], 'iblock/908/touv9dxh483vvklvm4k6513zrmd25m07.jpg', 'Хлопок 100%, выдерживает промышленную стирку, возможно нанесение логотипа.'],
    ['blouse', 'Блуза для персонала с коротким рукавом, голубая', 'SP-1008', 1250, 80, 'Голубой', '46', [$catFood['id']], 'iblock/ce8/6wrfl6ly1gy8nnpq1n05wnebgis20wz5.jpg', 'Блуза из смесовой ткани для кафе, магазинов и пищевых производств.'],
];
foreach ($productDefs as [$key, $name, $sku, $price, $stock, $color, $size, $cats, $img, $desc]) {
    $p = $prodRepo->save($partnerId, [
        'name' => $name,
        'slug' => CUtil::translit($name, 'ru', ['replace_space' => '-', 'replace_other' => '-']) . '-sp',
        'sku' => $sku, 'price' => $price, 'stock' => $stock, 'color' => $color, 'size' => $size,
        'category_ids' => $cats,
        'short_desc' => $desc,
        'full_desc' => '<p>' . $desc . '</p><p>Поставщик — «Спецодежда Плюс». Для юридических лиц — оплата по счёту.</p>',
        'preview_image' => $fileFromUpload($img),
        'custom_props' => [['name' => 'Производитель', 'value' => 'Спецодежда Плюс'], ['name' => 'Страна', 'value' => 'Россия']],
        'status' => 'active',
    ]);
    $products[$key] = $p;
}
$out('Товары: ' . count($products));

// ---------- Скидки и купон ----------
(new R\DiscountRepository())->save($partnerId, [
    'name' => 'Осенняя скидка 10% на зимнюю спецодежду', 'target_type' => 'products',
    'target_ids' => [$products['winter']['id'], $products['vest']['id'], $products['gloves-winter']['id']],
    'discount_type' => 'percent', 'value' => 10,
    'date_from' => date('Y-m-d', time() - 7 * 86400), 'date_to' => date('Y-m-d', time() + 60 * 86400), 'status' => 'active',
]);
(new R\DiscountRepository())->save($partnerId, [
    'name' => 'Перчатки оптом: −15% от 50 пар', 'target_type' => 'category', 'target_ids' => [245],
    'discount_type' => 'percent', 'value' => 15, 'min_qty' => 50,
    'date_from' => date('Y-m-d', time() - 30 * 86400), 'date_to' => '', 'status' => 'active',
]);
(new R\CouponRepository())->save($partnerId, [
    'code' => 'SPECPLUS15', 'discount_type' => 'percent', 'value' => 15, 'usage_type' => 'multiple',
    'date_from' => date('Y-m-d', time() - 7 * 86400), 'date_to' => date('Y-m-d', time() + 90 * 86400), 'status' => 'active',
]);
$out('Скидки: 2, купон SPECPLUS15');

// ---------- Новости ----------
$newsRepo = new R\NewsRepository();
$newsRepo->save($partnerId, [
    'title' => 'Новая коллекция зимней спецодежды', 'slug' => 'novaya-kollektsiya-zimney-spetsodezhdy-sp',
    'short_desc' => 'Утеплённые костюмы, жилеты с подогревом и перчатки до −30 °C уже в продаже.',
    'full_desc' => '<p>Мы обновили зимнюю коллекцию: костюм «Дорожник Плюс» с сигнальными полосами, жилет с подогревом «Термо» и утеплённые перчатки «Зима».</p><p>Подберём размеры для всей бригады и нанесём логотип.</p>',
    'image' => $fileFromUpload('iblock/b50/hpdeczmtu6yszc3k21owec2hlsaanivr.jpg'),
    'created_at' => $agoIso(5), 'status' => 'active',
    'catalog_section_ids' => [214], 'catalog_product_ids' => [$products['winter']['id'], $products['vest']['id'], $products['gloves-winter']['id']],
]);
$newsRepo->save($partnerId, [
    'title' => 'Скидка 10% на зимнюю спецодежду до конца ноября', 'slug' => 'skidka-10-na-zimnyuyu-spetsodezhdu-sp',
    'short_desc' => 'Осенняя акция «Спецодежды Плюс» и промокод SPECPLUS15 на первый заказ.',
    'full_desc' => '<p>До конца ноября — скидка 10% на зимние костюмы, жилеты и утеплённые перчатки. Для первого заказа — промокод <b>SPECPLUS15</b> на 15%.</p>',
    'image' => $fileFromUpload('iblock/69d/b3i22tf6ys0xfs8v18nwjx0pig8tx11t.jpg'),
    'created_at' => $agoIso(2), 'status' => 'active',
    'catalog_product_ids' => [$products['winter']['id'], $products['vest']['id']],
]);
$out('Новости: 2');

// ---------- Заказы ----------
$orderRepo = new R\OrderRepository();
$ordersClass = R\HlblockEntityFactory::getDataClass('CabinetOrders');
$customer = ['name' => 'Тестовый Тест Тестович', 'phone' => '+7 900 000-00-01', 'email' => $DEMO['buyer_email'], 'address' => 'г. Москва, ул. Тестовая, д. 5, склад 2'];
// price — цена покупателя (с наценкой площадки), supplier_price — цена поставщика.
$item = static fn(array $p, int $qty) => ['product_id' => $p['id'], 'name' => $p['name'], 'sku' => $p['sku'], 'color' => $p['color'], 'size' => $p['size'], 'qty' => $qty,
    'price' => \Formaro\Cabinet\Service\MarkupService::retail((float)$p['price']), 'supplier_price' => (float)$p['price']];
$orderDefs = [
    // [items, status, payment, days ago, history [[days, hours, text, author]], comment]
    [[$item($products['winter'], 2), $item($products['gloves-winter'], 10)], 'completed', 'paid', 21, [
        [21, 0, 'Заказ оформлен на сайте', 'Покупатель'], [20, 6, 'Поставщик подтвердил наличие товара. Срок отгрузки: 2 рабочих дня', 'Менеджер'],
        [20, 5, 'Статус изменён на «Подтверждён»', 'Менеджер'], [20, 4, 'Площадка выставила счёт на оплату', 'Площадка'],
        [19, 2, 'Оплата подтверждена площадкой', 'Площадка'], [18, 0, 'Статус изменён на «Отправлен». Заказ передан в транспортную компанию: СДЭК, трек-номер 9876543210', 'Менеджер'],
        [14, 0, 'Статус изменён на «Выполнен»', 'Менеджер'],
    ], 'Доставка до склада, разгрузка нашими силами'],
    [[$item($products['rain'], 5)], 'shipped', 'paid', 9, [
        [9, 0, 'Заказ оформлен на сайте', 'Покупатель'], [8, 5, 'Поставщик подтвердил наличие товара. Срок отгрузки: 3 рабочих дня', 'Менеджер'],
        [8, 4, 'Статус изменён на «Подтверждён»', 'Менеджер'], [8, 3, 'Площадка выставила счёт на оплату', 'Площадка'],
        [8, 1, 'Оплата подтверждена площадкой', 'Площадка'], [0, 3, 'Статус изменён на «Отправлен». Заказ передан в транспортную компанию: СДЭК, трек-номер 1234567890', 'Менеджер'],
    ], ''],
    [[$item($products['mask'], 1), $item($products['gloves-chem'], 20)], 'processing', 'awaiting', 3, [
        [3, 0, 'Заказ оформлен на сайте', 'Покупатель'], [2, 4, 'Поставщик подтвердил наличие товара. Срок отгрузки: 1 рабочий день', 'Менеджер'],
        [2, 2, 'Площадка выставила счёт на оплату', 'Площадка'],
        [1, 2, 'Покупатель сообщил об оплате', 'Покупатель'],
    ], 'Нужны закрывающие документы'],
    [[$item($products['polo'], 6), $item($products['blouse'], 4)], 'new', 'awaiting', 0, [
        [0, 2, 'Заказ оформлен на сайте', 'Покупатель'],
    ], 'Можно ли нанести логотип?'],
    [[$item($products['vest'], 1)], 'cancelled', 'awaiting', 12, [
        [12, 0, 'Заказ оформлен на сайте', 'Покупатель'], [11, 0, 'Покупатель отменил заказ', 'Покупатель'],
    ], ''],
];
$orders = [];
foreach ($orderDefs as [$items, $status, $payment, $days, $history, $comment]) {
    $subtotal = array_sum(array_map(static fn($i) => $i['qty'] * $i['price'], $items));
    $o = $orderRepo->save($partnerId, [
        'status' => $status, 'customer' => $customer, 'items' => $items, 'subtotal' => $subtotal, 'total' => $subtotal,
        'delivery_method' => 'Транспортной компанией (СДЭК)', 'payment_method' => 'По счёту (безнал)', 'payment_status' => $payment,
        'customer_comment' => $comment, 'user_id' => $buyerId,
        'history' => array_map(static fn($h) => ['date' => $agoIso($h[0], $h[1]), 'text' => $h[2], 'author' => $h[3]], $history),
    ]);
    $fields = ['UF_CREATED_AT' => $ago($days, 3)];
    // Подтверждён поставщиком и счёт выставлен (OrderInvoiceFlowService) — все, кроме нового и отменённого.
    if (!in_array($status, ['new', 'cancelled'], true)) {
        $confirmed = $days === 3 ? 2 : $days - 1;
        $fields += [
            'UF_SUPPLIER_CONFIRMED_AT' => $ago($confirmed, 5), 'UF_SUPPLIER_LEAD_TIME' => '2 рабочих дня',
            'UF_INVOICE_STATUS' => 'issued', 'UF_INVOICE_DATE' => $ago($confirmed, 3),
            'UF_INVOICE_DUE' => \Formaro\Cabinet\Service\OrderInvoiceFlowService::dueDate(time() - $confirmed * 86400),
        ];
    }
    $ordersClass::update($o['id'], $fields);
    $orders[] = $orderRepo->get($o['id']);
}
$out('Заказы: ' . implode(', ', array_column($orders, 'order_number')));

// ---------- Переписка покупателя и партнёра ----------
$chat = new R\ChatRepository();
$msgClass = R\HlblockEntityFactory::getDataClass('CabinetChatMessages');
$thread = static function (string $orderNumber, array $product, array $messages) use ($chat, $msgClass, $buyerId, $partnerId, $ago) {
    $tid = $chat->createThread($buyerId, $partnerId, 'Тестовый Тест Тестович', $orderNumber, $product['id'] ?? 0, $product['name'] ?? '');
    foreach ($messages as [$sender, $text, $days, $hours, $read]) {
        $chat->addMessage($tid, $sender, $text);
        $last = $msgClass::getList(['filter' => ['=UF_THREAD_ID' => $tid], 'order' => ['ID' => 'DESC'], 'limit' => 1])->fetch();
        $msgClass::update($last['ID'], ['UF_DATE' => $ago($days, $hours), 'UF_IS_READ' => $read, 'UF_NOTIFIED' => true]);
    }

    return $tid;
};
$thread($orders[0]['order_number'], [], [
    ['client', 'Добрый день! Подскажите, когда отправите заказ?', 20, 6, true],
    ['partner', 'Здравствуйте! Заказ собран, передадим в СДЭК завтра утром.', 20, 4, true],
    ['client', 'Спасибо, получили, всё подошло по размерам.', 13, 2, true],
    ['partner', 'Рады, что всё подошло! Будем ждать снова.', 13, 1, true],
]);
$thread('', $products['mask'], [
    ['client', 'Здравствуйте! Маска подойдёт на каску СОМЗ?', 4, 5, true],
    ['partner', 'Да, крепление универсальное, подходит к большинству касок с пазами 30 мм.', 4, 3, true],
    ['client', 'Отлично, тогда оформляю заказ.', 3, 1, true],
]);
$thread($orders[3]['order_number'], [], [
    ['client', 'Можно ли нанести логотип нашей компании на поло и блузы? Файл логотипа пришлю.', 0, 1, false],
]);
$thread('', [], [
    ['client', 'Добрый день! Есть ли у вас костюмы для клининга?', 1, 6, true],
    ['partner', 'Здравствуйте! Готовим новую категорию, появится на этой неделе.', 1, 3, false],
]);
$out('Диалоги: 4');

// ---------- Техподдержка ----------
$tickets = new R\TicketRepository();
$ticketsClass = R\HlblockEntityFactory::getDataClass('CabinetTickets');
$t1 = $tickets->create($partnerId, 'Как изменить банковские реквизиты?', 'Добрый день! Мы сменили банк. Как обновить реквизиты в профиле?');
$tickets->replyAsSupport($t1['id'], 'Здравствуйте! Укажите новые реквизиты в «Данных партнёра» на вкладке «Основное» и нажмите «Отправить на проверку» — мы проверим их в течение рабочего дня.');
$tickets->reply($partnerId, $t1['id'], 'Спасибо, отправили.');
$tickets->setStatus($t1['id'], 'closed');
$ticketsClass::update($t1['id'], ['UF_CREATED_AT' => $ago(15)]);
$t2 = $tickets->create($partnerId, 'Планируется ли оплата картой?', 'Покупатели спрашивают про оплату картой на сайте. Планируется ли такая возможность?');
$ticketsClass::update($t2['id'], ['UF_CREATED_AT' => $ago(1)]);
$out('Обращения: 2');

// ---------- Финансы: выплаты по трек-номеру (PayoutService) ----------
// Заказ 1 — оплачен, отгружен, выплачен; заказ 2 — оплачен, трек указан
// сегодня, «к выплате» у администратора.
$tx = R\HlblockEntityFactory::getDataClass('CabinetTransactions');
$addTx = static function (string $type, float $amount, string $desc, int $days, int $orderId) use ($tx, $partnerId, $ago) {
    $tx::add(['UF_TYPE' => $type, 'UF_AMOUNT' => $amount, 'UF_STATUS' => 'completed', 'UF_DESCRIPTION' => $desc, 'UF_PARTNER_ID' => $partnerId, 'UF_ORDER_ID' => $orderId, 'UF_CREATED_AT' => $ago($days)]);
};
[$o1, $o2] = $orders;
$addTx('income', $o1['supplier_total'], 'Оплата заказа ' . $o1['order_number'], 19, $o1['id']);
$addTx('payout', $o1['supplier_total'], 'Выплата по заказу ' . $o1['order_number'] . ': п/п № 101', 18, $o1['id']);
$ordersClass::update($o1['id'], [
    'UF_TRACK_NUMBER' => '9876543210', 'UF_TRACK_CARRIER' => 'СДЭК', 'UF_SHIPPED_AT' => $ago(18, 2),
    'UF_PAYOUT_STATUS' => 'paid', 'UF_PAYOUT_AT' => $ago(18), 'UF_PAYOUT_AMOUNT' => $o1['supplier_total'], 'UF_PAYOUT_COMMENT' => 'п/п № 101',
]);
$addTx('income', $o2['supplier_total'], 'Оплата заказа ' . $o2['order_number'], 8, $o2['id']);
$ordersClass::update($o2['id'], [
    'UF_TRACK_NUMBER' => '1234567890', 'UF_TRACK_CARRIER' => 'СДЭК', 'UF_SHIPPED_AT' => $ago(0, 3), 'UF_PAYOUT_STATUS' => 'due',
]);
$out('Финансы: заказ ' . $o1['order_number'] . ' выплачен, ' . $o2['order_number'] . ' — к выплате');

// ---------- Уведомления ----------
$partnerNotes = new R\NotificationRepository();
$buyerNotes = R\NotificationRepository::forBuyer();
$notesClass = R\HlblockEntityFactory::getDataClass('CabinetNotifications');
foreach ($orders as $i => $o) {
    $partnerNotes->add($partnerId, 'order', 'Новый заказ ' . $o['order_number'], 'Тестовый Тест Тестович оформил заказ на сайте.', 'orders/edit/' . $o['id'] . '/');
    $buyerNotes->addForBuyer($buyerId, 'order', 'Заказ ' . $o['order_number'] . ' оформлен', 'Продавец «Спецодежда Плюс» получил ваш заказ.', '/personal/orders/#order-' . $o['id']);
}
$buyerNotes->addForBuyer($buyerId, 'order', 'Оплата заказа ' . $orders[1]['order_number'] . ' подтверждена', 'Маркетплейс получил оплату.', '/personal/orders/#order-' . $orders[1]['id']);
$buyerNotes->addForBuyer($buyerId, 'order', 'Заказ ' . $orders[1]['order_number'] . ': отправлен', 'Заказ передан в доставку: Транспортной компанией (СДЭК).', '/personal/orders/#order-' . $orders[1]['id']);
// Старые уведомления — прочитанными, свежие (последние заказы) — нет.
foreach ($notesClass::getList(['filter' => ['LOGIC' => 'OR', '=UF_PARTNER_ID' => $partnerId, '=UF_USER_ID' => $buyerId], 'select' => ['ID']])->fetchAll() as $n) {
    $notesClass::update($n['ID'], ['UF_IS_READ' => true]);
}
$partnerNotes->add($partnerId, 'chat', 'Новое сообщение от покупателя', 'Тестовый Тест Тестович: «Можно ли нанести логотип…»', 'chat/');
$out('Уведомления добавлены');

CIBlock::clearIblockTagCache($partnerIblock);
$out('Готово.');
