<?php

namespace gateways\components;

use gateways\exceptions\GatewayException;
use gateways\exceptions\NotFoundGatewayException;
use gateways\exceptions\NotFoundTransactionException;
use gateways\exceptions\ValidateException;
use gateways\GatewaysModule;
use gateways\models\enum\OperationType;
use gateways\models\enum\Result;
use gateways\models\enum\State;
use gateways\models\Log;
use gateways\models\Process;
use gateways\models\Request;
use gateways\models\Transaction;
use gateways\models\ChargeRequest;

class Api extends \CApplicationComponent {

	/**
	 * Start charge operation and return paymentModel. Frontend should be send payment request
	 * to payment system, if state is WAIT_VERIFICATION
	 * @param \gateways\models\ChargeRequest $chargeRequest
	 * @return \gateways\models\Process
	 * @throws \gateways\exceptions\NotFoundGatewayException
	 * @throws \gateways\exceptions\ValidateException
	 * @throws \Exception
	 */
	public function charge(ChargeRequest $chargeRequest) {
		// Validate transaction data
		if (!$chargeRequest->validate()) {
			throw new ValidateException();
		}

		// Create and fill transaction model
		$transactionModel = new Transaction();
		$transactionModel->setScenario('create');
		$transactionModel->attributes = $chargeRequest->getAttributes(array(
			'amount',
			'description',
			'gatewayName',
			'userData',
		));
		$transactionModel->operationType = OperationType::CHARGE;
		$transactionModel->state = State::CREATED;

		// Save transaction in database
		if ($transactionModel->save()) {
			// Get gateway component instance
			$gatewayComponent = GatewayFactory::get($chargeRequest->gatewayName, $chargeRequest->gatewayParams);

			if ($gatewayComponent->enable !== true) {
				throw new NotFoundGatewayException('Gateway is disabled');
			}

			// Run start method in gateway
			try {
				$processModel = $gatewayComponent->start($transactionModel);
			} catch (\Exception $e) {
				$this->log('Exception when run gateway start.', 'error', $transactionModel->id, array(
					'exception' => (string)$e,
				));
				throw $e;
			}

			// Update transaction status
			$transactionModel->setScenario('update');
			$transactionModel->attributes = $processModel->getAttributes(array(
				'outsideTransactionId',
				'state',
				'result',
				'gatewayData',
			));

			// Save with transaction and trigger event
			$db = \Yii::app()->db;
			$transaction = $db->currentTransaction === null ? $db->beginTransaction() : null;
			try {
				// Save
				$transactionModel->save(false);

				// Trigger change state event
				$event = new \CModelEvent($transactionModel);
				GatewaysModule::findInstance()->onTransactionCreate($event);

				if ($transaction !== null) {
					$transaction->commit();
				}
			} catch (\Exception $e) {
				if ($transaction !== null) {
					$transaction->rollback();
				}
				$processModel->result = Result::ERROR;
			}
		} else {
			// set error
			$processModel = new Process();
			$processModel->state = State::CREATED;
			$processModel->result = Result::ERROR;
		}

		return $processModel;
	}

	/**
	 * @param string $gatewayName
	 * @param \gateways\models\Request $requestModel
	 * @return \gateways\models\Process
	 * @throws \gateways\exceptions\ValidateException
	 * @throws \gateways\exceptions\NotFoundTransactionException
	 * @throws \Exception
	 */
	public function check($gatewayName, Request $requestModel) {
		// Get gateway component instance
		$gatewayComponent = GatewayFactory::get($gatewayName);

		if ($gatewayComponent->enable !== true) {
			throw new NotFoundGatewayException('Gateway is disabled');
		}

		// Run check method in gateway
		try {
			$processModel = $gatewayComponent->check($requestModel);
		} catch (\Exception $e) {
			$this->log('Exception when run gateway check method.', 'error', null, array(
				'exception' => (string)$e,
				'gatewayName' => $gatewayName,
				'requestModel' => $requestModel,
			));
			throw $e;
		}

		// Get transaction and check it exists
		$transactionModel = Transaction::model()->findByPk($processModel->transactionId);
		if ($transactionModel === null) {
			throw new NotFoundTransactionException();
		}

		// Get previous state for check changed state
		$previousState = $transactionModel->state;

		// Update status
		$transactionModel->setScenario('update');
		$transactionModel->attributes = $processModel->getAttributes(array(
			'outsideTransactionId',
			'state',
			'result',
			'gatewayData',
		));

		// Validate transaction
		if (!$transactionModel->validate()) {
			throw new ValidateException();
		}

		// Save with transaction and trigger event
		$db = \Yii::app()->db;
		$transaction = $db->currentTransaction === null ? $db->beginTransaction() : null;
		try {
			// Save
			$transactionModel->save(false);

			// Trigger change state event
			$event = new \CModelEvent($transactionModel);
			GatewaysModule::findInstance()->onTransactionChangeState($event);

			// Trigger complete event
			if ($previousState !== State::COMPLETE && $transactionModel->state === State::COMPLETE && $transactionModel->result === Result::SUCCEED) {
				$event = new \CModelEvent($transactionModel);
				GatewaysModule::findInstance()->onTransactionComplete($event);
			}

			if ($transaction !== null) {
				$transaction->commit();
			}
		} catch (\Exception $e) {
			if ($transaction !== null) {
				$transaction->rollback();
			}
			$processModel->result = Result::ERROR;
		}

		return $processModel;
	}

	/**
	 * @param string $gatewayName
	 * @param string $result
	 * @param \gateways\models\Request $requestModel
	 * @return \gateways\models\Process
	 * @throws \gateways\exceptions\NotFoundTransactionException
	 * @throws \Exception
	 */
	public function end($gatewayName, $result, Request $requestModel) {
		// Get gateway component instance
		$gatewayComponent = GatewayFactory::get($gatewayName);

		if ($gatewayComponent->enable !== true) {
			throw new NotFoundGatewayException('Gateway is disabled');
		}

		// Run end method in gateway
		try {
			$processModel = $gatewayComponent->end($result, $requestModel);
		} catch (\Exception $e) {
			$this->log('Exception when run gateway end method.', 'error', null, array(
				'exception' => (string)$e,
				'gatewayName' => $gatewayName,
				'requestModel' => $requestModel,
			));
			throw $e;
		}

		// Get transaction and check it exists
		$transactionModel = Transaction::model()->findByPk($processModel->transactionId);
		if ($transactionModel === null) {
			throw new NotFoundTransactionException();
		}

		// Get previous state for check changed state
		$previousState = $transactionModel->state;

		// Check already paid
		if ($transactionModel->state === State::COMPLETE && $transactionModel->result === Result::SUCCEED) {
			return $processModel;
		}

		// Update status
		$transactionModel->setScenario('update');
		$transactionModel->attributes = $processModel->getAttributes(array(
			'outsideTransactionId',
			'state',
			'result',
			'gatewayData',
		));

		// Save with transaction and trigger event
		$db = \Yii::app()->db;
		$transaction = $db->currentTransaction === null ? $db->beginTransaction() : null;
		try {
			// Save
			$transactionModel->save(false);

			// Trigger change state event
			$event = new \CModelEvent($transactionModel);
			GatewaysModule::findInstance()->onTransactionChangeState($event);

			if ($previousState !== State::COMPLETE && $transactionModel->state === State::COMPLETE && $transactionModel->result === Result::SUCCEED) {
				$event = new \CModelEvent($transactionModel);
				GatewaysModule::findInstance()->onTransactionComplete($event);
			}

			if ($transaction !== null) {
				$transaction->commit();
			}
		} catch (\Exception $e) {
			if ($transaction !== null) {
				$transaction->rollback();
			}
			$processModel->result = Result::ERROR;
		}

		return $processModel;
	}

	/**
	 * @param string $message
	 * @param string $level
	 * @param integer $transactionId
	 * @param array $stateData
	 * @throws \gateways\exceptions\GatewayException
	 */
	public function log($message, $level = 'log', $transactionId = null, $stateData = array()) {
		$logModel = new Log();
		$logModel->transactionId = $transactionId;
		$logModel->message = $message;
		$logModel->level = $level;
		$logModel->jsonStateData = \CJSON::encode($stateData);

		if (!$logModel->save()) {
			throw new GatewayException('Cannot save log to database.');
		}
	}

	/**
	 * Manual run complete transaction
	 * @param integer $transactionId
	 * @param string $description
	 * @return bool
	 * @throws \gateways\exceptions\GatewayException
	 * @throws \gateways\exceptions\NotFoundTransactionException
	 * @throws \Exception
	 */
	public function complete($transactionId, $description = '') {
		$transactionModel = Transaction::model()->findByPk((int) $transactionId);
		if ($transactionModel === null) {
			throw new NotFoundTransactionException();
		}

		// Get previous state for check changed state
		$previousState = $transactionModel->state;

		if (State::isCompleteState($transactionModel->state)) {
			throw new GatewayException('Transaction with id `' . $transactionModel->id . '` is already have final status complete.');
		}

		// Update status
		$transactionModel->setScenario('update');
		$transactionModel->state = State::COMPLETE;
		$transactionModel->result = Result::SUCCEED;
		$transactionModel->description .= $description . "\n";

		// Save with transaction and trigger event
		$db = \Yii::app()->db;
		$transaction = $db->currentTransaction === null ? $db->beginTransaction() : null;
		try {
			// Save
			$transactionModel->save(false);

			// Trigger change state event
			$event = new \CModelEvent($transactionModel);
			GatewaysModule::findInstance()->onTransactionChangeState($event);

			// Trigger complete event
			if ($previousState !== State::COMPLETE && $transactionModel->state === State::COMPLETE && $transactionModel->result === Result::SUCCEED) {
				$event = new \CModelEvent($transactionModel);
				GatewaysModule::findInstance()->onTransactionComplete($event);
			}

			if ($transaction !== null) {
				$transaction->commit();
			}
		} catch (\Exception $e) {
			if ($transaction !== null) {
				$transaction->rollback();
			}
			throw $e;
		}

		return true;
	}

	/**
	 * Manual reject transaction
	 * @param integer $transactionId
	 * @param string $description
	 * @return bool
	 * @throws \gateways\exceptions\GatewayException
	 * @throws \gateways\exceptions\NotFoundTransactionException
	 * @throws \Exception
	 */
	public function reject($transactionId, $description = '') {
		$transactionModel = Transaction::model()->findByPk($transactionId);
		if ($transactionModel === null) {
			throw new NotFoundTransactionException();
		}

		// Get previous state for check changed state
		$previousState = $transactionModel->state;

		if (State::isCompleteState($transactionModel->state)) {
			throw new GatewayException('Payment is already have final status complete.');
		}

		// set status and result
		$transactionModel->setScenario('processUpdate');
		$transactionModel->state = State::COMPLETE;
		$transactionModel->result = Result::REJECTED;
		$transactionModel->description .= $description . "\n";

		// Save with transaction and trigger event
		$db = \Yii::app()->db;
		$transaction = $db->currentTransaction === null ? $db->beginTransaction() : null;
		try {
			// Save
			$transactionModel->save(false);

			// Trigger change state event
			$event = new \CModelEvent($transactionModel);
			GatewaysModule::findInstance()->onTransactionChangeState($event);

			// Trigger complete event
			if ($previousState !== State::COMPLETE && $transactionModel->state === State::COMPLETE && $transactionModel->result === Result::SUCCEED) {
				$event = new \CModelEvent($transactionModel);
				GatewaysModule::findInstance()->onTransactionComplete($event);
			}

			if ($transaction !== null) {
				$transaction->commit();
			}
		} catch (\Exception $e) {
			if ($transaction !== null) {
				$transaction->rollback();
			}
			throw $e;
		}

		return true;
	}

	/**
	 * Start withdraw operation
	 * @param integer $accountId
	 * @param string $paymentSystemName
	 * @param double $amount
	 * @param array $data
	 * @param string $description Will be saved in payment order description field
	 * @return bool
	 */
	/*public function withdraw($accountId, $paymentSystemName, $amount, $data = null, $description = '') {
		$accountModel = AccountModel::model()->findByPk($accountId);
		if (!$accountModel) {
			throw new CException('Not find account model.');
		}

		// check rules
		if (Yii::app()->user->id != $accountModel->userId) {
			throw new CException('Account is not owned by user.');
		}

		if (!in_array($paymentSystemName, PaymentSystemName::getKeys())) {
			throw new CException('Payment system `'.$paymentSystemName.'` in not supported.');
		}

		if (!$this->accounting->haveFundsOnBalance($accountId, $amount)) {
			throw new CException('Not enough funds in the account.');
		}

		// create payment order
		$transactionModel = new PaymentModel();
		$transactionModel->setScenario('create');
		$transactionModel->accountId = $accountId;
		$transactionModel->paymentSystemName = $paymentSystemName;
		$transactionModel->amount = abs($amount);
		$transactionModel->type = PaymentOperationType::WITHDRAW;
		$transactionModel->state = PaymentState::CREATED;
		$transactionModel->data = $data;
		$transactionModel->description .= $description ? $description . "\n" : '';

		if ($transactionModel->save()) {
			// create operation for withdraw funds
			$operationModel = new OperationModel();
			$operationModel->setScenario('create');
			$operationModel->category = OperationCategory::WITHDRAW;
			$operationModel->type = OperationType::PAYMENT;
			$operationModel->delta = -1 * abs($transactionModel->amount);
			$operationModel->accountId = $transactionModel->accountId;
			$operationModel->objectId = $transactionModel->id;

			if ($operationModel->save()) {
				// start payment operation
				$paymentModule = PaymentModule::get($transactionModel->paymentSystemName);
				$paymentModule->paymentModel = $transactionModel;
				$processModel = $paymentModule->start();

				// update payment order status
				$transactionModel->setScenario('processUpdate');
				$transactionModel->attributes = $processModel->getAttributes(array(
					'id',
					'state',
					'result',
				));
				if ($transactionModel->save()) {
					return $processModel;
				}
			}
		}

		// set error
		$processModel = new PaymentProcessModel();
		$processModel->state = PaymentState::CREATED;
		$processModel->result = PaymentResult::ERROR;
		return $processModel;
	}*/

}
