<?php
/*
 * ProductImagesAddKN
 *
 * File:ProductImageAddKn.php
 *
 * Copyright(c) kaoken All Rights Reserved.
 *
 * http://www.kaoken.cg0.xyz/
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/util/plg_ProductImagesAddKN_Util.php';
require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/plg_ProductImagesAddKN_Img.php';
require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/plg_ProductImagesAddKN_PageUtil.php';

/**
 * ProductImagesAddKNプラグイン情報クラス
 *
 * @package ProductImagesAddKN
 * @author kaoken
 * @since PHP 5.3　
 * @version 1.0
 */
class ProductImagesAddKN extends SC_Plugin_Base
{
	private $m_aConfig = array();
	/**
	 * @var null|plg_ProductImagesAddKN_Img
	 */
	private $m_img = null;

	/**
	 * コンストラクタ
	 *
	 * @param  array $arrSelfInfo 自身のプラグイン情報
	 */
	public function __construct(array $arrSelfInfo)
	{
		parent::__construct($arrSelfInfo);
		$knUtil = plg_ProductImagesAddKN_Util::getMy();

		$dbConfig = $knUtil->getDB('Config');
		$this->m_aConfig = $dbConfig->get();
		$this->m_img = new plg_ProductImagesAddKN_Img($this->m_aConfig);
	}

	/**
	 * インストール
	 * installはプラグインのインストール時に実行されます.
	 * 引数にはdtb_pluginのプラグイン情報が渡されます.
	 * メモ：ProductImagesAddKNクラス内のメンバ変数などはアクセスできない。
	 *
	 * @param  array $arrPlugin plugin_infoを元にDBに登録されたプラグイン情報(dtb_plugin)
	 */
	public function install($arrPlugin, $objPluginInstaller = null)
	{
		$knUtil = plg_ProductImagesAddKN_Util::getMy();


		// ファイル単位
		$knUtil->copyToHTMLFileFromUploadFile('html_realdir/logo.png','logo.png');
		$knUtil->copyToHTMLFileFromUploadFile('html_realdir/img_upload.php','img_upload.php');
		$knUtil->copyToHTMLFileFromUploadFile('html_realdir/uploadify.swf','uploadify.swf');

		// ディレクトリ単位
		$knUtil->copyToHTMLDirFromUploadDir('/html_realdir/media/img', '/img');
		$knUtil->copyToHTMLDirFromUploadDir("/html_realdir/media/js", '/js');
		$knUtil->copyToHTMLDirFromUploadDir('/html_realdir/media/css', '/css');

		// 一時ファイル作成用ディレクトリ作成
		$knUtil->dirCreatedToHTML('temp');
		// リサイズした画像を入れるディレクトリ作成
		$knUtil->dirCreatedToHTML('cash_images');
		// リサイズした画像を入れるディレクトリ作成
		$knUtil->dirCreatedToHTML('temp_images');

		//--------------------------------
		// テーブル作成
		//--------------------------------
		self::tableCreateOrDrop(true,$arrPlugin);
	}

	/**
	 * アンインストール
	 * uninstallはアンインストール時に実行されます.
	 * 引数にはdtb_pluginのプラグイン情報が渡されます.
	 * メモ：ProductImagesAddKNクラス内のメンバ変数などはアクセスできない。
	 *
	 * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
	 * @return void
	 */
	public function uninstall($arrPlugin, $objPluginInstaller = null)
	{
		//--------------------------------
		// テーブル削除
		//--------------------------------
		self::tableCreateOrDrop(false,$arrPlugin);
	}

	/**
	 * DBのテーブル作成または削除をする。install,uninstall内でのみ呼ばれる
	 *
	 * @param  boolean $isCreate trueで作成
	 * @param  array   $arrPlugin プラグイン情報の連想配列(dtb_plugin)
	 * @return boolean true でエラーなし
	 */
	protected static function tableCreateOrDrop($isCreate,$arrPlugin)
	{
		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		$objQ = SC_Query_Ex::getSingletonInstance();
		$arrTableList = $objQ->listTables();

		$isErr = false;
		$aDBClass = array("AllowableSize","CashImg","Config","ProductImg","TmpID");

		$objQ->begin();
		foreach($aDBClass as &$val) {
			$objDBTmp = $knUtil->getDB($val);

			if ( $isCreate )
				$isErr = !$objDBTmp->install($arrPlugin, $arrTableList);
			else
				$isErr = !$objDBTmp->uninstall($arrPlugin, $arrTableList);
			if ( $isErr )
			{
				$objQ->rollback();
				break;
			}

			unset($objDBTmp);
		}
		if ( !$isErr )$objQ->commit();

		return $isErr;
	}

	/**
	 * 稼働
	 * enableはプラグインを有効にした際に実行されます.
	 * 引数にはdtb_pluginのプラグイン情報が渡されます.
	 *
	 * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
	 * @return string
	 */
	public function enable($arrPlugin, $objPluginInstaller = null)
	{
		$ret = "";
		if (!extension_loaded('imagick'))
			$ret .= "・ImageMagickモジュールが有効ではありません。<br>";
		if (!extension_loaded('json'))
			$ret .= "・jsonモジュールが有効ではありません。<br>";
		if ( $ret != "" )return $ret;
	}

	/**
	 * 停止
	 * disableはプラグインを無効にした際に実行されます.
	 * 引数にはdtb_pluginのプラグイン情報が渡されます.
	 *
	 * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
	 * @return void
	 */
	public function disable($arrPlugin, $objPluginInstaller = null)
	{
		// nop
	}

	/**
	 * プラグインヘルパーへ, コールバックメソッドを登録します.
	 *
	 * @param SC_Helper_Plugin $objHelperPlugin
	 * @param integer $priority
	 */
	public function register(SC_Helper_Plugin $objHelperPlugin, $priority)
	{
		parent::register($objHelperPlugin, $priority);

		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		$path = $knUtil->getPUploadPath('/class','HeadNavi.php');
		$objHelperPlugin->setHeadNavi($path);
	}

	/**
	 * loadClassFileChange のフック
	 * SC_系のクラスをプラグイン側で置き換える
	 *
	 * @param string $plugin_class     読み込む事を要求されたクラスの名前
	 * @param string $plugin_classpath 本来読み込む予定であるクラスファイルのパス
	 */
	public function loadClassFileChange(&$plugin_class, &$plugin_classpath)
	{
		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		if ($plugin_class == 'SC_UploadFile_Ex') {
			$plugin_classpath = $knUtil->getPUploadPath('/class', 'SC_UploadFile.php');
			$plugin_class = 'plg_ProductImagesAddKN_SC_UploadFile';
		} else if ($plugin_class == 'SC_Image_Ex') {
			$plugin_classpath = $knUtil->getPUploadPath('/class', 'SC_Image.php');
			$plugin_class = 'plg_ProductImagesAddKN_SC_Image';
		}
	}

	/**
	 * プラグインヘルパーへ, コールバックメソッドを登録します.
	 *
	 * @param LC_Page_Ex  $objPage
	 */
	public function preProcess(LC_Page_Ex $objPage)
	{
		if (get_class($objPage) == 'LC_Page_ResizeImage_Ex') {
			$this->m_img->readImage($objPage);
		}
	}

	/**
	 * preFilterTransform のフック
	 * prefilterコールバック関数
	 * テンプレートの変更処理を行います.
	 *
	 * @param string     $source テンプレートのHTMLソース
	 * @param LC_Page_Ex $objPage ページオブジェクト
	 * @param string     $filename テンプレートのファイル名
	 * @return void
	 */
	public function prefilterTransform( &$source, LC_Page_Ex $objPage, $filename )
	{
		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		// SC_Helper_Transformのインスタンスを生成.
		$objTransform = new SC_Helper_Transform($source);
		// 呼び出し元テンプレートを判定します.
		switch($objPage->arrPageLayout['device_type_id']){
			case DEVICE_TYPE_MOBILE: // モバイル
			case DEVICE_TYPE_SMARTPHONE: // スマホ
				break;
			case DEVICE_TYPE_PC: // PC
				// 商品一覧画面
//				if (strpos($filename, 'products/list.tpl') !== false) {
//				}
				break;
			case DEVICE_TYPE_ADMIN: // 管理画面
//				$knUtil->outputFile("test.txt", $objPage);
			default:
//				$knUtil->outputFile("test.txt", $filename."\n");
				// カテゴリ登録画面
//				if (strpos($filename, 'products/category.tpl') !== false) {
//				}
				// 商品ナビゲーション
//				if (strpos($filename, 'products/subnavi.tpl') !== false) {
//				}

				// 商品管理 のページ
				if (strpos($filename, 'products/index.tpl') !== false){
					$path = $knUtil->getTemplatePath('/products/admin','TdMainListImage');
					$objTransform->select('.thumbnail', 0)->replaceElement(file_get_contents($path));
				}
				// 商品登録・編集画面
				elseif (strpos($filename, 'products/product.tpl') !== false){
					$path = $knUtil->getTemplatePath('/products/admin','AddImgForm');
					$objTransform->select('#products .form', 0)->insertAfter(file_get_contents($path));
				}
				// 商品登録・編集の確認画面
				elseif (strpos($filename, 'products/confirm.tpl') !== false){
					$path = $knUtil->getTemplatePath('/products/admin','Confirm');
					$objTransform->select('.btn-area', 0)->insertBefore(file_get_contents($path));
				}
				break;
		}

		// 変更を実行します
		$source = $objTransform->getHTML();
	}

	/**
	 * LC_Page_ResizeImage_action_before のフック
	 * LC_Page_ResizeImageのaction実行前に呼び出す
	 *
	 * @param LC_Page_EX $objPage LC_Page_ResizeImageのpageオブジェクト
	 */
	public function hookResizeImageActionBefore(LC_Page_EX $objPage)
	{
		$this->m_img->readImage($objPage);
	}


	/**
	 * アップロードファイルパラメーター情報の初期化
	 * - 画像ファイル用
	 *
	 * @param  object $objUpFile SC_UploadFileインスタンス
	 */
	protected function lfInitFile(&$objUpFile)
	{
		$objUpFile->addFile('一覧-メイン画像', 'main_list_image', array('jpg', 'gif', 'png'),IMAGE_SIZE, false, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT);
		$objUpFile->addFile('詳細-メイン画像', 'main_image', array('jpg', 'gif', 'png'), IMAGE_SIZE, false, NORMAL_IMAGE_WIDTH, NORMAL_IMAGE_HEIGHT);
		$objUpFile->addFile('詳細-メイン拡大画像', 'main_large_image', array('jpg', 'gif', 'png'), IMAGE_SIZE, false, LARGE_IMAGE_WIDTH, LARGE_IMAGE_HEIGHT);
		for ($cnt = 1; $cnt <= PRODUCTSUB_MAX; $cnt++) {
			$objUpFile->addFile("詳細-サブ画像$cnt", "sub_image$cnt", array('jpg', 'gif', 'png'), IMAGE_SIZE, false, NORMAL_SUBIMAGE_WIDTH, NORMAL_SUBIMAGE_HEIGHT);
			$objUpFile->addFile("詳細-サブ拡大画像$cnt", "sub_large_image$cnt", array('jpg', 'gif', 'png'), IMAGE_SIZE, false, LARGE_SUBIMAGE_WIDTH, LARGE_SUBIMAGE_HEIGHT);
		}
	}

	/**
	 * LC_Page_Admin_Products_action_after のフック
	 * 管理画面：商品管理 のページ after
	 * @param LC_Page_Admin_Products_EX $objPage
	 */
	public function pageAdminProductsActionAfter($objPage)
	{
		/**
		 * @var $knUtil   plg_ProductImagesAddKN_Util
		 * @var $dbImg    plg_ProductImagesAddKN_DB_ProductImg
		 * @var $dbCash   plg_ProductImagesAddKN_DB_CashImg
		 */

		$knUtil = plg_ProductImagesAddKN_Util::getMy();

		$dbImg = $knUtil->getDB('ProductImg');
		$dbCash = $knUtil->getDB('CashImg');

		$mode = $objPage->getMode();
		switch ($mode) {
			case 'delete':
				// 商品IDに関連する商品画像およびキャッシュ画像削除
				$product_id = $_POST['product_id'];
				$dbImg->begin();
				if ( $dbImg->deleteFromProductID($product_id) === false )
				{ $dbImg->rollback(); return; }
				if ( $dbCash->deleteFromProductID($product_id) === false )
				{ $dbImg->rollback(); return; }
				$dbImg->commit();
				break;
			case 'search':
				for( $i=0; $i<$objPage->tpl_linemax; $i++ ) {
					$ref = &$objPage->arrProducts[$i];
					$aInfo = $dbImg->getFromProductIDAndPriority($ref['product_id'], 0);
					$ref['product_fast_img_id'] = $aInfo['img_id'];
				}
				break;
		}

	}

	/**
	 * LC_Page_preProcess のフック
	 * Page を初期化する.
	 * @param object $objPlugin
	 */
	public function pagePreProcess($objPlugin)
	{
		// すべてのページ共通
		$objPlugin->productImagesAddKn = new plg_ProductImagesAddKN_PageUtil($this->m_img);
	}

	/**
	 * LC_Page_Admin_Products_Product_action_after のフック
	 * 管理画面：商品登録・編集ページ after
	 * @param LC_Page_Admin_EX $objPage
	 */
	public function pageAdminProductsProductActionAfter( $objPage )
	{
		/**
		 * @var $knUtil   plg_ProductImagesAddKN_Util
		 * @var $dbImg    plg_ProductImagesAddKN_DB_ProductImg
		 * @var $dbCash   plg_ProductImagesAddKN_DB_CashImg
		 * @var $dbTmpID  plg_ProductImagesAddKN_DB_TmpID
		 */
		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		// DB
		$dbImg = $knUtil->getDB('ProductImg');
		$dbCash = $knUtil->getDB('CashImg');
		$dbTmpID = $knUtil->getDB('TmpID');

		$mode = $objPage->getMode();

		$objPage->arrForm['kn_temp_product_id'] = 0;
		$objPage->isMoveForciblyKn = false;

		$product_id = intval($objPage->arrForm['product_id']);

		if ( $product_id === 0 ) {
			if ( $mode == '' || $mode == 'copy') {
				$objPage->isMoveForciblyKn = true;
				$dbTmpID = $knUtil->getDB('TmpID');
				$_SESSION['kn_temp_product_id'] = $dbTmpID->getCreateID('ProductImg');
				if ( $_SESSION['kn_temp_product_id'] === 0 ) {
					// 失敗
					SC_Utils_Ex::sfDispSiteError(FREE_ERROR_MSG, "", false,"<b>ProductImagesAddKNプラグインより</b><br /><br />商品画像で使用する一時商品ID作成に失敗しました。");
				}
				$objPage->arrForm['kn_temp_product_id'] = $_SESSION['kn_temp_product_id'];
			} else if ( isset($_SESSION['kn_temp_product_id']) ) {
				$objPage->arrForm['kn_temp_product_id'] = $_SESSION['kn_temp_product_id'];
			}
			$product_id = intval($objPage->arrForm['kn_temp_product_id']);

		} else if ( isset($_SESSION['kn_temp_product_id']) && $mode != 'complete' ) {
			unset($_SESSION['kn_temp_product_id']);
		}

		// お掃除
		// 一時的に作られた商品画像は24時間以上過ぎたら削除
		$dbImg->deleteNegativeProductID(24,true);
		$dbCash->deleteNegativeProductID(24,true);


		// 商品画像をアップロードするにあたっての関する情報
		$objPage->arrForm['kn_product_img_max_width'] = $this->m_aConfig['product_img_max_width'];
		$objPage->arrForm['kn_product_img_max_height'] = $this->m_aConfig['product_img_max_height'];
		$objPage->arrForm['kn_product_img_upload_max'] = $this->m_aConfig['product_img_upload_max'];
		$objPage->arrForm['kn_product_img_max_size_prefix'] = $knUtil->getFileSizePrefix($this->m_aConfig['product_img_max_size']*1048576);
		$objPage->arrForm['kn_product_img_max_size'] = $this->m_aConfig['product_img_max_size'];

		switch( $mode ) {
			case 'copy' :
				// 複製
				if ( $this->m_aConfig['product_img_copy'] == 1 ) {
					$product_id = intval($_POST['product_id']);
					$dbImg->cloneFormProductID($product_id,$_SESSION['kn_temp_product_id'],true);
				}

				break;
			case 'edit':
				// コンファーム
				$objPage->arrProductImgs = $dbImg->getFromProductID($product_id, true);

				break;
			case 'complete':
				if ( isset($_SESSION['kn_temp_product_id']) ) {
					$currentID = $_SESSION['kn_temp_product_id'];
					$dbTmpID->begin();
					if ( $dbTmpID->delete($currentID, 'ProductImg' ) === false )
					{ $dbTmpID->rollback(); return; }
					if ( $dbImg->changeProductID($currentID, $product_id ) === false )
					{ $dbTmpID->rollback(); return; }
					if ( $dbCash->changeProductID($currentID, $product_id ) === false )
					{ $dbTmpID->rollback(); return; }
					$dbTmpID->commit();
					unset($_SESSION['kn_temp_product_id']);
				}
				break;
			case 'moveForciblyKn':
				// 商品登録のはじめの一回のみ呼ばれる
				break;
			case '':
				// 初期
				break;
			default:
				;
		}
//		echo $objPage->getMode();
//		exit;
	}
}
