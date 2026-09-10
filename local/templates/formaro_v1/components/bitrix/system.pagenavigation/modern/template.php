<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$this->setFrameMode(true);

$pageCnt = $arResult['NavPageSize'];
$nextPageCnt = $pageCnt;
$nextPageIsset = $arResult['NavRecordCount'] - ($pageCnt * $arResult['NavPageNomer']);
if($nextPageIsset < $nextPageCnt) {
    $nextPageCnt = $nextPageIsset;
}

if(!$arResult["NavShowAlways"])
{
	if ($arResult["NavRecordCount"] == 0 || ($arResult["NavPageCount"] == 1 && $arResult["NavShowAll"] == false))
		return;
}
?>
<div class="pager">
    <?if($arResult["NavPageNomer"] < $arResult["NavPageCount"]):?>
        <div class="d-flex justify-content-center pt-4">
            <button class="f-button c-secondary" id="load-more" data-href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>">
                <svg width="16" height="16">
                    <use xlink:href="#icon-grid"></use>
                </svg>
                <svg width="16" height="16" class="icon-progress d-none">
                    <use xlink:href="#icon-progress"></use>
                </svg>
                <span class="pl-2">Показать еще <?=$nextPageCnt?></span>
            </button>
        </div>
    <?endif?>
    <div class="d-flex justify-content-center hide-on-main pt-5">
        <ul class="pagging__list">
<?

$strNavQueryString = ($arResult["NavQueryString"] != "" ? $arResult["NavQueryString"]."&amp;" : "");
$strNavQueryStringFull = ($arResult["NavQueryString"] != "" ? "?".$arResult["NavQueryString"] : "");

if($arResult["bDescPageNumbering"] === true):
	$bFirst = true;
	if ($arResult["NavPageNomer"] < $arResult["NavPageCount"]):
		if($arResult["bSavePage"]):
?>
            <li class="ml-4"><a class="pagging__arrow pagging__item bg-secondary text-white" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>"><svg width="16" height="16"><use xlink:href="#icon-arrow-right"></use></svg></a></li>
<?
		else:
			if ($arResult["NavPageCount"] == ($arResult["NavPageNomer"]+1) ):
?>
                <li class="mr-4"><a class="pagging__arrow pagging__item bg-secondary text-white" href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>"><svg width="16" height="16"><use xlink:href="#icon-arrow-left"></use></svg></a></li>
<?
			else:
?>
                <li class="ml-4"><a class="pagging__arrow pagging__item bg-secondary text-white" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>"><svg width="16" height="16"><use xlink:href="#icon-arrow-right"></use></svg></a></li>
<?
			endif;
		endif;

		if ($arResult["nStartPage"] < $arResult["NavPageCount"]):
			$bFirst = false;
			if($arResult["bSavePage"]):
?>
			<li><a class="pagging__item" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["NavPageCount"]?>">1</a></li>
<?
			else:
?>
			<li><a class="pagging__item" href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>">1</a></li>
<?
			endif;
			if ($arResult["nStartPage"] < ($arResult["NavPageCount"] - 1)):
?>
			<li><a class="pagging__item" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=intval($arResult["nStartPage"] + ($arResult["NavPageCount"] - $arResult["nStartPage"]) / 2)?>">...</a></li>
<?
			endif;
		endif;
	endif;
	do
	{
		$NavRecordGroupPrint = $arResult["NavPageCount"] - $arResult["nStartPage"] + 1;

		if ($arResult["nStartPage"] == $arResult["NavPageNomer"]):
?>
		<li><span class="pagging__item"><?=$NavRecordGroupPrint?></span></li>
<?
		elseif($arResult["nStartPage"] == $arResult["NavPageCount"] && $arResult["bSavePage"] == false):
?>
		<li><a href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>" class="pagging__item"><?=$NavRecordGroupPrint?></a></li>
<?
		else:
?>
		<li><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["nStartPage"]?>"<?
			?> class="pagging__item"><?=$NavRecordGroupPrint?></a></li>
<?
		endif;

		$arResult["nStartPage"]--;
		$bFirst = false;
	} while($arResult["nStartPage"] >= $arResult["nEndPage"]);

	if ($arResult["NavPageNomer"] > 1):
		if ($arResult["nEndPage"] > 1):
			if ($arResult["nEndPage"] > 2):
/*?>
		<li><span class="pagging__item">...</span></li>
<?*/
?>
		<li><a class="pagging__item" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=round($arResult["nEndPage"] / 2)?>">...</a></li>
<?
			endif;
?>
		<li><a class="pagging__item" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=1"><?=$arResult["NavPageCount"]?></a></li>
<?
		endif;

?>
		<li class="mr-4"><a class="pagging__arrow pagging__item bg-secondary text-white" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>"><svg width="16" height="16"><use xlink:href="#icon-arrow-left"></use></svg></a></li>
<?
	endif;

else:
	$bFirst = true;

	if ($arResult["NavPageNomer"] > 1):
		if($arResult["bSavePage"]):
?>
			<li class="mr-4"><a class="pagging__arrow pagging__item bg-secondary text-white" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>"><svg width="16" height="16"><use xlink:href="#icon-arrow-left"></use></svg></a></li>
<?
		else:
			if ($arResult["NavPageNomer"] > 2):
?>
			<li class="mr-4"><a class="pagging__arrow pagging__item bg-secondary text-white" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>"><svg width="16" height="16"><use xlink:href="#icon-arrow-left"></use></svg></a></li>
<?
			else:
?>
			<li class="mr-4"><a class="pagging__arrow pagging__item bg-secondary text-white" href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>"><svg width="16" height="16"><use xlink:href="#icon-arrow-left"></use></svg></a></li>
<?
			endif;

		endif;

		if ($arResult["nStartPage"] > 1):
			$bFirst = false;
			if($arResult["bSavePage"]):
?>
			<li><a class="pagging__item" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=1">1</a></li>
<?
			else:
?>
			<li><a class="pagging__item" href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>">1</a></li>
<?
			endif;
			if ($arResult["nStartPage"] > 2):
/*?>
			<li><span class="pagging__item">...</span></li>
<?*/
?>
			<li><a class="pagging__item" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=round($arResult["nStartPage"] / 2)?>">...</a></li>
<?
			endif;
		endif;
	endif;

	do
	{
		if ($arResult["nStartPage"] == $arResult["NavPageNomer"]):
?>
		<li><span class="pagging__item"><?=$arResult["nStartPage"]?></span></li>
<?
		elseif($arResult["nStartPage"] == 1 && $arResult["bSavePage"] == false):
?>
		<li><a href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>" class="pagging__item"><?=$arResult["nStartPage"]?></a></li>
<?
		else:
?>
		<li><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["nStartPage"]?>"<?
			?> class="pagging__item"><?=$arResult["nStartPage"]?></a></li>
<?
		endif;
		$arResult["nStartPage"]++;
		$bFirst = false;
	} while($arResult["nStartPage"] <= $arResult["nEndPage"]);

	if($arResult["NavPageNomer"] < $arResult["NavPageCount"]):
		if ($arResult["nEndPage"] < $arResult["NavPageCount"]):
			if ($arResult["nEndPage"] < ($arResult["NavPageCount"] - 1)):
?>
		<li><a class="pagging__item" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=round($arResult["nEndPage"] + ($arResult["NavPageCount"] - $arResult["nEndPage"]) / 2)?>">...</a></li>
<?
			endif;
?>
		<li><a class="pagging__item" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["NavPageCount"]?>"><?=$arResult["NavPageCount"]?></a></li>
<?
		endif;
?>
		<li class="ml-4"><a class="pagging__arrow pagging__item bg-secondary text-white" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>"><svg width="16" height="16"><use xlink:href="#icon-arrow-right"></use></svg></a></li>
<?
	endif;
endif;
?>
        </ul>
    </div>
</div>