<?php

namespace gateways\models\enum;

use \gateways\base\ModelEnum;

/**
 * Имена платёжных систем
 * @author Vladimir Kozhin <affka@affka.ru>
 */
class DataType extends ModelEnum {

    const GATEWAY = 'gateway';
	const USER = 'user';

	public static function getData() {
		return array(
			self::GATEWAY => array(
				'label' => 'Gateway',
				'attributeName' => 'gatewayData',
			),
			self::USER => array(
				'label' => 'User',
				'attributeName' => 'userData',
			),
		);
	}

	public static function getList() {
		$list = array();
		foreach (self::getData() as $key => $params) {
			$list[$key] = $params['label'];
		}
		return $list;
	}

	public static function getAttributeName($key) {
		$data = self::getData();
		return isset($data[$key]) ? $data[$key]['attributeName'] : null;
	}
}
