<?php

namespace gateways\components;

use gateways\GatewaysModule;
use gateways\models\Request;
use gateways\models\Transaction;

abstract class BaseGateway extends \CComponent {

	/**
	 * Флаг, отображающий включена ли платёжный шлюз. По-умолчанию выключена.
	 * @var boolean
	 */
	public $enable = false;

	/**
	 * Способ оплаты. Поле актуально только для платёжных интеграторов, где есть выбор способа оплаты.
	 * @var string
	 */
	public $paymentMethod;

	/**
	 * Флаг, отображающий включен ли платёжный шлюз для реальных транзакций.
	 * По-умолчанию включен режим разработчика.
	 * @var boolean
	 */
	public $enableProductionMode = false;

	/**
	 * Имя платёжного шлюза, одно из значений enum GatewayName
	 * @var
	 */
	private $gatewayName;

	/**
	 * Constructor.
	 * @param mixed $gatewayName Имя платёжного шлюза, одно из значений enum GatewayName
	 */
	public function __construct($gatewayName) {
		$this->gatewayName = $gatewayName;
	}

	/**
	 * @param \gateways\models\Transaction $transactionModel
	 * @return \gateways\models\Process
	 */
	abstract public function start(Transaction $transactionModel);

	/**
	 * @param \gateways\models\Request $requestModel
	 * @return \gateways\models\Process
	 */
	abstract public function check(Request $requestModel);

	/**
	 * @param string$result
	 * @param \gateways\models\Request $requestModel
	 * @return \gateways\models\Process
	 */
	abstract public function end($result, Request $requestModel);

	/**
	 * Возвращает имя платёжного шлюза
	 * @return string
	 */
	public function getName() {
		return $this->gatewayName;
	}

	/**
	 * Адрес, по которому должна направить пользователя платёжная система при успешной оплате
	 * @return string
	 */
	public function getSuccessUrl() {
		return GatewaysModule::findInstance()->getSuccessUrl($this->getName());
	}

	/**
	 * Адрес, по которому должна направить пользователя платёжная система при неудачной оплате
	 * @return string
	 */
	public function getFailureUrl() {
		return GatewaysModule::findInstance()->getFailureUrl($this->getName());
	}

	/**
	 * Отсылает сообщения лога для записи его в БД
	 * @param string $message
	 * @param array $stateData
	 */
	protected function log($message, $level= 'log', $transactionId = null, $stateData = array()) {
		GatewaysModule::findInstance()->api->log($message, $level, $transactionId, $stateData);
	}

	/**
	 * Отправляет POST запрос на указанный адрес
	 * @param string $url
	 * @param array|string $data
	 * @return string
	 */
	protected function httpSend($url, $data = '', $headers = array()) {
		$headers = array_merge(array(
			'Content-Type' => 'application/x-www-form-urlencoded',
		), $headers);

		$headersString = '';
		foreach ($headers as $key => $value) {
			$headersString .= trim($key) . ": " . trim($value) . "\n";
		}

		return file_get_contents($url, false, stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => $headersString . "\n",
				'content' => is_array($data) ? http_build_query($data) : $data,
			),
		)));
	}
}
