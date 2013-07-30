<?php

namespace gateways\components\gateways;

use gateways\components\BaseGateway;
use gateways\exceptions\InvalidArgumentException;
use gateways\exceptions\NotFoundTransactionException;
use gateways\exceptions\ProcessException;
use gateways\models\enum\Result;
use gateways\models\enum\State;
use gateways\models\Process;
use gateways\models\Request;
use gateways\models\Transaction;

class Robokassa extends BaseGateway {

	public $login;
	public $password1;
	public $password2;
	public $url;
	public $paymentMethod;

	/**
	 * @param \gateways\models\Transaction $transactionModel
	 * @return \gateways\models\Process
	 */
	public function start(Transaction $transactionModel) {
		$processModel = new Process();
		$processModel->transactionId = $transactionModel->id;
		$processModel->state = State::WAIT_VERIFICATION;
		$processModel->result = Result::SUCCEED;

		// Create and fill request model
		$requestModel = new Request();
		$requestModel->url = $this->url;
		$requestModel->params = array(
			'MrchLogin' => $this->login,
			'OutSum' => $transactionModel->amount,
			'InvId' => $transactionModel->id,
			'Desc' => $transactionModel->description,
			'SignatureValue' => md5($this->login . ":" . $transactionModel->amount . ":" . $transactionModel->id . ":" . $this->password1),
			'IncCurrLabel' => $this->paymentMethod,
			'Culture' => 'ru'
		);

		$processModel->requestModel = $requestModel;
		return $processModel;
	}

	/**
	 * @param \gateways\models\Request $requestModel
	 * @return \gateways\models\Process
	 * @throws \gateways\exceptions\NotFoundTransactionException
	 * @throws \gateways\exceptions\InvalidArgumentException
	 * @throws \gateways\exceptions\ProcessException
	 */
	public function check(Request $requestModel) {
		$processModel = new Process();
		$processModel->state = State::COMPLETE;

		// Check required params
		if (empty($requestModel->params['InvId']) || empty($requestModel->params['SignatureValue'])) {
			throw new InvalidArgumentException();
		}

		// Find transaction model
		$processModel->transactionId = (int)$requestModel->params['InvId'];
		$transactionModel = Transaction::model()->findByPk($processModel->transactionId);
		if ($transactionModel == null) {
			throw new NotFoundTransactionException();
		}

		// Generate hash sum
		$md5 = strtoupper(md5($requestModel->params['OutSum'] . ':' . $transactionModel->id . ':' . $this->password2));
		$remoteMD5 = $requestModel->params['SignatureValue'];

		// Check md5 hash
		if ($md5 !== $remoteMD5) {
			throw new ProcessException();
		}

		// Send success result
		$processModel->responseText = 'OK' . $transactionModel->id;
		$processModel->result = Result::SUCCEED;
		return $processModel;
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