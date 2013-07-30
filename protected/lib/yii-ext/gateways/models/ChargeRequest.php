<?php

namespace gateways\models;

use \gateways\models\enum\PaymentType;
use \gateways\models\enum\GatewayName;
use \gateways\models\enum\PaymentMethod;

class ChargeRequest extends \CFormModel {

	public $amount;
	public $description;
	public $gatewayName;
	public $gatewayParams = array();
	public $userData = array();

    /**
     * @return array
     */
    public function rules() {
        return array(
            //array('amount, gatewayName', 'required'),
	        array('amount', 'numerical'),
	        array('amount', 'length', 'max' => 19),
	        array('gatewayName', 'in', 'range' => GatewayName::getKeys()),
	        array('description', 'length', 'max' => 255),
	        array('gatewayParams, userData', 'safe'),
        );
    }

}