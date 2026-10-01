<?php
$arUrlRewrite=array (
  0 => 
  array (
    'CONDITION' => '#^\\/?\\/mobileapp/jn\\/(.*)\\/.*#',
    'RULE' => 'componentName=$1',
    'ID' => NULL,
    'PATH' => '/bitrix/services/mobileapp/jn.php',
    'SORT' => 100,
  ),
  2 => 
  array (
    'CONDITION' => '#^/bitrix/services/ymarket/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/bitrix/services/ymarket/index.php',
    'SORT' => 100,
  ),
  25 => 
  array (
    'CONDITION' => '#^/partners/([^/]+)/catalog/[^?]*\\??#',
    'RULE' => 'PARTNER_CODE=$1&',
    'ID' => 'formaro:catalog',
    'PATH' => '/partners/catalog.php',
    'SORT' => 100,
  ),
  4 => 
  array (
    'CONDITION' => '#^/partners/#',
    'RULE' => '',
    'ID' => 'bitrix:news',
    'PATH' => '/partners/index.php',
    'SORT' => 100,
  ),
  5 => 
  array (
    'CONDITION' => '#^/catalog/#',
    'RULE' => '',
    'ID' => 'formaro:catalog',
    'PATH' => '/catalog/index.php',
    'SORT' => 100,
  ),
  6 => 
  array (
    'CONDITION' => '#^/cabinet/#',
    'RULE' => '',
    'ID' => 'formaro:cabinet.partner',
    'PATH' => '/cabinet/index.php',
    'SORT' => 100,
  ),
  1 => 
  array (
    'CONDITION' => '#^/rest/#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/bitrix/services/rest/index.php',
    'SORT' => 100,
  ),
  3 => 
  array (
    'CONDITION' => '#^/news/#',
    'RULE' => '',
    'ID' => 'bitrix:news',
    'PATH' => '/news/index.php',
    'SORT' => 100,
  ),
  20 => 
  array (
    'CONDITION' => '#^/for-buyers/#',
    'RULE' => '',
    'ID' => 'formaro:content.section',
    'PATH' => '/for-buyers/index.php',
    'SORT' => 100,
  ),
  21 => 
  array (
    'CONDITION' => '#^/for-partners/#',
    'RULE' => '',
    'ID' => 'formaro:content.section',
    'PATH' => '/for-partners/index.php',
    'SORT' => 100,
  ),
  22 => 
  array (
    'CONDITION' => '#^/about/#',
    'RULE' => '',
    'ID' => 'formaro:content.section',
    'PATH' => '/about/index.php',
    'SORT' => 100,
  ),
  23 => 
  array (
    'CONDITION' => '#^/support/#',
    'RULE' => '',
    'ID' => 'formaro:content.section',
    'PATH' => '/support/index.php',
    'SORT' => 100,
  ),
  24 => 
  array (
    'CONDITION' => '#^/guide/#',
    'RULE' => '',
    'ID' => 'formaro:content.section',
    'PATH' => '/guide/index.php',
    'SORT' => 100,
  ),
);
