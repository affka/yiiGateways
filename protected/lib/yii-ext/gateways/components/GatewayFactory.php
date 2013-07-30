<?php

namespace gateways\components;

use gateways\exceptions\NotFoundGatewayException;
use gateways\GatewaysModule;
use gateways\models\enum\GatewayName;

class GatewayFactory {

	/**
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Фабричный метод для получения экземпляра класса работы с шлюзом
	 * @param string $gatewayName
	 * @param array $params
	 * @return \gateways\components\BaseGateway
	 * @throws \CException
	 */
	public static function get($gatewayName, $params = array()) {
		// Check gateway name
		if (!in_array($gatewayName, GatewayName::getKeys())) {
			throw new NotFoundGatewayException('Gateway `' . $gatewayName . '` is not supported.');
		}

		if (!isset(self::$instances[$gatewayName])) {
			// Create gateway component
			$gatewaysConfigs = GatewaysModule::findInstance()->gateways;
			$config = isset($gatewaysConfigs[$gatewayName])	? $gatewaysConfigs[$gatewayName] : array();
			$config['class'] = GatewayName::getClassName($gatewayName);

			// Merge with custom config
			$config = \CMap::mergeArray($config, $params);

			self::$instances[$gatewayName] = \Yii::createComponent($config, $gatewayName);
		}

		return self::$instances[$gatewayName];
	}


}
