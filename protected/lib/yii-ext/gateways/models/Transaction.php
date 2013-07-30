<?php

namespace gateways\models;

use gateways\models\enum\DataType;
use \gateways\models\enum\GatewayName;
use \gateways\models\enum\OperationType;
use \gateways\models\enum\Result;
use \gateways\models\enum\State;

/**
 * @property integer $id
 * @property string $outsideTransactionId
 * @property string $amount
 * @property string $gatewayName
 * @property string $operationType
 * @property string $result
 * @property string $state
 * @property string $description
 * @property integer $creationTime
 * @property integer $updateTime
 * @property \gateways\models\TransactionData[] dataModels
 */
class Transaction extends \CActiveRecord {

	public $gatewayData = array();
	public $userData = array();
	public $invoiceIds = array();

    /**
     * @param string
     * @return \gateways\models\Transaction
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string
     */
    public function tableName() {
        return 'billing_gateway_transactions';
    }

    /**
     * @return array
     */
    public function rules() {
        return array(
            // scenario create
            array('amount, gatewayName, operationType, state', 'required', 'on' => 'create'),
            array('creationTime', 'default', 'value' => time(), 'on' => 'create'),
	        array('amount', 'numerical', 'on' => 'create'),
	        array('amount', 'length', 'max' => 19, 'on' => 'create'),
	        array('gatewayName', 'in', 'range' => GatewayName::getKeys(), 'on' => 'create'),
	        array('operationType', 'in', 'range' => OperationType::getKeys(), 'on' => 'create'),

            // scenario update
            array('state, result', 'required', 'on' => 'update'),

	        // scenario create+update
	        array('result', 'in', 'range' => Result::getKeys(), 'on' => 'create, update'),
	        array('state', 'in', 'range' => State::getKeys(), 'on' => 'create, update'),
	        array('outsideTransactionId, description', 'length', 'max' => 255, 'on' => 'create, update'),
	        array('creationTime, updateTime', 'numerical', 'integerOnly' => true, 'on' => 'create, update'),
	        array('gatewayData, userData', 'safe', 'on' => 'create, update'),
        );
    }

    /**
     * @return array
     */
    public function relations() {
		return array(
			'dataModels' => array(self::HAS_MANY, 'gateways\models\TransactionData', 'transactionId'),
		);
	}

    /**
     * @return array
     */
    public function attributeLabels() {
        return array(
            'id' => '№',
            'gatewayName' => 'Платежная система',
            'amount' => 'Сумма',
            'state' => 'Статус',
            'result' => 'Результат',
            'description' => 'Описание',
            'creationTime' => 'Создано',
            'updateTime' => 'Обновлено',
        );
    }

	protected function afterFind() {
		parent::afterFind();

		// Fill json data
		foreach ($this->dataModels as $dataModel) {
			$attributeName = DataType::getAttributeName($dataModel->type);
			$this->$attributeName = \CJSON::decode($dataModel->jsonData, false);
		}
	}

	protected function beforeSave() {
		$this->updateTime = time();
		return parent::beforeSave();
	}

	protected function afterSave() {
		// Update json data
		foreach (DataType::getData() as $type => $item) {
			$finedDataModel = null;
			$attributeName = $item['attributeName'];

			// Search dataModel
			foreach ($this->dataModels as $dataModel) {
				if ($dataModel->type === $type) {
					$finedDataModel = $dataModel;
					break;
				}
			}

			// Update data
			if ($this->$attributeName) {
				// Create, if not fined
				if ($finedDataModel === null) {
					$finedDataModel = new TransactionData();
					$finedDataModel->transactionId = $this->id;
					$finedDataModel->type = $type;
				}

				$finedDataModel->jsonData = \CJSON::encode($this->$attributeName);
				$finedDataModel->save();
			} elseif ($finedDataModel !== null) {
				$finedDataModel->delete();
			}
		}

		parent::afterSave();
	}
}