<?php

// {{{ requires
require_once CLASS_EX_REALDIR . 'page_extends/admin/LC_Page_Admin_Ex.php';
require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/util/plg_ProductImagesAddKN_Util.php';
require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/plg_ProductImagesAddKN_SC_FormParam_Ex.php';
// }}}


class plg_ProductImagesAddKN_LC_Page_Config extends LC_Page_Admin_Ex
{
	/**
	 * @var null|plg_ProductImagesAddKN_Util
	 */
	protected $m_knUtil = null;


	/**
	 * Page を初期化する.
	 *
	 * @return void
	 */
	public function init()
	{
		parent::init();
		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		$plugin = SC_Plugin_Util_Ex::getPluginByPluginCode('ProductImagesAddKN');
		$this->tpl_mainpage = $knUtil->getTemplatePath('config/admin','Config');
		$this->tpl_subtitle = $plugin['plugin_name'];
	}

	/**
	 * Page のプロセス
	 */
	public function process()
	{
		$this->action();
		$this->sendResponse();
	}

	/**
	 * Page のアクション.
	 */
	public function action()
	{
		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		$plugin = SC_Plugin_Util_Ex::getPluginByPluginCode('ProductImagesAddKN');
		// DB
		$dbAllowableSize = $knUtil->getDB('AllowableSize');
		$dbCashImg = $knUtil->getDB('CashImg');
		$dbConfig = $knUtil->getDB('Config');
		$dbImg = $knUtil->getDB('ProductImg');
		$dbTmpID = $knUtil->getDB('TmpID');

		// 通常
		$objDefaultFP = new plg_ProductImagesAddKN_SC_FormParam_Ex();
		$this->lfInitParamDefault($objDefaultFP);
		$objDefaultFP->setParam($_POST);
		$objDefaultFP->convParam();
		// 追加サイズ
		$objAddSizeFP = new plg_ProductImagesAddKN_SC_FormParam_Ex();
		$this->lfInitParamAddSize($objAddSizeFP);
		$objAddSizeFP->setParam($_POST);
		$objAddSizeFP->convParam();
		// 追加サイズ
		$objOldCahsFP = new plg_ProductImagesAddKN_SC_FormParam_Ex();
		$this->lfInitDelCashFiles($objOldCahsFP);
		$objOldCahsFP->setParam($_POST);
		$objOldCahsFP->convParam();

		//


		$aAddSize = array();
		// プラグイン情報を取得
		$arrForm = $dbConfig->get();

		switch ($this->getMode())
		{
			case 'edit':
				$arrForm = $objDefaultFP->getHashArray();
				$this->arrErr = $objDefaultFP->checkError();
				// エラーなしの場合にはデータを更新
				if (count($this->arrErr) == 0) {
					// データ更新
					//SC_Utils::sfPrintR( $objDefaultFP);
					if (  ($ret = $dbConfig->update($arrForm)) === false )
					{
						$this->arrErr["update_failure"]=true;
					}

					if (count($this->arrErr) == 0) {
						$this->alertMsg = "更新しました。";
					}

				}
				break;
			case 'add':
				$aAddSize = $objAddSizeFP->getHashArray();
				$this->aErrAddSize = $objAddSizeFP->checkError();
				// エラーなしの場合にはデータを更新
				if (count($this->aErrAddSize) == 0)
				{
					if ( $dbAllowableSize->insert($aAddSize,$this->aErrAddSize) === true )
					{
						$this->alertMsg = "追加しました。";
					}
				}
				break;
			case 'del':
				$aAddSize = $objAddSizeFP->getHashArray();
				$this->aErrAddSize = $objAddSizeFP->checkError();
				if (count($this->aErrAddSize) == 0)
				{
					if ( $dbAllowableSize->delete($aAddSize,$this->aErrAddSize) === true )
					{
						$this->alertMsg = "幅：".$aAddSize['width']." 高さ：".$aAddSize['height']."px を削除しました。";
					}
				}
				break;
			case 'old_chas_del':
				// 指定期間アクセスされないキャッシュファイルを削除
				$aOldCash = $objOldCahsFP->getHashArray();
				$this->aErrOldCash = $objOldCahsFP->checkError();
				// エラーなしの場合にはデータを更新
				if (count($this->aErrAddSize) == 0)
				{
					if ( ($delNum = $dbCashImg->deleteOldImgs($aOldCash['days'],true)) !== -1 )
					{
						$this->alertMsg = "{$delNum} 個 キャッシュ画像ファイルを削除しました。";
					}
					else
					{
						$this->alertMsg = "古いキャッシュ画像ファイル削除に失敗しました。";
					}
				}

				break;
			case 'lost_clean':
				$dbImg->begin();
				$dbImg->deleteLostProductIdLink();
				$dbCashImg->deleteLostProductIdLink();
				$dbImg->commit();
				$this->alertMsg = "リンク切れを削除しました。";
				break;
			default:
				// プラグイン情報を取得
				$arrForm = $dbConfig->get();

				break;
		}

		//---------------------------
		// 情報
		//---------------------------
		// 許容幅高さテーブルサイズ
		//$this->aInfo['tb_size_ar'] = $dbAllowableSize->getTableSize();
		// キャッシュイメージテーブルサイズ
		//$this->aInfo['tb_size_cash'] = $dbCashImg->getTableSize();
		// コンフィグテーブルサイズ
		//$this->aInfo['tb_size_config'] = $dbConfig->getTableSize();
		// 商品画像テーブルサイズ
		$this->aInfo['tb_size_img'] = $dbImg->getTableSize();
		// 一時IDテーブルサイズ
		//$this->aInfo['tb_size_tmp'] = $dbTmpID->getTableSize();

		// 商品とリンク切れ
		$this->aInfo['lost_product_img_num'] = $dbImg->geNumThatLostProductIdLink();
		$this->aInfo['lost_cash_num'] = $dbCashImg->geNumThatLostProductIdLink();

		// キャッシュ画像ファイル数
		$this->aInfo['cash_img_num'] = $dbCashImg->getNum();
		// 商品画像ファイル数
		$this->aInfo['img_id'] = $dbImg->getNum();
		//
		$this->aInfo['max_execution_time'] = intval(ini_get('max_execution_time'));


		// キャッシュディレクトリの合計ファイルサイズおよびファイル数の取得
		$chasImgPath = $knUtil->getHtmlDirPath('/cash_images');
		$aFileInfo = $knUtil->getDirFileSize($chasImgPath);
		$this->aInfo['chas_imgs_size'] = $aFileInfo['size'];
		$this->aInfo['cash_img_num'] = $aFileInfo['num'];

		//
		if ( !isset($aOldCash['days']) )
			$this->aInfo['days'] = 31;
		else
			$this->aInfo['days'] = $aOldCash['days'];


		// ブラウザーのスクロール位置
		$this->aInfo['scroll_x'] = intval($_POST['scroll_x']);
		$this->aInfo['scroll_y'] = intval($_POST['scroll_y']);


		$this->arrForm = $arrForm;
		$this->aAddSize = $aAddSize;

		// 許容サイズリスト取得
		$this->aSizeList = $dbAllowableSize->getList();
		// プラグインが有効か？
		$this->plgEnable = $plugin['enable'] == 1;

		$this->setTemplate($this->tpl_mainpage);
	}

	/**
	 * デストラクタ.
	 *
	 * @return void
	 */
	public function destroy()
	{
		parent::destroy();
	}

	/**
	 * 基本パラメーター情報の初期化
	 *
	 * @param plg_ProductImagesAddKN_SC_FormParam_Ex $objFP SC_FormParamインスタンス
	 */
	private function lfInitParamDefault(&$objFP)
	{
		$objFP->addParamNumLimit('リサイズ時の圧縮率', 'compression_rate', 10,100);
		$objFP->addParamNumLimit('商品の複製時に商品画像も複製', 'product_img_copy', 0,1);
		$objFP->addParamNumLimit('画像の最大データサイズ', 'product_img_max_size', 1,15);
		$objFP->addParamNumLimit('1つの商品にアップロードできる画像の最大数', 'product_img_upload_max', 1,64);
		$objFP->addParamNumLimit('画像の最大幅', 'product_img_max_width', 1,9999);
		$objFP->addParamNumLimit('画像の最大の高さ', 'product_img_max_height', 1,9999);
	}

	/**
	 * サイズ追加パラメーター情報の初期化
	 *
	 * @param plg_ProductImagesAddKN_SC_FormParam_Ex $objFP SC_FormParamインスタンス
	 */
	private function lfInitParamAddSize(&$objFP)
	{
		$objFP->addParamNumLimit('幅', 'width', 1,9999);
		$objFP->addParamNumLimit('高さ', 'height', 1,9999);
	}

	/**
	 * サイズ追加パラメーター情報の初期化
	 *
	 * @param plg_ProductImagesAddKN_SC_FormParam_Ex $objFP SC_FormParamインスタンス
	 */
	private function lfInitDelCashFiles(&$objFP)
	{
		$objFP->addParamNumLimit('日数', 'days', 0,9999);
	}
}
