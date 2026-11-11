<?
// Меню раздела (справа на страницах, плитки на главной раздела, подвал) —
// элементы раздела «for-partners» инфоблока «Контентные страницы» по сортировке.
$aMenuLinksExt = \Bitrix\Main\Loader::includeModule('formaro.cabinet')
    ? \Formaro\Cabinet\Content\SectionPages::menu('for-partners')
    : [];
// Пока скрыт «Список партнеров» (/partners/): меню, плитки раздела, подвал.
$aMenuLinksExt = array_values(array_filter($aMenuLinksExt, static fn($l) => $l[1] !== '/partners/'));
$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
?>
