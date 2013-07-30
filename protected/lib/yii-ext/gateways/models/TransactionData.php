<?php

namespace gateways\models;

use gateways\models\enum\DataType;

/**
 * @property integer $id
 * @property integer $transactionId
 * @property integer $type
 * @property string $jsonData
 */
class TransactionData extends \CActiveRecord {

    /**
     * @param string
     * @return Data
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string
     */
    public function tableName() {
        return 'billing_gateway_data';
    }

    /**
     * @return array
     */
    public function rules() {
        return array(
            array('transactionId, jsonData', 'required'),
	        array('transactionId', 'numerical', 'integerOnly' => true),
	        array('type', 'in', 'range' => DataType::getKeys()),
	        array('jsonData', 'length', 'max' => pow(2, 32)),
        );
    }

}