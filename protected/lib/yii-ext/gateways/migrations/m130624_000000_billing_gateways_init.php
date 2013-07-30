<?php

use \gateways\models\enum\GatewayName;
use \gateways\models\enum\OperationType;
use \gateways\models\enum\Result;
use \gateways\models\enum\State;
use \gateways\models\enum\DataType;

class m130624_000000_billing_gateways_init extends \CDbMigration {

    public function up() {
        $this->createTable('billing_gateway_transactions', array(
            'id' => 'pk',
            'outsideTransactionId' => 'varchar(255) NULL',
            'amount' => 'money UNSIGNED NOT NULL',
	        'gatewayName' =>  GatewayName::toMysqlEnum() . ' NOT NULL',
	        'operationType' =>  OperationType::toMysqlEnum() . ' NOT NULL',
	        'result' =>  Result::toMysqlEnum() . ' NOT NULL',
	        'state' =>  State::toMysqlEnum(),
            'description' => 'varchar(255) NOT NULL',
            'creationTime' => 'int(10) NOT NULL',
            'updateTime' => 'int(10) NOT NULL',
        ), 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

	    $this->createTable('billing_gateway_data', array(
		    'id' => 'pk',
		    'transactionId' => 'integer NOT NULL',
		    'type' => DataType::toMysqlEnum() . ' NOT NULL',
		    'jsonData' => 'longtext NOT NULL',
	    ), 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

	    $this->createTable('billing_gateway_log', array(
		    'id' => 'pk',
		    'transactionId' => 'integer',
		    'message' => 'varchar(255)',
		    'level' => 'varchar(10)',
		    'jsonStateData' => 'longtext NOT NULL',
	    ), 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

	    $this->createIndex('transactionId', 'billing_gateway_data', 'transactionId');
	    $this->createIndex('transactionId', 'billing_gateway_log', 'transactionId');

	    $this->addForeignKey('fk_billing_gateway_data', 'billing_gateway_data', 'transactionId', 'billing_gateway_transactions', 'id', 'CASCADE');
	    $this->addForeignKey('fk_billing_gateway_log', 'billing_gateway_log', 'transactionId', 'billing_gateway_transactions', 'id', 'CASCADE');
    }

    public function down() {
        $this->dropTable('billing_gateway_data');
        $this->dropTable('billing_gateway_log');
	    $this->dropTable('billing_gateway_transactions');
    }

}