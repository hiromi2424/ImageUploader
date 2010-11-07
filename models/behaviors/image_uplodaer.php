<?php

class ImageUploadableBehavior extends ModelBehavior {
	var $_ImageUplodader = null;

	function setup(&$model, $settings = array()) {
		if (($this->_ImageUplodaer = ClassRegistry::getObject('ImageUploader')) === null) {
			$this->_ImageUploader = new ImageUplodaer();
			$this->ImageUploader->initialize($settings);
			ClassRegistry::addObject('ImageUploader', $this->_ImageUploader);
		} else {
			$this->_ImageUplodaer->_set($settings);
		}
	}

	function validateImageUpload($check) {
		$field = key($check);
		$value = current($check);
	}
}