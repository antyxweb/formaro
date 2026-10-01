<?
// Меню раздела (справа на страницах, плитки на главной раздела, подвал) —
// элементы раздела «support» инфоблока «Контентные страницы» по сортировке.
$aMenuLinksExt = \Bitrix\Main\Loader::includeModule('formaro.cabinet')
    ? \Formaro\Cabinet\Content\SectionPages::menu('support')
    : [];
$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
?>
