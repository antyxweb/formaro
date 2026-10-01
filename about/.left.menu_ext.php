<?
// Меню раздела (справа на страницах, плитки на главной раздела, подвал) —
// элементы раздела «about» инфоблока «Контентные страницы» по сортировке.
$aMenuLinksExt = \Bitrix\Main\Loader::includeModule('formaro.cabinet')
    ? \Formaro\Cabinet\Content\SectionPages::menu('about')
    : [];
$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
?>
