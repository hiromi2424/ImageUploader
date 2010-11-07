<?php
/**
 * ThumbMake
 *
 * @author ZARU
 * @author hiromi(rewrite)
 * @copyright 2008 ZARU
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @link http://blog.tofu-kun.org
*/

class Thumbmake extends Object {

	var $setMakeType = 'im'; //画像生成エンジンの選択 (GD => 'gd'/ImageMagick => 'im')
	var $imageMagic = '/usr/bin/convert'; //ImageMagickのパス

	/**
	 * initializeコールバック
	 *
	 * @return boolean success
	 * @access public
	 */
	function initialize($settings = array()) {
		$this->_set($settings);
		return true;
	}

	/**
	 * 初期化
	 *
	 * @return 
	 * @access public
	 */
	function init(){
		$this->srcPath = '';
		$this->dstPath = '';
		$this->dstName = '';
		$this->imageType = '';
		$this->dstImage = '';
		$this->left = 0;
		$this->top = 0;
		$this->x = 0;
		$this->y = 0;
		$this->srcWidth = 0;
		$this->srcHeight = 0;
		$this->dstWidth = 0;
		$this->dstHeight = 0;
		$this->cropWidth = 0;
		$this->cropHeight = 0;
		$this->quality = 100;
		$this->crop = false;
	}

	/**
	 * 指定された画像を表示する
	 *
	 * @return boolean
	 * @access public
	 */
	function disp() {
	
		//保存された画像を表示する
		if(file_exists($this->dstPath)){
			$source = file_get_contents($this->dstPath);
			$len = strlen($source);
			header('Content-Type: ' . $this->imageType . ';');
			header('Content-Length: ' . $len);
			header('Content-Disposition: inline; filename="' . $this->dstName .'"');
			echo $source;
		}else{
			return false;
		}
	}

	/**
	 * 生成された画像を保存する
	 *
	 * @return boolean
	 * @access public
	 */
	function save() {
		
		//保存先のディレクトリが存在しているかチェック
		$filePath = dirname($this->dstPath);
		if(!file_exists($filePath)){
			mkdir($filePath);
		}
		if($this->imageType == 'image/jpeg'){
		
			return imageJpeg($this->dstImage,$this->dstPath,$this->quality);
			
		}elseif($this->imageType == 'image/gif'){
		
			return imageGif($this->dstImage,$this->dstPath);
			
		}elseif($this->imageType == 'image/png'){
		
			return imagePng($this->dstImage,$this->dstPath);
		
		}
	
	}
	
	/**
	 * 元画像のパスと、保存先の画像のパスをセットする
	 *
	 * @param string $srcPath 元画像のファイルパス
	 * @param string $dstPath 保存先画像のファイルパス
	 *
	 * @return boolean
	 * @access public
	 */
	function setImage($srcPath, $dstPath) {
	
		$this->init();
	
		if(file_exists($srcPath)){

			//画像のパスをセット
			$this->srcPath = $srcPath;
			$this->dstPath = $dstPath;
			
			//ファイルタイプと縦横サイズを取得
			$imageInfo = getimagesize($this->srcPath);
			$this->srcWidth = $imageInfo['0'];
			$this->srcHeight = $imageInfo['1'];
			$this->imageType = $imageInfo['mime'];
			
			$dstFile = explode('/',$dstPath);
			$this->dstName = array_pop($dstFile);
			
			return true;
		}else{
			return false;
		}
		
	}
	
	/**
	 * JPEGの画質設定
	 *
	 * @param integer $quality JPEGの圧縮率
	 *
	 * @return 
	 * @access public
	 */
	function setQuality($quality) {
	
		$this->quality = $quality;
		
	}
	
	/**
	 * 横幅指定、縦なりゆきリサイズ
	 *
	 * @param string $width 横幅指定
	 * 
	 * @return boolean
	 * @access public
	 */
	function width($width) {
		
		$this->dstWidth = $width;
		
		//比率を出して縦のpxを計算する
		$rate = $width / $this->srcWidth;
		$this->dstHeight = ceil($this->srcHeight * $rate);
		
		//横縦が出たのでリサイズする
		return $this->resize();
	
	}
	
	/**
	 * 縦幅指定、横なりゆきリサイズ
	 *
	 * @param string $height 縦幅指定
	 *
	 * @return boolean
	 * @access public
	 */
	function height($height) {
	
		$this->dstHeight = $height;
		
		//比率を出して縦のpxを計算する
		$rate = $height / $this->srcHeight;
		$this->dstWidth = ceil($this->srcWidth * $rate);
		
		//横縦が出たのでリサイズする
		return $this->resize();
	
	}
	
	/**
	 * リサイズ＆トリミング
	 * 
	 * @param string $width 横幅指定
	 * @param string $height 縦幅指定
	 *
	 * @return boolean
	 * @access public
	 */
	function resizeCrop($width, $height) {

		//use GD
		if($this->setMakeType === 'gd'){
		
			$left	= 0;
			$top	= 0;
			
			$srcRate = $this->srcWidth / $this->srcHeight;
			$dstRate = $width / $height;
			
			if($srcRate < $dstRate){
				$origHeight = $this->srcHeight;
				$this->srcHeight = $this->srcWidth / $dstRate;
				$top = ($origHeight - $this->srcHeight) / 2;
			}elseif($srcRate > $dstRate){
				$origWidth = $this->srcWidth;
				$this->srcWidth = $this->srcHeight * $dstRate;
				$left = ($origWidth - $this->srcWidth) / 2;
			}
			$this->dstWidth = $width;
			$this->dstHeight = $height;
		
		//use ImageMagick
		}elseif($this->setMakeType === 'im'){
		
			//縦の方が短いので縦に合わせる
			if($width / $this->srcWidth <= $height / $this->srcHeight){

				$rate = $height / $this->srcHeight;
				$this->dstHeight = $height;
				$this->dstWidth = ceil($this->srcWidth * $rate);
				
				//切り抜き位置
				$left = ceil(($this->dstWidth - $width) / 2);
				$top = 0;
			
			//横に合わせる
			}else{
			
				$rate = $width / $this->srcWidth;
				$this->dstWidth = $width;
				$this->dstHeight = ceil($this->srcHeight * $rate);
				
				//切り抜き位置
				$top = ceil(($this->dstHeight - $height) / 2);
				$left = 0;
			
			}
			//最終トリミングサイズ
			$this->cropWidth = $width;
			$this->cropHeight = $height;
			
		}
		
		//トリミングの設定
		$this->crop($top, $left);
		
		//横縦が出たのでリサイズする
		return $this->resize();

	
	}
	
	/**
	 * リサイズ
	 * 
	 * @param string $width 横幅指定
	 * @param string $height 縦幅指定
	 *
	 * @return boolean
	 * @access public
	 */
	function resize() {
	
		/**
		 * 元画像の比率と、リサイズ指定の比率が合わない場合、どちらかに合わせてリサイズ後
		 * crop を使って指定のサイズにトリミングする
		 */
		if($this->setMakeType === 'gd'){
		
			if($this->imageType == 'image/jpeg'){
			
				//元画像を読み込み
				$srcImage = imagecreatefromjpeg($this->srcPath);
			
			}elseif($this->imageType == 'image/gif'){
			
				//元画像を読み込み
				$srcImage = imagecreatefromgif($this->srcPath);
			
			}elseif($this->imageType == 'image/png'){
			
				//元画像を読み込み
				$srcImage = imagecreatefrompng($this->srcPath);
			
			}
				
			//リサイズ用の画像を作成
			$this->dstImage = imagecreatetruecolor($this->dstWidth, $this->dstHeight);
			
			//白色に塗りつぶし
			$white = imagecolorallocate($this->dstImage, 255, 255, 255);
			imagefill($this->dstImage, 0, 0, $white);
			
			//元画像からリサイズしてコピー
			imagecopyresampled($this->dstImage, $srcImage, $this->x, $this->y, $this->left, $this->top,
							   $this->dstWidth, $this->dstHeight, $this->srcWidth, $this->srcHeight);

			//画像を保存
			return $this->save();
			
		}elseif($this->setMakeType === 'im'){
			
			//縮小
			$param  = ' -resize ';
			$param .= $this->dstWidth . 'x' . $this->dstHeight . ' ';
			
			//トリミング設定がされていた場合
			if($this->crop){
				$param .= ' -crop ';
				$param .= $this->cropWidth . 'x' . $this->cropHeight;
				$param .= '+' . $this->left . '+' . $this->top . ' ';
				
				//余白設定がされていた場合、ボーダーをつける
				if($this->x != 0 || $this->y != 0){
					$param .= ' -border ';
					$param .= $this->x . 'x' . $this->y;
					$param .= ' -bordercolor white ';
					
				}
			}
			
			$param .= $this->srcPath . ' ' . $this->dstPath;
				
			$dump = exec($this->imageMagick . $param);
			
			if($dump == ''){
				return true;
			}else{
				return false;
			}
		
		}
	
	}
	
	/**
	 * トリミングの設定
	 * 
	 * @param string $top 切り抜き y
	 * @param string $$left 切り抜き x
	 *
	 * @return 
	 * @access public
	 */
	function crop($top, $left) {
	
		$this->crop = true;
		$this->top = $top;
		$this->left = $left;
		
	}

	/**
	 * URLからパラメータを受け取る
	 *
	 * @return array
	 * @access public
	 */
	function getParam($params) {
		
		//初期化
		$this->init();
		
		//サムネイルのwidthとheight
		list($width,$height) = explode('x',$params['pass']['0']);
		
		//元画像のパスを解析
		$count = count($params['pass']);
		$srcPath = array();
		for($i=2;$i<$count;$i++){
			$srcPath[] = $params['pass'][$i];
			if($i === ($count - 1)){
				$srcName = $params['pass'][$i];
			}else{
				$srcDir[] = $params['pass'][$i];
			}
		}
		//元画像のパス
		$srcDir = implode(DS,$srcDir);
		
		//元画像のファイル名から拡張子部分を取り除く
		if(preg_match("/^(.+)\.([^\.]+)$/",$srcName,$match)){
			$fileName = $match['1'];
			$fileExt = $match['2'];
		}
		
		//元画像のパス
		$this->srcPath = WWW_ROOT . $srcDir . DS . $fileName . '.' . $fileExt;
		
		//元画像が存在したらサムネイルのサイズを変えす
		if(file_exists($this->srcPath)){
		
			//サムネイルのパス
			$this->dstPath = WWW_ROOT . $srcDir . DS
						   . $fileName . '_' . $params['pass']['0'] . '_' . $params['pass']['1'] . '.' . $fileExt;
			$this->dstName = $fileName . '_' . $params['pass']['1'] . '.' . $fileExt;
						
			//ファイルタイプと縦横サイズを取得
			$imageInfo = getimagesize($this->srcPath);
			$this->srcWidth = $imageInfo['0'];
			$this->srcHeight = $imageInfo['1'];
			$this->imageType = $imageInfo['mime'];
			
			return array($width,$height);
		
		}else{

			header("HTTP/1.0 404 Not Found");
			exit();
			
		}
		
	}

}