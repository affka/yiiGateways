<?php

namespace gateways\models;

/**
 * @property string $url
 * @property integer $method
 * @property integer $params
 */
class Request extends \CFormModel {

	CONST REQUEST_METHOD_GET = 'get';
	CONST REQUEST_METHOD_POST = 'post';

    public $url;
    public $method = self::REQUEST_METHOD_GET;
    public $params = array();

}