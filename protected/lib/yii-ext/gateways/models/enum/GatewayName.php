<?php

namespace gateways\models\enum;

use \gateways\base\ModelEnum;

/**
 * Имена платёжных систем
 * @author Vladimir Kozhin <affka@affka.ru>
 */
class GatewayName extends ModelEnum {

    const MANUAL = 'manual';
	const ROBOKASSA = 'robokassa';
	const PORTMONE = 'portmone';
	const STRIPE = 'stripe';
	const PAYPAL = 'paypal';

	public static function getData() {
		return array(
			self::MANUAL => array(
				'label' => 'Ручное',
				'className' => '\gateways\components\gateways\Manual',
				'supportedPaymentMethods' => array(
					PaymentMethod::WEBMONEY => PaymentMethod::WEBMONEY,
					PaymentMethod::YANDEXMONEY => PaymentMethod::YANDEXMONEY,
					PaymentMethod::BANK => PaymentMethod::BANK,
				),
			),
			self::ROBOKASSA => array(
				'label' => 'Robokassa',
				'className' => '\gateways\components\gateways\Robokassa',
				'supportedPaymentMethods' => array(
					PaymentMethod::QIWI => 'Qiwi29OceanR',
					PaymentMethod::YANDEXMONEY => 'YandexMerchantR',
					PaymentMethod::WEBMONEY => 'WMRM',
					PaymentMethod::VISA => 'BANKOCEAN2R',
					PaymentMethod::MASTERCARD => 'BANKOCEAN2R',
				),
			),
			self::PORTMONE => array(
				'label' => 'Портмоне',
				'className' => '\gateways\components\gateways\Portmone',
				'supportedPaymentMethods' => array(
					PaymentMethod::WEBMONEY => PaymentMethod::WEBMONEY,
					PaymentMethod::YANDEXMONEY => PaymentMethod::YANDEXMONEY,
					PaymentMethod::BANK => PaymentMethod::BANK,
				),
			),
			self::STRIPE => array(
				'label' => 'Stripe',
				'className' => '\gateways\components\gateways\Stripe',
				'supportedPaymentMethods' => array(
					PaymentMethod::VISA => PaymentMethod::VISA,
					PaymentMethod::MASTERCARD => PaymentMethod::MASTERCARD,
				),
			),
			self::PAYPAL => array(
				'label' => 'Stripe',
				'className' => '\gateways\components\gateways\PayPal',
				'supportedPaymentMethods' => array(
					PaymentMethod::WEBMONEY => PaymentMethod::WEBMONEY,
					PaymentMethod::YANDEXMONEY => PaymentMethod::YANDEXMONEY,
					PaymentMethod::OTHER => PaymentMethod::OTHER,
				),
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

	public static function getClassName($key) {
		$data = self::getData();
		return isset($data[$key]) ? $data[$key]['className'] : null;
	}
}
