<?php

namespace gateways\components\gateways;

use \gateways\components\BaseGateway;
use gateways\components\LinkParser;
use gateways\exceptions\InvalidArgumentException;
use gateways\exceptions\NotFoundTransactionException;
use gateways\exceptions\ProcessException;
use \gateways\exceptions\UnsupportedStateMethodException;
use \gateways\models\enum\Result;
use \gateways\models\enum\State;
use \gateways\models\Process;
use gateways\models\Request;
use gateways\models\Transaction;

class PayPal extends BaseGateway {

	/**
	 * Api url. For developers: https://api.sandbox.paypal.com
	 * For production: https://api.paypal.com
	 * @var string
	 */
	public $apiUrl = '';

	/**
	 * Client ID. Example: EOJ2S-Z6OoN_le_KS1d75wsZ6y0SFdVsY9183IvxFyZp
	 * @var string
	 */
	public $clientId = '';

	/**
	 * Secret key. Example: EClusMEUk8e9ihI7ZdVLF5cZ6y0SFdVsY9183IvxFyZp
	 * @var string
	 */
	public $secretKey = '';

	public $userEmail = '';

	/**
	 * @param Transaction $transactionModel
	 * @return \gateways\models\Process
	 * @throws \gateways\exceptions\InvalidArgumentException
	 * @throws \gateways\exceptions\ProcessException
	 */
	public function start(Transaction $transactionModel) {
		// Make response
		$processModel = new Process();
		$processModel->transactionId = $transactionModel->id;

		// Make and send payment call
		$requestData = array(
			'intent' => 'sale',
			'payer' => array(
				'payment_method' => 'paypal',
			),
			'transactions' => array(
				array(
					'amount' => array(
						'total' => $transactionModel->amount,
						'currency' => 'USD', // @todo
					),
					'description' => $transactionModel->description,
				),
			),
			'redirect_urls' => array(
				'return_url' => $this->getSuccessUrl(),
				'cancel_url' => $this->getFailureUrl(),
			),
		);
		$paymentResponseData = $this->httpSend($this->apiUrl . '/v1/payments/payment', json_encode($requestData), array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $this->getAuthData()->access_token,
		));
		$paymentResponseObject = $paymentResponseData ? json_decode($paymentResponseData) : null;
		if (!$paymentResponseObject) {
			throw new ProcessException('Wrong response data from paypal payment request.');
		}

		// Set state
		$processModel->state = State::WAIT_VERIFICATION;
		$processModel->result = isset($paymentResponseObject->state) && $paymentResponseObject->state === 'created' ? Result::SUCCEED : Result::ERROR;
		$processModel->gatewayData = $paymentResponseObject;

		// Get redirect link
		$approvalUrl = null;
		foreach ($paymentResponseObject->links as $link) {
			if ($link->rel === 'approval_url') {
				$approvalUrl = $link;
				break;
			}
		}
		if (!$approvalUrl) {
			throw new ProcessException('Not find redirect link.');
		}

		$approvalLink = new LinkParser($approvalUrl->href);

		// Save token as transaction ID
		if (!isset($approvalLink->parameters['token'])) {
			throw new ProcessException('Not find token in approval url.');
		}
		$processModel->outsideTransactionId = $approvalLink->parameters['token'];

		// Set redirect request
		$requestModel = new Request();
		$requestModel->params = $approvalLink->parameters;

		// Clean link
		$approvalLink->parameters = array();

		$requestModel->url = (string) $approvalLink;
		$requestModel->method = Request::REQUEST_METHOD_GET;
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
	 * @throws \gateways\exceptions\ProcessException
	 * @throws \gateways\exceptions\InvalidArgumentException
	 * @throws \gateways\exceptions\NotFoundTransactionException
	 */
	public function end($result, Request $requestModel) {
		if (!isset($requestModel->params['token']) || !isset($requestModel->params['PayerID'])) {
			throw new InvalidArgumentException('Invalid request arguments. Need `token` and `PayerID`.');
		}

		$processModel = new Process();
		$processModel->state = State::COMPLETE;

		// Find transaction model
		$processModel->outsideTransactionId = $requestModel->params['token'];
		$transactionModel = Transaction::model()->findByAttributes(array(
			'outsideTransactionId' => $processModel->outsideTransactionId,
		));
		if ($transactionModel === null) {
			throw new NotFoundTransactionException();
		}

		$processModel->transactionId = $transactionModel->id;

		// Get execute link
		$executeUrl = null;
		foreach ($transactionModel->gatewayData->links as $link) {
			if ($link->rel === 'execute') {
				$executeUrl = $link;
				break;
			}
		}
		if (!$executeUrl) {
			throw new ProcessException('Not find execute link.');
		}

		// Send execute payment request
		$requestData = array(
			'payer_id' => $requestModel->params['PayerID'],
		);
		$paymentResponseData = $this->httpSend($executeUrl->href, json_encode($requestData), array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $this->getAuthData()->access_token,
		));
		$paymentResponseObject = $paymentResponseData ? json_decode($paymentResponseData) : null;
		if (!$paymentResponseObject) {
			throw new ProcessException('Wrong response data from paypal payment request.');
		}

		// Set state
		$processModel->result = isset($paymentResponseObject->state) && $paymentResponseObject->state === 'approved' ? Result::SUCCEED : Result::ERROR;

		return $processModel;
	}

	/**
	 * @return object
	 * @throws \gateways\exceptions\ProcessException
	 */
	private function getAuthData() {
		// Send auth request
		$authResponseData = $this->httpSend($this->apiUrl . '/v1/oauth2/token', array(
			'grant_type' => 'client_credentials',
		), array(
			'Accept' => 'application/json',
			'Accept-Language' => 'en_US',
			'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->secretKey),
		));
		$authResponseObject = $authResponseData ? json_decode($authResponseData) : null;

		if (!$authResponseObject || !$authResponseObject->access_token) {
			throw new ProcessException('Wrong response data from paypal auth request.');
		}

		return $authResponseObject;
	}

}
