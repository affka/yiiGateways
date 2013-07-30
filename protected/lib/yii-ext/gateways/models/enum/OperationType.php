<?php

namespace gateways\models\enum;

use \gateways\base\ModelEnum;

class OperationType extends ModelEnum {

    const CHARGE = 'charge';
    const WITHDRAW = 'withdraw';
    const CHECK_STATUS = 'check_status';

    public static function getList() {
        return array(
            self::CHARGE => 'Пополенение средств из платежной системы во внутреннюю',
            self::WITHDRAW => 'Вывод средств во внешнюю платежную систему',
            self::CHECK_STATUS => 'Проверка статуса операции во внешней платежной системе',
        );
    }
}
