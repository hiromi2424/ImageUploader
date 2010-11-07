<?php

assert(App::import('Vendor', 'ImageUploader.Thumbmake'));

class ImageUploader extends Object {
	var $Thumbmake = null;
	var $config = array(
		'max_size' => 2097152, // (2MB)制限する最大のファイルサイズ
		'max_length' => 1600, // 制限する最大の写真サイズ
		'max_rate' => 1.8, // 制限する縦横比の最大
		'original' => 'through',
		'thumbnails' => array(),
		// 許可する画像タイプ
		'allow_types' => array(
			'image/jpeg',
			'image/gif',
			'image/png',
		),
		'error_messages' => array(
			'allow_types' => '許可されていない種類のファイルです。',
			'image_not_found' => 'ファイルが見つかりません。',
			'failed_upload' => 'アップロードに失敗しました。',
			'moving_file' => 'ファイル移動に失敗しました。',
			'max_size' => 'ファイルの容量が大きすぎます。',
			'max_length' => '画像が大きすぎます。',
			'max_rate' => '画像の縦横比が大きすぎます。',
			'save' => 'リサイズ後のファイル格納に失敗しました。',
			'resize' => '画像のリサイズに失敗しました。',
		),
	);
	var $error = false;
	var $srcPath;
	var $dstPath = null;

	function initialize($settings = array()) {
		$this->Thumbmake = new Thumbmake();
		$this->Thumbmake->initialize($settings);
		$this->_set($settings);
		return true;
	}

	function getProperty($key) {
		if (isset($this->Thumbmake->$key)) {
			return $this->Thumbmake->$key;
		}
		return null;
	}

	function getErrorMessage($key = null) {
		if ($key === null) {
			$key = $this->error;
		}
		if ($key !== false && isset($this->config['error_messages'][$key])) {
			return $this->config['error_messages'][$key];
		}
		return $key;
	}

	function configure($config) {
		$this->config = Set::merge($this->config, $config);
	}

	function raiseError($error) {
		$this->error = $error;
	}

	function setSource($path) {
		$this->srcPath = $path;
		return true;
	}

	function _setImage($src, $dist) {
		$this->error = false;
		$result = $this->Thumbmake->setImage($src, $dist);
		if (!$result) {
			$this->raiseError('image_not_found');
		}
		return $result;
	}

	function getExt($comma = false) {
		$t =& $this->Thumbmake;
		if (empty($t->srcPath)) {
			$this->_setImage($this->srcPath, $this->dstPath);
		}

		if ($comma) {
			$comma = '.';
		}
		switch ($t->imageType) {
			case 'image/jpeg':
				return $comma . 'jpg';
			case 'image/gif':
				return $comma . 'gif';
			case 'image/png':
				return $comma . 'png';
			default:
				return null;
		}
		return false;
	}

	function upload($upload_info, $toSave) {
		$defaults = array(
			'name' => '',
			'type' => '',
			'tmp_name' => '',
			'error' => 4,
			'size' => 0,
		);
		extract(array_merge($defaults, $upload_info));
		if($error || $size === 0){
			$this->raiseError('failed_upload');
			return false;
		}
		if($size > $this->config['max_size']){
			$this->raiseError('max_size');
			return false;
		}
		if (!move_uploaded_file($tmp_name, $toSave)) {
			$this->raiseError('moving_file');
			return false;
		}
		return true;
	}

	function moveFromFile($src, $dist) {
		if (!file_exists($src)) {
			$this->raiseError('moving_file');
			return false;
		}
		if(filesize($src) > $this->config['max_size']){
			$this->raiseError('max_size');
			return false;
		}
		if (!rename($src, $dist)) {
			$this->raiseError('moving_file');
			return false;
		}
		return true;
	}

	function validates() {
		$t =& $this->Thumbmake;
		if (empty($t->srcPath)) {
			$this->_setImage($this->srcPath, $this->dstPath);
		}
		extract($this->config);

		if (!empty($allow_types)) {
			if (!in_array($t->imageType, $allow_types)) {
				$this->raiseError('allow_types');
				return false;
			}
		}

		$rateWidth = $t->srcWidth / $t->srcHeight;
		$rateHeight = $t->srcHeight / $t->srcWidth;

		if (!empty($max_length)) {
			if ($t->srcWidth > $max_length || $t->srcHeight > $max_length) {
				$this->raiseError('max_length');
				return false;
			}
		}

		if (!empty($max_rate)) {
			if ($rateWidth > $max_rate || $rateHeight > $max_rate) {
				$this->raiseError('max_rate');
				return false;
			}
		}

		return true;
	}

	function save($validates = true) {
		if ($validates && !$this->validates()) {
			return false;
		}
		extract($this->config);
		if (!empty($thumbnails)) {
			foreach ($thumbnails as $thumbnail) {
				$this->_setImage($this->srcPath, $thumbnail['path']);
				if (!$this->_save($thumbnail['length'], $thumbnail['way'])) {
					$this->raiseError('save');
					return false;
				}
			}
		}
		return true;
	}

	function _save($length, $way) {
		switch($way) {
			case 'width':
				return $this->resizeWidth($length);
			case 'height':
				return $this->resizeHeight($length);
			case 'crop':
				$width = $height = $length;
				if (is_array($length)) {
					extract($length);
				}
				return $this->resizeCrop($width, $height);
			default:
				return copy($this->Thumbmake->srcPath, $this->Thumbmake->dstPath);
		}
		return true;
	}

	function resizeWidth($width) {
		$result = $this->Thumbmake->width($width);
		if (!$result) {
			$this->raiseError('resize');
			return false;
		}
		return true;
	}

	function resizeHeight($height) {
		$result = $this->Thumbmake->height($height);
		if (!$result) {
			$this->raiseError('resize');
			return false;
		}
		return true;
	}

	function resizeCrop($width, $height) {
		$result = $this->Thumbmake->resizeCrop($width, $height);
		if (!$result) {
			$this->raiseError('resize');
			return false;
		}
		return true;
	}
}