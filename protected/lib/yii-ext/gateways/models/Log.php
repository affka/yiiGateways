<?php

namespace gateways\models;

/**
 * @property integer $id
 * @property string $transactionId
 * @property string $message
 * @property string $level
 * @property string $jsonStateData
 */
class Log extends \CActiveRecord {

    /**
     * @param string
     * @return Log
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string
     */
    public function tableName() {
        return 'billing_gateway_log';
    }

    /**
     * @return array
     */
    public function rules() {
        return array(
            array('jsonStateData', 'required'),
	        array('transactionId', 'numerical', 'integerOnly' => true),
	        array('message', 'length', 'max' => 255),
	        array('level', 'length', 'max' => 10),
	        array('jsonStateData', 'length', 'max' => pow(2, 32)),
        );
    }

}