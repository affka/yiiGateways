<?php

namespace gateways\components\gateways;

use \gateways\components\BaseGateway;
use \gateways\exceptions\UnsupportedStateMethodException;
use \gateways\models\enum\GatewayName;
use \gateways\models\enum\Result;
use \gateways\models\enum\State;
use \gateways\models\Process;
use gateways\models\Request;
use gateways\models\Transaction;

class Manual extends BaseGateway {

	/**
	 * @param \gateways\models\Transaction $transactionModel
	 * @return \gateways\models\Process
	 */
	public function start(Transaction $transactionModel) {
		$processModel = new Process();
		$processModel->transactionId = $transactionModel->id;
		$processModel->state = State::WAIT_RESULT;
		$processModel->result = Result::SUCCEED;

		return $processModel;
	}

	/**
	 * @param \gateways\models\Request $requestModel
	 * @return void
	 * @throws \gateways\exceptions\UnsupportedStateMethodException
	 */
	public function check(Request $requestModel) {
		throw new UnsupportedStateMethodException();
	}

	/**
	 * @param string $result
	 * @param \gateways\models\Request $requestModel
	 * @return \gateways\models\Process
	 */
	public function end($result, Request $requestModel) {
		$processModel = new Process();
		$processModel->state = State::COMPLETE;
		$processModel->result = $result;

		return $processModel;
	}

}
