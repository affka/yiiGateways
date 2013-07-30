<?php

namespace gateways\models\enum;

use \gateways\base\ModelEnum;

/**
 * Состояние платежной операции
 * @author Vladimir Kozhin <affka@affka.ru>
 */
class State extends ModelEnum {

    /**
     * Создано платежное поручение, но еще не отправлено в платежную систему
     */
    const CREATED = 'created';

    /**
     * Операция ожидает запроса верификации от платежной системы
     */
    const WAIT_VERIFICATION = 'wait_verification';

    /**
     * Операция ожидает получения окончательного результата от платежной системы
     */
    const WAIT_RESULT = 'wait_result';

    /**
     * Результат операции известен, получен о ПС или выставлен оператором вручную
     */
    const COMPLETE = 'complete';

    /**
     * Результат операции известен, отправленответ к ПС, ждем редиректа от ПС.
     * Необходимо для некоторых ПС.
     */
    const COMPLETE_VERIFY = 'complete_verify';

    public static function getList() {
        return array(
            self::CREATED => 'Создан',
            self::WAIT_VERIFICATION => 'Ожидание подтверждения',
            self::WAIT_RESULT => 'В обработке', //'Ожидание результата',
            self::COMPLETE => 'Выполнен',
            self::COMPLETE_VERIFY => 'Выполнен, требуется ответ',
        );
    }

	public static function isCompleteState($state) {
		return in_array($state, array(self::COMPLETE, self::COMPLETE_VERIFY));
	}

}
