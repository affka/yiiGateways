<?php

namespace gateways\models\enum;

use \gateways\base\ModelEnum;

/**
 * Имена платёжных систем
 * @author Vladimir Kozhin <affka@affka.ru>
 */
class PaymentMethod extends ModelEnum {

	const WEBMONEY = 'webmoney';
	const YANDEXMONEY = 'yandexmoney';
	const VISA = 'visa';
	const MASTERCARD = 'mastercard';
	const QIWI = 'qiwi';
	const BANK = 'bank';
	const OTHER = 'other';

	public static function getData() {
		return array(
			array(
				'label' => 'Электронные деньги',
				'items' => array(
					self::WEBMONEY => 'Webmoney',
					self::YANDEXMONEY => 'Яндекс.деньги',
				),
			),
			array(
				'label' => 'Банковской картой',
				'items' => array(
					self::VISA => 'Visa',
					self::MASTERCARD => 'Master card',
					self::BANK => 'Банковский перевод',
				),
			),
			array(
				'label' => 'Платёжный терминал',
				'items' => array(
					self::QIWI => 'Qiwi',
				),
			),
			self::OTHER => 'Другое',
		);
	}

	public static function getList() {
		$list = array();
		foreach (self::getData() as $key => $item) {
			if (is_array($item)) {
				foreach ($item['items'] as $key2 => $item2) {
					$list[$key2] = $item2;
				}
			} else {
				$list[$key] = $item;
			}
		}
		return $list;
	}
}
