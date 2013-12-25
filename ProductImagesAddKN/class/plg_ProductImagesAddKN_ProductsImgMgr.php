<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2013 kaoken CO.,LTD. All Rights Reserved.
 *
 * http://www.kaoken.net/
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
require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/plg_ProductImagesAddKN_Img.php';

/**
* ProductImagesAddKNプラグイン画像クラス
*
* @package ProductImagesAddKN
* @author kaoken
* @since PHP 5.3　
* @version 0.1
*/
class plg_ProductImagesAddKN_ProductsImgMgr 
{
	protected $m_UH = null;
	protected $m_aConfig = array();
	protected $m_img = null;
	protected $m_knUtil = null;
	protected $m_isInit = false;
	protected $m_productID = 0;
	/**
	 * コンストラクタ
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->m_knUtil = plg_ProductImagesAddKN_Util::GetMy();
		$dbConfig = $this->m_knUtil->GetDB('Config');
		$this->m_aConfig = $dbConfig->Get();
		$this->m_img = new plg_ProductImagesAddKN_Img($this->m_aConfig);
	}
	
	/**
	 * アクセス拒否をJSON形式で表示する。
	 *
	 * @return void
	 */
	protected function AccessRefusalError()
	{ $this->ErroInJSON('アクセスできません'); }
	
	/**
	 * JSON形式でエラー表示する。
	 *
	 * @param string   $err  内容
	 * @return void
	 */
	protected function ErroInJSON($err)
	{ $this->PrintJSON(array('error', $err)); }
	
	/**
	 * JSON形式で表示する。
	 *
	 * @param array   $ary  内容
	 * @return void
	 */
	protected function PrintJSON($ary)
	{
		header("Content-Type: application/json; charset=utf-8");
		echo json_encode($ary);
		exit;
	}
	
	/**
	 * 初期化
	 *
	 * @return void
	 */
	public function init()
	{
//		GC_Utils_Ex::gfPrintLog("POST\n".print_r($_POST,true));

		// プラグインが有効か？
		$plugin = SC_Plugin_Util_Ex::getPluginByPluginCode('ProductImagesAddKN');
		if($plugin['enable'] != '1') {
			$this->AccessRefusalError();
		}
				
		//IP制限チェック
		$allow_hosts = unserialize(ADMIN_ALLOW_HOSTS);
		if(is_array($allow_hosts) && count($allow_hosts) > 0) {
			if(array_search($_SERVER['REMOTE_ADDR'],$allow_hosts) === FALSE) {
				$this->AccessRefusalError();
			}
		}

		//SSL制限チェック
		if(ADMIN_FORCE_SSL == TRUE) {
			if(SC_Utils_Ex::sfIsHTTPS() === false) {
				$this->AccessRefusalError();
			}
		}
		
		// 管理者としてログインしているか？
		$objSess = new SC_Session_Ex();
		SC_Utils_Ex::sfIsSuccess($objSess);
		
		if( $_POST[TRANSACTION_ID_NAME] !== SC_Helper_Session_Ex::getToken() )
		{
			//$objSess->logout();
			$this->AccessRefusalError();
		}
		if( intval($_POST['kn_temp_product_id']) !== 0 )
			$this->m_productID = intval($_POST['kn_temp_product_id']);
		else if( intval($_POST['product_id']) !== 0 )
			$this->m_productID = intval($_POST['product_id']);
		
		$this->m_isInit = true;
	}
	/**
	 * 処理開始
	 *
	 * @return void
	 */
	public function process()
	{
		if( !$this->m_isInit ) {
			SC_Utils_Ex::sfDispError(AUTH_ERROR);
		}
		switch( $_GET['mode'] )
		{
			case 'debug':
				$this->DebugTest();
			break;
		}

		switch( $_POST['mode'] )
		{
			case 'list':
				$this->GetImageList();
				break;
			case 'move':
				$this->MoveProductImages();
				break;
			case 'del':
				$this->DeleteProductImages();
				break;
			case 'upload':
				$this->Upload();
				break;
			default:
				;
		}
	}
	private function DebugTest()
	{
/*		
		$dbImg = $this->m_knUtil->GetDB('ProductImg');	
		//
		$dbImg->Begin();		
		$aInfo = $dbImg->GetFromPrdouctID(-484546438, true);
		$dbImg->Commit();
		echo "json\n";
		if( count($aInfo) > 0 )
		{
			for($i=0;$i<count($aInfo);++$i)
			{
				$this->RecreateColumn($aInfo[$i]);
			}
			$aJson = $aInfo;
		}
		echo json_encode($aJson)."\n";
		exit;*/
	}	
	
	
	
	
	
	//#########################################################################
	//#########################################################################
	//## アップロード関係
	//#########################################################################
	//#########################################################################
	protected $m_aErr = array(
		1 => 'アップロードされたファイル:"#1"は、php.ini内のupload_max_filesize[#2]の値を超えています。',
		2 => 'アップロードされたファイル:"#1"は、HTMLフォームで指定されたMAX_FILE_SIZE[#2]の値を超えています。',
		3 => 'アップロードされたファイル:"#1"は一部のみしかアップロードされていません。',
		4 => 'ファイル:"#1"はアップロードされませんでした。',
		6 => '一時フォルダが見つかりません。',
		7 => 'ファイル:"#1"は、ディスクへの書き込みに失敗しました。',
		8 => 'PHP拡張モジュールは、ファイル:"#1"のアップロードを停止しました。',
		100 => 'アップロードできるファイルは（jpeg, png, gif）のみです。',
		101 => 'ファイル:"#1"のサイズが大きいです。',
		102 => 'ファイル:"#1"は、最大幅[#2 px]を超えています。',
		103 => 'ファイル:"#1"は、最大高さ[#2 px]を超えています。',
		200 => '商品IDが存在しません',
		300 => 'Imagickでerrorが発生しました。',
		301 => 'ファイルの最大数#2を超えました。'
	);
	private function Upload()
	{
		$upload = isset($_FILES['kn_prduct_img_files']) ?
			$_FILES['kn_prduct_img_files'] : null;
			
		$file_name = $this->GetDerverVer('HTTP_CONTENT_DISPOSITION') ?
			rawurldecode(preg_replace(
				'/(^[^"]+")|("$)/',
				'',
				$this->GetDerverVer('HTTP_CONTENT_DISPOSITION')
			)) : null;
		
		$files = $this->FileUpload(
			isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
			$file_name ? $file_name : (isset($upload['name']) ? $upload['name'] : null),
			isset($upload['size']) ? $upload['size'] : $this->GetDerverVer('CONTENT_LENGTH'),
			isset($upload['type']) ?  $upload['type'] : $this->GetDerverVer('CONTENT_TYPE'),
			isset($upload['error']) ? $upload['error'] : null
		);
		$this->PrintJSON($files);
	}
	/**
	 * $_SERVERに指定したIDの値を持っていたら返す。
	 *
	 * @return mixi
	 */
	protected function GetDerverVer($id){ return isset($_SERVER[$id]) ? $_SERVER[$id] : ''; }

	/**
	 * 単位付きのファイルサイズをバイト単位で返す
	 *
	 * @param string $val 
	 * @return int
	 */
	function ReturnBytes($val)
	{
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			case 'g':
				$val *= 1024*1024*1024;
			case 'm':
				$val *= 1024*1024;
			case 'k':
				$val *= 1024;
		}
		return $this-FixIntegerOverflow($val);
	}

	/**
	 * 32ビット符号付き整数をオーバーフローしないために
	 *
	 * @param int $size 
	 * @return int
	 */
	protected function FixIntegerOverflow($size)
	{
		if($size < 0)
			$size += 2.0 * (PHP_INT_MAX + 1);
		return $size;
	}
	protected function CheckUploadErr(&$file)
	{
		if( $file->errorNo != 0 )
		{
			$file->error = preg_replace('/#1/',	$name, $this->m_aErr[$file->errorNo]);
			switch($file->errorNo)
			{
				case UPLOAD_ERR_INI_SIZE:
					$file->error = preg_replace('/#2/',	ini_get('upload_max_filesize'), $file->error);
					break;	
				case UPLOAD_ERR_FORM_SIZE:
					$file->error = preg_replace('/#2/',	$_POST['MAX_FILE_SIZE'], $file->error);
					break;	
				case 102:
					$file->error = preg_replace('/#2/',	$this->m_aConfig['product_img_max_width'], $file->error);
					break;	
				case 103:
					$file->error = preg_replace('/#2/',	$this->m_aConfig['product_img_max_height'], $file->error);
					break;
				case 301:
					$file->error = preg_replace('/#2/',	$this->m_aConfig['product_img_upload_max'], $file->error);
			}
			return true;
		}
		return false;
	}
	/**
	 * 
	 *
	 * @param string $uploaded_file 
	 * @param string $name 
	 * @param int	$size 
	 * @param string $type 
	 * @param string $error 
	 * @return mixi
	 */
	protected function FileUpload($uploaded_file, $name, $size, $type, $error)
	{

		$file = new stdClass();
		$file->size = $this->FixIntegerOverflow($size);
		$file->product_id = $this->m_productID;
		if(!preg_match('/^image\/(gif|jpe?g|png)/', $type))		
			$file->type = mime_content_type($uploaded_file);
		else
			$file->type = $type;
		$file->errorNo = 0;
		
		//=====================================
		// エラーチェック
		//=====================================
		if($error)
		{
			// ファイルアップロードに関するPHP内でのエラー
			$file->errorNo = $error;
		}
		else if(!preg_match('/^image\/(gif|jpe?g|png)/', $file->type))
		{
			// ファイルの種類をチェック
			$file->errorNo = 100;
		}
		else if( $file->size > ($this->m_aConfig['product_img_max_size']*1048576) )
		{
			// ファイルサイズのチェック
			$file->errorNo = 101;
		}
		else if( $this->m_productID == 0 )
		{
			// 商品IDが存在しているか？
			$file->errorNo = 200;
		}
		if( $file->errorNo == 0 )
		{
			$max_width = $this->m_aConfig['product_img_max_width'];
			$max_height = $this->m_aConfig['product_img_max_height'];
			if( $max_width || $max_height ) {
				list($img_width, $img_height) = @getimagesize($uploaded_file);
			}
			if(!empty($img_width)) {
				if($max_width && $img_width > $max_width) {
					$file->errorNo = 102;
				}
				if($max_height && $img_height > $max_height) {
					$file->errorNo = 103;
				}
			}
		}
		if( $this->CheckUploadErr($file) ) return $file;	
		
		$this->m_img->UploadImg($file, $uploaded_file);
		
		if( $this->CheckUploadErr($file) ) return $file;
		
//		if( $file->error != "")
//			GC_Utils_Ex::gfPrintLog("アップロードエラー:".$file->error);
		return $file;
	}
		

	
	
	//#########################################################################
	//#########################################################################
	//## 指定商品の画像の順番を変える
	//#########################################################################
	//#########################################################################
	/**
	 * 指定された商品IDの商品画像IDをの順番を変える
	 *
	 * @return void
	 */
	protected function MoveProductImages()
	{
		$dbImg = $this->m_knUtil->GetDB('ProductImg');	
		$img_id = intval($_POST['img_id']);
		$priority = intval($_POST['priority']);
		
		if( $this->m_productID === 0 )
		{
			$this->ErroInJSON('商品画像削除用の商品画像IDが存在しません。');
		}

		$dbImg->Begin();		
		
		// ロック
		if( DB_TYPE == 'pgsql')
			$dbImg->PsqlLockMyTable('SHARE UPDATE EXCLUSIVE MODE');
		else
			$dbImg->MysqlLockMyTable();
		
		// 入れ替え開始
		if( !$dbImg->ChangePriority($this->m_productID, $img_id, $priority))
		{
			$dbImg->Rollback();
			$this->ErroInJSON('商品画像の順番変更に失敗しました。');
			return;
		}
		
		// 入れ替え後の情報収集
		$aInfo = $dbImg->Get($img_id, true);
		$this->RecreateColumn($aInfo);
		$aJson['target'] = $aInfo;				
		//
		$aInfo = $dbImg->GetFromPrdouctID($this->m_productID, true);
		
		$aJson['num'] = count($aInfo);				
		if( count($aInfo) > 0 )
		{
			for($i=0;$i<count($aInfo);++$i)
			{
				$this->RecreateColumn($aInfo[$i]);
			}
			$aJson['list'] = $aInfo;
		}

		$dbImg->Commit();
		
		$this->PrintJSON($aJson);
	}
	protected function RecreateColumn(&$aTmp)
	{
		$aTmp['date'] = date("Y年m月d日 H：i", strtotime($aTmp['create_tm']));
		$aTmp['id'] = $aTmp['img_id'];
		unset($aTmp['img_id']);
		unset($aTmp['create_tm']);
		unset($aTmp['product_id']);
		if( isset($aTmp['imgdat']) )
			unset($aTmp['imgdat']);
	}


	
	
	//#########################################################################
	//#########################################################################
	//## 指定された商品IDの商品画像IDを削除する
	//#########################################################################
	//#########################################################################
	
	/**
	 * 指定された商品IDの商品画像IDを削除する
	 *
	 * @return void
	 */
	protected function DeleteProductImages()
	{
		$dbImg = $this->m_knUtil->GetDB('ProductImg');
		$dbCash = $this->m_knUtil->GetDB('CashImg');
		
		
		if( $this->m_productID === 0 )
		{
			$this->ErroInJSON('商品画像削除用の商品画像IDが存在しません。');
		}

		$dbImg->Begin();		
		
		// ロック
		if( DB_TYPE == 'pgsql')
			$dbImg->PsqlLockMyTable('SHARE UPDATE EXCLUSIVE MODE');
		else
			$dbImg->MysqlLockMyTable();
		
		
			
		// 指定した商品画像IDから商品画像を削除
		$aJson['del_img'] = $dbImg->DeleteFromImageIDs($_POST['nums']);
		if( $aJson['del_img'] === false || $dbImg->IsError() )
		{
			$dbImg->Rollback();
			$this->ErroInJSON('商品画像削除時にDB内でエラーが発生しました');
			return;
		}
		// 順番を再割り当てする
		if( $dbImg->ReassignThePriority($this->m_productID) === false || $dbImg->IsError() )
		{
			$dbImg->Rollback();
			$this->ErroInJSON('商品画像削除時にDB内でエラー(順番の再割り当て)が発生しました');
			return;
		} 
		// 指定した商品画像IDから商品画像キャッシュを削除
		$aJson['del_cash'] = $dbCash->DeleteFromImageIDs($_POST['nums'], $this->m_productID>0);
		if( $aJson['del_cash'] === false || $dbCash->IsError() )
		{
			$dbImg->Rollback();
			$this->ErroInJSON('商品画像削除時にDB内でエラー(キャッシュ削除)が発生しました');	
			return;
		}
		$aJson['list'] = $dbImg->GetFromPrdouctID($this->m_productID, true);
		for($i=0;$i<count($aJson['list']);++$i)
		{
			$this->RecreateColumn($aJson['list'][$i]);
		}
		$dbImg->Commit();
		
		$this->PrintJSON($aJson);
	}
	
	
	//#########################################################################
	//#########################################################################
	//##
	//#########################################################################
	//#########################################################################
		
	/**
	 * 指定された商品IDから商品画像リストのJSONデータを表示する
	 *
	 * @return void
	 */
	protected function GetImageList()
	{
		$dbPImg = $this->m_knUtil->GetDB('ProductImg');
		$aImgInf = array();

		if( isset($_POST['product_id']) )
		{
			$id = 0;
			
			if( intval($_POST['product_id']) != 0 )
				$id = intval($_POST['product_id']);
			else if( intval($_POST['kn_temp_product_id']) != 0 )
				$id = intval($_POST['kn_temp_product_id']);
				
			$aImgInf['list'] = $dbPImg->GetFromPrdouctID($id, true);
			if( count($aImgInf['list']) > 0 )
			{
				$aTmp = &$aImgInf['list'];
				for($i=0;$i<count($aTmp);++$i)
				{
					$this->RecreateColumn($aTmp[$i]);
				}
				$aImgInf['num'] = count($aTmp);
			}
			else
			{
				$aImgInf['num'] = 0;
			}
			
		}
		$this->PrintJSON($aImgInf);
	}
	
};