<?php
/**
 * Низ текстовой страницы (см. text_page_top.php): меню раздела справа —
 * меню типа left (.left.menu.php ближайшей папки), заголовок — название
 * раздела из .section.php той же папки.
 *
 * @global CMain $APPLICATION
 */
$textPageMenuTitle = '';
$textPageDir = rtrim($APPLICATION->GetCurDir(), '/');
while ($textPageDir !== '') {
    $textPagePath = $_SERVER['DOCUMENT_ROOT'] . $textPageDir;
    if (is_file($textPagePath . '/.left.menu.php')) {
        if (is_file($textPagePath . '/.section.php')) {
            $sSectionName = '';
            include $textPagePath . '/.section.php';
            $textPageMenuTitle = (string)$sSectionName;
        }
        break;
    }
    $textPageDir = substr($textPageDir, 0, (int)strrpos($textPageDir, '/'));
}
?>
                    </article>
                </div>

                <div class="col-12 col-xl-3">
                    <?php $APPLICATION->IncludeComponent(
                        'bitrix:menu',
                        'section-sidebar',
                        [
                            'ROOT_MENU_TYPE' => 'left',
                            'MAX_LEVEL' => '1',
                            'USE_EXT' => 'N',
                            'ALLOW_MULTI_SELECT' => 'N',
                            'MENU_CACHE_TYPE' => 'N',
                            'TITLE' => $textPageMenuTitle,
                        ],
                        false,
                        ['HIDE_ICONS' => 'Y']
                    ); ?>
                </div>
            </div>
        </div>
    </div>
</section>
