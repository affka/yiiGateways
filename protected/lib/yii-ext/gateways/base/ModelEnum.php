<?php

namespace gateways\base;

abstract class ModelEnum {

	public static function getList() {
		return array();
	}

	public static function getLabel($typeIds) {
		return static::getLabels($typeIds);
	}

	public static function getLabels($names, $separator = ", ") {
		if (!is_array($names)) {
			$names = array($names);
		}

		$fined = array();
		$typeLabels = static::getList();

		foreach ($names as $name) {
			if (isset($typeLabels[$name])) {
				$fined[] = $typeLabels[$name];
			}
		}

		if (count($fined) === 0) {
			return null;
		}

		return implode($separator, $fined);
	}

	public static function getKeys() {
		return array_keys(static::getList());
	}

	public static function toMysqlEnum() {
		return "enum('" . implode("','", static::getKeys()) . "')";
	}

}