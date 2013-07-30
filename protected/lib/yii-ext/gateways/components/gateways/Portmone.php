<?php

namespace gateways\components\gateways;

use gateways\components\BaseGateway;
use gateways\exceptions\InvalidArgumentException;
use gateways\exceptions\NotFoundTransactionException;
use gateways\exceptions\ProcessException;
use gateways\exceptions\UnsupportedStateMethodException;
use gateways\models\enum\Result;
use gateways\models\enum\State;
use gateways\models\Process;
use gateways\models\Request;
use gateways\models\Transaction;

class Portmone extends BaseGateway {

	public $payeeId;
	public $login;
	public $password;
	public $url;

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
		$requestModel->method = Request::REQUEST_METHOD_POST;
		$requestModel->params = array(
			'payee_id' => $this->payeeId,
			'shop_order_number' => $transactionModel->id,
			'bill_amount' => $transactionModel->amount,
			'description' => $transactionModel->description,
			'success_url' => $this->getSuccessUrl(),
			'failure_url' => $this->getFailureUrl(),
			'lang' => 'ru',
			'encoding' => 'UTF-8',
		);

		$processModel->requestModel = $requestModel;
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
	 * @throws \gateways\exceptions\NotFoundTransactionException
	 * @throws \gateways\exceptions\InvalidArgumentException
	 * @throws \gateways\exceptions\ProcessException
	 */
	public function end($result, Request $requestModel) {
		$processModel = new Process();
		$processModel->state = State::COMPLETE;

		if ($result === Result::FAILED) {
			$processModel->result = Result::FAILED;
			return $processModel;
		}

		// Check required params
		if (empty($requestModel->params['SHOPORDERNUMBER'])) {
			throw new InvalidArgumentException();
		}

		// Find transaction model
		$processModel->outsideTransactionId = (int)$requestModel->params['SHOPORDERNUMBER'];
		$transactionModel = Transaction::model()->findByPk($processModel->outsideTransactionId);
		if ($transactionModel == null) {
			throw new NotFoundTransactionException();
		}

		// Check transaction
		$requestData = array(
			'method' => 'result',
			'payee_id' => $this->payeeId,
			'login' => $this->login,
			'password' => $this->password,
			'shop_order_number' => $transactionModel->id,
			'encoding' => 'UTF-8',
		);
		$responseContent = $this->httpSend($this->url, $requestData);

		// Log request and response data
		$this->log('end response', 'log', $transactionModel->id, array(
			'request' => $requestData,
			'response' => $responseContent,
		));

		// Check response format
		$xml = @new \SimpleXMLElement($responseContent);

		if (!isset($xml->orders) || count($xml->orders) !== 1) {
			throw new InvalidArgumentException();
		}

		// Response order
		$order = $xml->orders[0];

		// Check response order
		if ((int)$order->shop_order_number !== $transactionModel->id || $order->status !== 'PAYED') {
			throw new ProcessException('Not return required transaction or order is not PAYED');
		}

		// Send success result
		$processModel->result = Result::SUCCEED;
		return $processModel;
	}
}