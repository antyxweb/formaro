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

	if($arResult[$index]["LINK"] <> "" && $index != $itemSize-1)
	{
		$strReturn .= '
			<li id="bx_breadcrumb_'.$index.'" itemscope="" itemtype="http://data-vocabulary.org/Breadcrumb"'.$child.$nextRef.'>
				<a href="'.$arResult[$index]["LINK"].'" title="'.$title.'" itemprop="url">
					<span itemprop="title">'.$title.'</span>
				</a>
                <meta itemprop="position" content="'.($index + 1).'">
                &mdash;
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
