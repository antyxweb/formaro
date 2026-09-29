<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Корзина");
$APPLICATION->SetPageProperty("title", "Корзина");
?>

<?$APPLICATION->IncludeComponent(
    "formaro:cart",
    "",
    [
        "CATALOG_URL" => "/catalog/",
    ]
);?>

<?$APPLICATION->IncludeComponent(
    "formaro:product.carousel",
    "",
    [
        "MODE" => "VIEWED",
        "COUNT" => "20",
        "TITLE_ACCENT" => "Просмотренные",
        "TITLE" => "товары",
        "CATALOG_URL" => "",
        "SECTION_CLASS" => "light-gray-stripe",
        "CACHE_TYPE" => "A",
        "CACHE_TIME" => "600",
    ]
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
