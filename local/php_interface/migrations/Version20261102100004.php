<?php

namespace Sprint\Migration;

use Bitrix\Main\Config\Option;

class Version20261102100004 extends Version
{
    protected $description = 'Отправитель писем сайта — noreply@reppaopt.ru вместо личного ящика';

    public function up()
    {
        // #DEFAULT_EMAIL_FROM# в почтовых шаблонах; адрес на домене сайта проходит SPF reppaopt.ru
        Option::set('main', 'email_from', 'noreply@reppaopt.ru');
        $this->outSuccess('Отправитель: noreply@reppaopt.ru');
    }

    public function down()
    {
        Option::set('main', 'email_from', 'antyxweb@yandex.ru');
    }
}
