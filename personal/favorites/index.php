<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Избранное");
?>

<?$APPLICATION->IncludeComponent(
    "formaro:favorites.list",
    "",
    [
        "PAGE_SIZE" => "24",
        "CATALOG_URL" => "/catalog/",
        "CACHE_TYPE" => "A",
        "CACHE_TIME" => "3600",
    ]
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
