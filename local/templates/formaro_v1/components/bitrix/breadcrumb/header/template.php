<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * @global CMain $APPLICATION
 */

global $APPLICATION;

// Свойство страницы BREADCRUMB_SKIP — сколько первых пунктов пропустить
// (каталог партнёра: вместо «Главная — Партнеры» сайта свой корень, см.
// formaro:catalog). Шаблон выполняется отложенно — свойство уже задано.
$breadcrumbSkip = (int)$APPLICATION->GetPageProperty('BREADCRUMB_SKIP');
if ($breadcrumbSkip > 0) {
	$arResult = array_values(array_slice($arResult, $breadcrumbSkip));
}

//delayed function must return a string
if(empty($arResult))
	return "";

$strReturn = '';

//we can't use $APPLICATION->SetAdditionalCSS() here because we are inside the buffered function GetNavChain()

$strReturn .= '<ul class="breadcrumbs">';

$itemSize = count($arResult);
for($index = 0; $index < $itemSize; $index++)
{
	$title = htmlspecialcharsex($arResult[$index]["TITLE"]);

	$nextRef = ($index < $itemSize-2 && $arResult[$index+1]["LINK"] <> ""? ' itemref="bx_breadcrumb_'.($index+1).'"' : '');
	$child = ($index > 0? ' itemprop="child"' : '');

	// Главная на телефоне — иконкой домика.
	$label = $arResult[$index]["LINK"] === '/'
		? '<svg class="d-sm-none mr-1 align-text-bottom" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 11 12 3.5 21 11M5.5 9v11.5h5V15h3v5.5h5V9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="d-none d-sm-inline" itemprop="title">'.$title.'</span>'
		: '<span itemprop="title">'.$title.'</span>';

	if($arResult[$index]["LINK"] <> "" && $index != $itemSize-1)
	{
		$strReturn .= '
			<li id="bx_breadcrumb_'.$index.'" itemscope="" itemtype="http://data-vocabulary.org/Breadcrumb"'.$child.$nextRef.'>
				<a href="'.$arResult[$index]["LINK"].'" title="'.$title.'" itemprop="url">
					'.$label.'
				</a>
                <meta itemprop="position" content="'.($index + 1).'">
                <span class="d-none d-sm-inline">&mdash;</span><span class="d-sm-none breadcrumbs__slash">/</span>
			</li>';
	}
	else
	{
		$strReturn .= '
			<li>
				<span>'.$title.'</span>
			</li>';
	}
}

$strReturn .= '</ul>';

return $strReturn;
