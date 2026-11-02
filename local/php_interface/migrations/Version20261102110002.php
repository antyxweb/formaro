<?php

namespace Sprint\Migration;

use Bitrix\Main\Config\Option;

class Version20261102110002 extends Version
{
    protected $description = 'Почта в настройках Битрикса: отправитель, сайт, магазин и уведомления админа — order@reppaopt.ru';

    private const EMAIL = 'order@reppaopt.ru';

    public function up()
    {
        // #DEFAULT_EMAIL_FROM# — отправитель и получатель служебных писем
        Option::set('main', 'email_from', self::EMAIL);
        // #SALE_EMAIL# — отправитель писем магазина о заказах
        Option::set('sale', 'order_email', self::EMAIL);
        // Адрес сайта s1 (Настройки → Сайты)
        (new \CSite())->Update('s1', ['EMAIL' => self::EMAIL]);
        // Получатели уведомлений администратору площадки
        Option::set('formaro.cabinet', 'mail_admin_emails', self::EMAIL);

        $this->outSuccess('Почта в настройках: %s', self::EMAIL);
    }

    public function down()
    {
        Option::set('main', 'email_from', 'noreply@reppaopt.ru');
    }
}
