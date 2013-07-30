<?php

namespace gateways\models\enum;

use \gateways\base\ModelEnum;

/**
 * Результат обработки события в платёжной системе
 * @author Vladimir Kozhin <affka@affka.ru>
 */
class Result extends ModelEnum {

    /**
     * Платежное поручение, поданное платежной системой в нашу на проверку, не подтверждено
     */
    const REJECTED = 'rejected';

    /**
     * Платежная система сообщила об успешно проведенном платеже
     */
    const SUCCEED = 'succeed';

    /**
     * Платежная система сообщила об ошибке в процессе платежа
     */
    const FAILED = 'failed';

    /**
     * Платеж отменен плательщиком через платежную систему
     */
    const CANCELED = 'canceled';

    /**
     * Зарегистрирована ошибка в обработке сообщения от ПС
     */
    const ERROR = 'error';

    public static function getList() {
        return array(
            self::REJECTED => 'Платёж отклонён',
            self::SUCCEED => 'Успешно',
            self::FAILED => 'Ошибка в платёжной системе',
            self::CANCELED => 'Платёж отменён',
            self::ERROR => 'Ошибка',
        );
    }

}
