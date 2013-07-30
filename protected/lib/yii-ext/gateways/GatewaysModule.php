<?php

namespace gateways;

use \gateways\exceptions\GatewayException;
use \gateways\models\Request;

/**
 * Class GatewaysModule
 * @property \gateways\components\Api $api
 * @property string $successUrl
 * @property string $failureUrl
 */
class GatewaysModule extends \CWebModule {

	public $defaultController = 'api';
	public $controllerNamespace = '\gateways\controllers';

	/**
	 * Конфигурация платёжный систем в формате gatewayName => array(param1, ..)
	 * @var array
	 */
	public $gateways = array();

	/**
	 * Поиск экземпляра модуля в приложении.
	 * @return \gateways\GatewaysModule
	 * @throws exceptions\GatewayException
	 */
	public static function findInstance() {
		if (\Yii::app()->hasModule('gateways')) {
			return \Yii::app()->getModule('gateways');
		}

		throw new GatewayException('Not find gateway module instance.');
	}

	/**
	 * Адрес, по которому должна направить пользователя платёжная система при успешной оплате
	 * @param string $gatewayName
	 * @return string
	 */
	public function getSuccessUrl($gatewayName) {
		return \Yii::app()->createAbsoluteUrl('/gateways/api/success', array('gatewayName' => $gatewayName));
	}

	/**
	 * Адрес, по которому должна направить пользователя платёжная система при неудачной оплате
	 * @param string $gatewayName
	 * @return string
	 */
	public function getFailureUrl($gatewayName) {
		return \Yii::app()->createAbsoluteUrl('/gateways/api/failure', array('gatewayName' => $gatewayName));
	}

	public function redirectPost(Request $requestModel) {
		// Check simple redirect
		if ($requestModel->method === Request::REQUEST_METHOD_GET && empty($requestModel->params)) {
			\Yii::app()->controller->redirect($requestModel->url);
			return;
		}

		\Yii::app()->controller->renderPartial('gateways.views.redirectPost', array(
			'requestModel' => $requestModel,
		));
		\Yii::app()->end();
	}

	public function onTransactionCreate($event) {
		$this->raiseEvent(__FUNCTION__, $event);
	}

	public function onTransactionChangeState($event) {
		$this->raiseEvent(__FUNCTION__, $event);
	}

	public function onTransactionComplete($event) {
		$this->raiseEvent(__FUNCTION__, $event);
	}

	public function onSuccessRequest($event) {
		$this->raiseEvent(__FUNCTION__, $event);
	}

	public function onFailureRequest($event) {
		$this->raiseEvent(__FUNCTION__, $event);
	}
}