<?php

namespace gateways\models;

use gateways\models\enum\Result;
use gateways\models\enum\State;

/**
 * @property string $result
 * @property string $state
 */
class Process extends \CFormModel {

	/**
	 * Внутренний идентификатор транзакции (в БД)
	 * @var integer $transactionId
	 */
	public $transactionId;

	/**
	 * Внешний идентификатор транзакции (генерируется платёжной системой)
	 * @var string $outsideTransactionId
	 */
	public $outsideTransactionId;

	/**
	 * Результат изменения состояния
	 * @var string $result
	 */
	public $result;

	/**
	 * Текущее состояние
	 * @var string $state
	 */
	public $state;

    /**
     * Запрос, который необходимо послать платёжной системе. Как правило, возвращается при начале
     * операции (состояние WAIT_VERIFICATION), для получения формы инициализации платежа, чтобы передать ее в браузер.
     * @var Request $requestModel
     */
    public $requestModel;

	/**
	 * Строка (обычно это текст, xml или json), которую необходимо передать платёжной системе. Как правило, возвращается
	 * как ответ при проверке статуса или завершения транзакции.
	 * @var string $responseText
	 */
	public $responseText;

	/**
	 * Строка/текст, который можно отобразить пользователю
	 * @var string $message
	 */
	public $message;

	/**
	 * Массив с произвольными данными о транзакции, специфичные только для текущей платёжной системы
	 * @var array|object $gatewayData
	 */
	public $gatewayData;

	/**
	 * @return array
	 */
	public function rules() {
		return array(
			array('transactionId, result, state', 'required'),
			array('transactionId', 'numerical', 'integerOnly' => true),
			array('result', 'in', 'range' => Result::getKeys()),
			array('state', 'in', 'range' => State::getKeys()),
			array('requestModel', 'safe'),
			array('message, responseText', 'type', 'type' => 'string'),
			array('gatewayData', 'safe'),
		);
	}
}