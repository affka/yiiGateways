<?php

namespace gateways\controllers;

use gateways\GatewaysModule;
use gateways\models\ChargeRequest;
use gateways\models\enum\Result;
use gateways\models\Request;

class ApiController extends \CController {

	public function actionCheck($gatewayName) {
		$processModel = GatewaysModule::findInstance()->api->check($gatewayName, $this->getPaymentRequestModel());
		echo $processModel->responseText;
	}

	public function actionSuccess($gatewayName) {
		$processModel = GatewaysModule::findInstance()->api->end($gatewayName, Result::SUCCEED, $this->getPaymentRequestModel());

		if ($processModel->result === Result::SUCCEED) {
			// Trigger success event
			$event = new \CEvent($this, $processModel);
			$this->module->onSuccessRequest($event);
		} else {
			// Trigger failure event
			$event = new \CEvent($this, $processModel);
			$this->module->onFailureRequest($event);
		}
	}

	public function actionFailure($gatewayName) {
		$processModel = GatewaysModule::findInstance()->api->end($gatewayName, Result::FAILED, $this->getPaymentRequestModel());

		// Trigger failure event
		$event = new \CEvent($this, $processModel);
		$this->module->onFailureRequest($event);
	}

	public function actionCharge() {
		$chargeRequest = new ChargeRequest();
		$chargeRequest->attributes = $_POST;

		// Validate data, send errors
		if (!$chargeRequest->validate()) {
			echo \CJSON::encode(array(
				'errors' => $chargeRequest,
				'process' => array(),
			));
			return;
		}

		$processModel = GatewaysModule::findInstance()->api->charge($chargeRequest);
		echo \CJSON::encode(array(
			'errors' => array(),
			'process' => $processModel->attributes,
		));
	}

	/**
	 * @return \gateways\models\Request
	 */
	private function getPaymentRequestModel() {
		$request = \Yii::app()->request;
		$port = $request->port && $request->port !== 80 ? ':' . $request->port : '';
		$url = $request->hostInfo . $port . str_replace('?' . $request->queryString, '', $request->requestUri);

		$paymentRequestModel = new Request();
		$paymentRequestModel->method = $request->requestType;
		$paymentRequestModel->url = $url;
		$paymentRequestModel->params = array_merge($_GET, $_POST);

		return $paymentRequestModel;
	}
}