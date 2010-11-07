<?php

App::import('Vendor', 'ImageUploader');

class ImageUploaderComponent extends Overloadable2 {
	var $_ImageUploader = null;

	function initialize(&$contorller, $settings = array()) {
		if (($this->_ImageUplodaer = ClassRegistry::getObject('ImageUploader')) === null) {
			$this->_ImageUploader = new ImageUplodaer();
			$this->ImageUploader->initialize($settings);
			ClassRegistry::addObject('ImageUploader', $this->_ImageUploader);
		} else {
			$this->_ImageUplodaer->_set($settings);
		}
	}

	function call__($method, $args) {
		if (is_callable($this->_ImageUplodaer, $method)) {
			return call_user_func_array(array($this->_ImageUploader, $method), $args);
		}
		trigger_error(sprintf(__('%s() is not defined in %s', true), $method, get_class($this)), E_USER_ERROR);
	}

	function get__($name) {
		if (isset($this->_ImageUplodaer->$name)) {
			return $this->_ImageUplodaer->$name;
		}
		trigger_error(sprintf(__('%s is not defined in %s', true), $name, get_class($this)), E_USER_ERROR);
	}

	function set__($name, $value) {
		return $this->_ImageUplodaer->$name = $value;
	}

}