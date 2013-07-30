<?php

namespace gateways\components\gateways;

use \gateways\components\BaseGateway;
use gateways\exceptions\InvalidArgumentException;
use \gateways\exceptions\UnsupportedStateMethodException;
use \gateways\models\enum\Result;
use \gateways\models\enum\State;
use \gateways\models\Process;
use gateways\models\Request;
use gateways\models\Transaction;

require_once \Yii::getPathOfAlias('gateways') . '/vendors/stripe/lib/Stripe.php';

class Stripe extends BaseGateway {

	public $secretApiKey = '';
	public $publicApiKey = '';
	public $userCard = array();

	/**
	 * @param Transaction $transactionModel
	 * @return Process
	 * @throws \gateways\exceptions\InvalidArgumentException
	 */
	public function start(Transaction $transactionModel) {
		if (!isset($this->userCard['number']) || !isset($this->userCard['exp_month'])
			|| !isset($this->userCard['exp_year']) || !isset($this->userCard['cvc'])) {
			throw new InvalidArgumentException('Not find user card or not all fields are filled.');
		}

		// Make response
		$processModel = new Process();
		$processModel->transactionId = $transactionModel->id;

		// Configure
		\Stripe::setApiKey($this->secretApiKey);

		// Send request to stripe server
		try {
			$stripeObject = \Stripe_Charge::create(array(
					"amount" => $transactionModel->amount,
					"currency" => "usd", // @todo
					"description" => $transactionModel->description,
					"card" => $this->userCard,
				)
			);
		} catch (\Stripe_CardError $exception) {
			$processModel->message = $exception->getMessage();
		}

		// Set state
		$processModel->state = State::COMPLETE;
		$processModel->result = isset($stripeObject) && $stripeObject instanceof \Stripe_Object ? Result::SUCCEED : Result::ERROR;

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
	 * @return void
	 * @throws \gateways\exceptions\UnsupportedStateMethodException
	 */
	public function end($result, Request $requestModel) {
		throw new UnsupportedStateMethodException();
	}

}
