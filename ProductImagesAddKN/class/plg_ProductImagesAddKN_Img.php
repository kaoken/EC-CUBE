<?php
/*
 * This is a plug-in "ProductImagesAddKN" of EC CUBE.
 *
 * Copyright(c) 2013 kaoken CO.,LTD. All Rights Reserved.
 *
 * http://www.kaoken.cg0.xyz/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/util/plg_ProductImagesAddKN_Util.php';
require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/plg_ProductImagesAddKN_SC_FormParam_Ex.php';

/**
 * ProductImagesAddKNプラグイン画像クラス
 *
 * @package ProductImagesAddKN
 * @author kaoken
 * @since PHP 5.3　
 * @version 1.0
 */
class plg_ProductImagesAddKN_Img
{
	protected $m_aConfig = array();
	/**
	 * @var null|plg_ProductImagesAddKN_Util
	 */
	protected $m_knUtil = null;

	/**
	 * コンストラクタ
	 */
	public function __construct($aConfig=array())
	{
		$this->m_knUtil = plg_ProductImagesAddKN_Util::getMy();
		if ( count($aConfig) == 0 )
		{
			$db = $this->m_knUtil->getDB('Config');
			$this->m_aConfig = $db->get();
		}
		else
			$this->m_aConfig = $aConfig;

	}

	/**
	 * initParam()
	 *
	 * @param mixed $objFormParam
	 */
	protected static function initParam(&$objFormParam)
	{
		$objFormParam->addParam('商品画像ID', 'img_id', INT_LEN, 'n',  array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
		$objFormParam->addParam('商品ID', 'product_id', INT_LEN, 'n',  array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
		$objFormParam->addParam('商品ID', 'p_id', INT_LEN, 'n',  array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
		$objFormParam->addParam('商品IDからの順番番号', 'priority', INT_LEN, 'n',  array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
		$objFormParam->addParam('商品IDからの順番番号', 'p', INT_LEN, 'n',  array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
		$objFormParam->addParam('商品イメージキー', 'image_key', STEXT_LEN, '',  array('GRAPH_CHECK', 'MAX_LENGTH_CHECK'));
		$objFormParam->addParam('画像ファイル名', 'image', STEXT_LEN, 'a',  array('MAX_LENGTH_CHECK'));
		$objFormParam->addParamNumLimit('画像の幅', 'width', 1,9999,  array());
		$objFormParam->addParamNumLimit('画像の高さ', 'height', 1,9999,  array());
		$objFormParam->addParamNumLimit('画像の幅', 'w', 1,9999,  array());
		$objFormParam->addParamNumLimit('画像の高さ', 'h', 1,9999,  array());
	}

	/**
	 * 商品画像のパスを取得する
	 *
	 * @param array $arrForm
	 * @return string ファイル名
	 */
	public function getProductImage($arrForm)
	{
		$objQuery = SC_Query_Ex::getSingletonInstance();
		$table = 'dtb_products';
		$col = $arrForm['image_key'];
		$product_id = $arrForm['product_id'];
		//指定されたカラムが存在する場合にのみ商品テーブルからファイル名を取得
		if (SC_Helper_DB_Ex::sfColumnExists($table, $col, '', '', false)) {
			$product_image = $objQuery->get($col, $table, 'product_id = ?', array($product_id));
		} else {
			GC_Utils_Ex::gfPrintLog('invalid access :resize_image.php image_key=' . $col);
			$product_image = '';
		}
		// ファイル名が正しく、ファイルが存在する場合だけ、ファイルパスを設定
		return SC_Utils_Ex::getSaveImagePath($product_image);
	}

	/**
	 * ファイル名の形式をチェック.
	 *
	 * @deprecated 2.13.0 商品IDを渡す事を推奨
	 * @param string $image
	 * @return boolean 正常な形式:true 不正な形式:false
	 */
	public function checkFileName($image)
	{
		$file = trim($image);
		if (!preg_match("/^[[:alnum:]_\.-]+$/i", $file)) {
			return false;
		}
		return true;
	}

	/**
	 * 指定した幅高さに比率固定で縮小する
	 *
	 * @param int $w 幅
	 * @param int $h 高さ
	 * @param int $rSrcW 元となる幅
	 * @param int $rSrcH 元となる高さ
	 * @param integer $flg
	 * @return array
	 */
	public function ratioFixation($w, $h, $rSrcW, $rSrcH, $flg = 0)
	{
		$aRet = array($rSrcW, $rSrcH);
		// 指定サイズの比率に合わせる
		if ( ($w <= $rSrcW || $h <= $rSrcH) && $flg==0){
			// 縮小
			if ($w < $rSrcW){
				$rate = $w/$rSrcW;
				$aRet[1] = (int)($rate * $rSrcH);
				$aRet[0] = (int)($rate * $rSrcW);
			}
			if ($h < $aRet[1]){
				$rate = $h/$aRet[1];
				$aRet[0] = (int)($rate * $aRet[0]);
				$aRet[1] = (int)($rate * $aRet[1]);
			}
		}else if ($flg == 1){
			// 拡大
			if ($w > $rSrcW){
				$rate = $w/$aRet[0];
				$aRet[1] = (int)($rate * $rSrcH);
				$aRet[0] = (int)($rate * $rSrcW);
			}
			if ($h > $aRet[1]){
				$rate = $h/$aRet[1];
				if ((int)($rate * $aRet[0]) < $w){
					$aRet[0] = (int)($rate * $aRet[0]);
					$aRet[1] = (int)($rate * $aRet[1]);
				}
			}
		}
		return $aRet;
	}

	/**
	 * 画像がないときに描画させるためのもの
	 *
	 * @param integer $type
	 * @return bool
	 */
	public function drawErrImage($type=0)
	{
		$path = PLUGIN_HTML_REALDIR."/ProductImagesAddKN/img";
		switch( $type )
		{
			case 0:
				header("HTTP/1.0 404 Not Found");
				header('Content-type: image/gif');
				readfile($path."/no_img.gif");
				break;
			case 1:
				header("HTTP/1.0 501 Not Implemented");
				header('Content-type: image/gif');
				readfile($path."/err_size.gif");
				break;
			case 2:
				header("HTTP/1.0 500 Internal Server Error");
				header('Content-type: image/gif');
				readfile($path."/err_db.gif");
				break;
			case 3:
				header("HTTP/1.0 500 Internal Server Error");
				header('Content-type: image/gif');
				readfile($path."/err_para.gif");
				break;
		}
		return true;
	}

	/**
	 * 画像がないときにURLパス返す。
	 *
	 * @param integer $type
	 * @return string
	 */
	public function drawErrImageURL($type=0)
	{
		$path = $this->getUrl().'plugin/'.$this->m_knUtil->getPluginName().'/img/';
		switch( $type )
		{
			case 0:
				return $path."no_img.gif";
			case 1:
				return $path."err_size.gif";
			case 2:
				return $path."err_db.gif";
			case 3:
				return $path."err_para.gif";
		}
		return $path."no_img.gif";
	}


	/**
	 * 商品画像をアップロードする
	 *
	 * @param object $file
	 * @param string $uploaded_file 幅
	 * @return int|bool 何らかのエラーの時はfalseを返す。
	 */
	public function uploadImg(&$file, $uploaded_file)
	{
		$dbImg = $this->m_knUtil->getDB('ProductImg');

		$objImage = new Imagick();

		$ret = $objImage->readImageBlob(@file_get_contents($uploaded_file));
		@unlink($uploaded_file);

		if ( $ret === false )
		{
			$objImage->destroy();
			$file->errorNo = 300;
			return false;
		}
		$aInsert = $dbImg->getRequiredColumn();
		$aInsert['width'] = $objImage->getImageWidth();
		$aInsert['height'] = $objImage->getImageHeight();
		$aInsert['mime'] = $file->type;
		$aInsert['product_id'] = $file->product_id;
		$aInsert['imgdat'] = $objImage->getimageblob();
		$objImage->destroy();


		// 挿入処理
		$dbImg->begin();

		if ( DB_TYPE == 'pgsql')
			$dbImg->psqlLockMyTable('SHARE UPDATE EXCLUSIVE MODE');
		else
			$dbImg->mysqlLockMyTable();

		$num = $dbImg->getNumFromProductID($file->product_id);
		if ( $num >= $this->m_aConfig['product_img_upload_max'] )
		{
			$file->errorNo = 301;
			$dbImg->rollback();
			return false;
		}

		$id = $dbImg->insert($aInsert,false);

		if ( $id === false )
		{
			$file->error = 'DBへの挿入が失敗しました。';
			$dbImg->rollback();
		}
		else
			$dbImg->commit();

		$file->id = $id;
		$file->date = date("Y年m月d日 H：i", time());
		$file->width = $aInsert['width'];
		$file->height = $aInsert['height'];
		$file->priority = $aInsert['priority'];
		return $id;
	}

	/**
	 * 指定した幅高さに比率固定で縮小する
	 *
	 * @param array     $aPara
	 * @param boolean   $isFile 画像ファイルの処理か？trueの場合画像ファイル、flaseの場合DBを使った商品画像処理
	 * @param boolean   $isURL  trueの場合URLを取得する。falseの場合画像を出力する
	 * @return boolean
	 */
	public function cashImage($aPara, $isFile=true, $isURL=false)
	{
		$aPara['img_id']=intval($aPara['img_id']);
		$aPara['product_id']=intval($aPara['product_id']);
		$aPara['width']=intval($aPara['width']);
		$aPara['height']=intval($aPara['height']);
		$aPara['priority']=intval($aPara['priority']);

		$isTmpDir = false;
		// DB
		$dbAllowableSize = $this->m_knUtil->getDB('AllowableSize');
		$dbCashImg = $this->m_knUtil->getDB('CashImg');
		$dbImg = $this->m_knUtil->getDB('ProductImg');

		$aCommon = array();
		if ( $isFile )
		{
			// ソースファイルがイメージがない場合
			if ( $aPara['src_file'] == NO_IMAGE_REALFILE )
				return $isURL?$this->drawErrImageURL():$this->drawErrImage();

			// 簡易画像情報取得
			$aInfo = @getimagesize($aPara['src_file']);
			//
			$aCommon['width'] = intval($aInfo[0]);
			$aCommon['height'] = intval($aInfo[1]);
			$aCommon['mime'] = $aInfo['mime'];
			$aPara['mime'] = $aInfo['mime'];
			unset($aInfo);

			// サイズが同じ場合、そのままファイルを出力
			if ( ($aPara['width'] == $aCommon['width'] && $aPara['height'] == $aCommon['height']) ||
			     ( $aPara['width'] == 0 && $aPara['height'] == 0 ))
			{
				if ( $isURL )
				{
					return $this->getUrl().'upload/save_image/'.basename($aPara['src_file']);
				}
				else
				{
					// 304 NotModifiedチェック			
					if ( $this->m_knUtil->notModifiedHeaders(filemtime($aPara['src_file']), $aPara['src_file']) )
						return false;
					header('Content-type: '.$aCommon['mime']);
					readfile($aPara['src_file']);
					return true;
				}
			}
			$aPara['ext'] = $this->m_knUtil->getExtFromMIME($aCommon['mime']);
			// キャッシュがあるか？
			if ( ($ret = $this->cashImagePrint($dbCashImg->get($aPara),$dbCashImg,$isURL)) !== false )
				return $ret;
		}
		else
		{
			// キャッシュ画像が存在するか？
			if ( $aPara['img_id'] != 0 )
			{
				if ( ($ret = $this->cashImagePrint($dbCashImg->getFromPrductImgId_W_H_E($aPara),$dbCashImg,$isURL)) !== false )return $ret;
			}
			else
			{
				if ( ($ret = $this->cashImagePrint($dbCashImg->getFromProductIDAndPriority($aPara),$dbCashImg,$isURL)) !== false )return $ret;
			}
			// 商品画像を取得する
			if ( $aPara['img_id'] != 0 )
				$aCommon = $dbImg->get($aPara['img_id']);
			else
			{
				$aCommon = $dbImg->getFromProductIDAndPriority($aPara['product_id'], $aPara['priority'],false);

				$aPara['img_id'] = $aCommon['img_id'];
			}

			if ( count($aCommon) == 0 )
				return $isURL?$this->drawErrImageURL(0):$this->drawErrImage(0);

			// サイズが同じ場合、そのままファイルを出力
			if ( ($aPara['width'] == $aCommon['width'] && $aPara['height'] == $aCommon['height']) ||
			     ( $aPara['width'] == 0 && $aPara['height'] == 0 ))
			{
				if ( $isURL )
				{
					return $this->getUrl().'resize_image.php?img_id='.$aCommon['img_id'];
				}
				else
				{
					// 304 NotModifiedチェック			
					if ( $this->m_knUtil->notModifiedHeaders(strtotime($aCommon['create_tm']), $aCommon['img_id']) )
						return false;
					header("Content-type: ".$aCommon['mime']);
					echo $aCommon['imgdat'];
					return true;
				}
			}
			// 商品IDが一時的なものかチェック
			$isTmpDir = $aCommon['product_id'] <= 0;
		}
		//---------------
		// ここから共通
		//---------------

		// 登録された 幅、高さ のサイズか？
		if ( !$dbAllowableSize->check($aPara['width'], $aPara['height']) )
			return $isURL?$this->drawErrImageURL(1):$this->drawErrImage(1);

		if ( $isFile )
		{
			$objImage = new Imagick($aPara['src_file']);
		}
		else
		{
			$objImage = new Imagick();
			if ( !$objImage->readImageBlob($aCommon['imgdat']) )
			{
				$objImage->destroy();
				return $isURL?$this->drawErrImageURL():$this->drawErrImage();
			}
			// サイズが大きい場合があるので、先に解放しておく
			unset($aCommon['imgdat']);
		}

		// 圧縮率
		$objImage->setCompressionQuality($this->m_aConfig['compression_rate']);


		if ($aPara['width'] < $aCommon['width'] || $aPara['height'] < $aCommon['height'])
		{
			$aWH = $this->ratioFixation($aPara['width'],$aPara['height'],$aCommon['width'],$aCommon['height']);
			$objImage->thumbnailImage($aWH[0],$aWH[1]);
			unset($aWH);
		}

		$aInsert = $dbCashImg->getRequiredColumn($aCommon);
		$aInsert['img_file']= $aPara['src_file']!=''?basename($aPara['src_file']):'';
		$aInsert['img_id']  = $aPara['img_id'];
		$aInsert['width']   = $aPara['width'];
		$aInsert['height'] = $aPara['height'];
		$aInsert['effect'] = $aPara['effect'];
		$aInsert['ext']	= $this->m_knUtil->getExtFromMIME($aCommon['mime']);

		// 縮小したやつを挿入
		if ( $dbCashImg->insert($aInsert) )
		{
			// キャッシュ画像ファイルの作成
			if ( $isTmpDir )
				$cashPath = $this->m_knUtil->getTmpCashImagesPath($aInsert['file_name']);
			else
				$cashPath = $this->m_knUtil->getCashImagesPath($aInsert['file_name']);
			// ファイルの書き込み
			$objImage->writeImage($cashPath);

			if ( $isURL )
			{
				$objImage->destroy();
				return $this->getImageCashUrl().$aInsert['file_name'];
			}
			else
			{
				header("Content-type: ".$aCommon['mime']);
				echo $objImage;
			}
		}
		else
		{	// DB 挿入失敗
			return $isURL?$this->drawErrImageURL(2):$this->drawErrImage(2);
		}
		$objImage->destroy();
		return !$isURL;
	}

	/**
	 * すでに登録されているキャッシュ画像の場合は、そのまま表示
	 * さらに、アクセスしていたら304NotModifiedを出力
	 *
	 *
	 * @param array   $aCashImg  配列
	 * @param object  $dbCashImg キャッシュ画像DBインスタンス
	 * @return bool
	 */
	protected function cashImagePrint(&$aCashImg, &$dbCashImg, $isURL=false)
	{
		if ( count($aCashImg) != 0 )
		{
			if ( $aCashImg['product_id'] > 0 || $aCashImg['img_file'] !== '' )
				$imgFilePath = $this->m_knUtil->getCashImagesPath($aCashImg['file_name']);
			else
				$imgFilePath = $this->m_knUtil->getTmpCashImagesPath($aCashImg['file_name']);

			if ( !file_exists($imgFilePath) )
			{
				$dbCashImg->deleteFromFileName($aCashImg['file_name'],true);
				return false;
			}
			@touch($imgFilePath);

			if ($isURL)
			{
				return $this->getImageCashUrl().$aCashImg['file_name'];
			}
			else
			{
				// 304 NotModifiedチェック			
				if ( $this->m_knUtil->notModifiedHeaders(filemtime($imgFilePath), $imgFilePath) )
					return true;

				$aInfo = @getimagesize($imgFilePath);

				header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($imgFilePath)).' GMT');
				$offset = 60 * 60 * 24 * 31;
				header('Expires: '.gmdate('D, d M Y H:i:s', time() + $offset).' GMT');
				header("Content-type: ".$aInfo['mime']);
				header('Content-Length: ' . filesize($imgFilePath));
				readfile($imgFilePath);
				die();
				return true;
			}
		}
		return false;
	}
	/**
	 * http[s]://ドメイン/plugin/ProductImagesAddKN/cash_images/ のURLを返す
	 *
	 * @return string
	 */
	public function getImageCashUrl()
	{
		return $this->getUrl().'plugin/'.$this->m_knUtil->getPluginName().'/cash_images/';
	}
	/**
	 * http[s]://ドメイン/ のURLを返す
	 *
	 * @return string
	 */
	protected function getUrl()
	{
		$https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0;
		return ($https ? HTTPS_URL : HTTP_URL);
	}

	/**
	 * 商品IDとイメージキーから商品画像名を取得する
	 *
	 * @param int    $product_id 商品ID
	 * @param string $img_key    イメージキー名
	 * @return mixi 取得に失敗した場合はfalseを返し、成功した場合はファイル名を返す
	 */
	public function getImgNameFromImgKeyProductID($product_id,$img_key)
	{
		// 商品IDからデフォルト商品画像を取得
		$aPItem = array('list'=>'main_list_image','main'=>'main_image','large'=>'main_large_image');
		$type = $aPItem[strtolower($img_key)];
		$errFlg = true;
		if ( $type != "" )
		{
			// dtb_productsテーブルから商品画像名取得
			$objQ = SC_Query_Ex::getSingletonInstance();
			$aCol = $objQ->select($type.' as name','dtb_products','product_id='.$product_id);
			if ( count($aCol) > 0 )
			{
				$file = $aCol[0]['name'];
				if (!$this->checkFileName($file))
					GC_Utils_Ex::gfPrintLog('invalid access :resize_image.php image=' .$file);
				return SC_Utils_Ex::getSaveImagePath($file);
			}
		}
		return false;
	}

	/**
	 * GETリクエストからパラメータを解析し、画像を読み込む。
	 *
	 * @param LC_Page_EX $objPage LC_Page_ResizeImageのpageオブジェクト
	 * @return void
	 */
	public function readImage(&$objPage)
	{
		$objFormParam = new plg_ProductImagesAddKN_SC_FormParam_Ex();
		self::initParam($objFormParam);
		$objFormParam->setParam($_GET);


		$arrErr = $objFormParam->checkError();
		if (SC_Utils_Ex::isBlank($arrErr))
		{
			$aPara  = $objFormParam->getHashArray();

			$aPara['width'] = intval($aPara['width'])==0?intval($aPara['w']):$aPara['width'];
			$aPara['height'] =  intval($aPara['height'])==0?intval($aPara['h']):$aPara['height'];
			$aPara['img_id'] =  intval($aPara['img_id']);
			$aPara['product_id'] =  intval($aPara['product_id'])==0?intval($aPara['p_id']):$aPara['product_id'];
			$aPara['priority'] =  intval($aPara['priority'])==0?intval($aPara['p']):$aPara['priority'];
			$isFile = true;

			if ( $aPara['image_key'] != "" && $aPara['product_id'] > 0)
			{
				if ( ($aPara['src_file'] = $this->getImgNameFromImgKeyProductID($aPara['product_id'],$aPara['image_key'])) === false )
				{
					$this->drawErrImage(3);
					exit();
				}
			}
			else if (strlen($aPara['image']) >= 1 && $aPara['image'] !== NO_IMAGE_REALFILE )
			{
				if (!$this->checkFileName($aPara['image']))
					GC_Utils_Ex::gfPrintLog('invalid access :resize_image.php image=' . $aPara['image']);
				$aPara['src_file'] = SC_Utils_Ex::getSaveImagePath($aPara['image']);
			}
			else if ( $aPara['img_id'] > 0 || ($aPara['product_id']>0 && $aPara['priority']>0) )
			{
				$isFile = false;
			}
			else
			{
				// 商品画像を取得する
				$aPara['src_file'] = $this->getProductImage($aPara);
			}
			// リサイズ画像の出力&ファイルの作成
			$this->cashImage($aPara, $isFile, false);
			exit();
		}
		else
		{
			$this->drawErrImage(3);
			exit();
		}
	}

}
