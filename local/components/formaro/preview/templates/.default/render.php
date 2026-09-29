<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Шаблон сайта со своими данными: include файла шаблона компонента с
 * заданными $arResult/$arParams вместо запуска самого компонента (он
 * читал бы запись из базы). $this внутри шаблона — этот объект.
 */
if (!class_exists('FormaroPreviewTemplate')) {
    class FormaroPreviewTemplate
    {
        public function setFrameMode($mode): void
        {
        }

        public function render(string $file, array $arResult, array $arParams): void
        {
            global $APPLICATION;
            $templateFolder = substr(dirname($file), strlen($_SERVER['DOCUMENT_ROOT']));
            if (is_file(dirname($file) . '/style.css')) {
                $APPLICATION->SetAdditionalCSS($templateFolder . '/style.css');
            }
            if (is_file(dirname($file) . '/script.js')) {
                $APPLICATION->AddHeadScript($templateFolder . '/script.js');
            }
            include $file;
        }
    }
}
