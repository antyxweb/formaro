<?
// Меню раздела (справа на страницах, плитки на главной раздела) —
// элементы раздела «guide» инфоблока «Контентные страницы» по сортировке (в подвале гайда нет).
$aMenuLinksExt = \Bitrix\Main\Loader::includeModule('formaro.cabinet')
    ? \Formaro\Cabinet\Content\SectionPages::menu('guide')
    : [];
$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
?>
