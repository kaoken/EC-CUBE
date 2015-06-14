<?php
/*
 * This is a plug-in "ProductImagesAddKN" of EC CUBE.
 *
 * Copyright(c) 2013 kaoken CO.,LTD. All Rights Reserved.
 *
 * http://www.kaoken.cg0.org/
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

require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/db/plg_ProductImagesAddKN_DB_Base.php';

/**
 * ProductImagesAddKNプラグイン キャッシュイメージ関連のDBクラス
 *
 * @package ProductImagesAddKN
 * @author kaoken
 * @since PHP 5.3　
 * @version 1.0
 */
class plg_ProductImagesAddKN_DB_CashImg extends plg_ProductImagesAddKN_DB_Base
{
	/**
	 * コンストラクタ
	 */
	public function __construct()
	{
		parent::__construct();
		$this->m_table = self::TABLE_CASH;

		$this->initVar('file_name', self::TYPE_STRING, '', true, 128);
		$this->initVar('img_file', self::TYPE_STRING, '', true, 64);
		$this->initVar('img_id', self::TYPE_INT, 0, true);
		$this->initVar('product_id', self::TYPE_INT, 0, true);
		$this->initVar('width', self::TYPE_INT, 0, true);
		$this->initVar('height', self::TYPE_INT, 0, true);
		$this->initVar('effect', self::TYPE_STRING, '', true, 32);
		$this->initVar('ext', self::TYPE_STRING, '', true, 4);
		$this->initVar('create_tm', self::TYPE_TIME, 'CURRENT_TIMESTAMP', false);
	}
	/**
	 * カラムからキャッシュ用ファイル名を作る
	 *
	 * @param array $aInfo 画像ファイル情報
	 * @return array
	 */
	public function createCashFileName($aInfo)
	{
		$name = $aInfo['img_file'].'$';
		$name .= $aInfo['img_id'].'$';
		$name .= $aInfo['product_id'].'$';
		$name .= $aInfo['width'].'$';
		$name .= $aInfo['height'].'$';
		$name .= $aInfo['effect'].'$';
		$name .= '.'.$aInfo['ext'];
		return $name;
	}
	/**
	 * 画像キャッシュを取得
	 *
	 * @param array $aInfo 画像ファイル名,商品画像ID,幅,高さ,エフェクト名（予約名）の情報を持つ配列
	 *  画像ファイル名はファイル名のみにする。
	 * @return array
	 */
	public function get(&$aInfo)
	{
		if ( $aInfo['src_file'] != '' )
			$aInfo['img_file'] = basename($aInfo['src_file']);

		$where = "file_name = '".$this->createCashFileName($aInfo)."'";

		$arrColumns = $this->db()->select("*", $this->m_table, $where);
		if ( count($arrColumns) == 1 )
		{
			return $arrColumns[0];
		}
		return array();
	}

	/**
	 * 縮小した画像情報を挿入する
	 *
	 * @param array $aInsert 必須Column配列
	 * @return boolean 成功した場合はtrueを返す
	 */
	public function insert(&$aInsert)
	{
		$aInsert['file_name'] = $this->createCashFileName($aInsert);
		$aInsert['create_tm'] = 'CURRENT_TIMESTAMP';
		$this->dataTypeCastFromColumn($aInsert);

		$q  = "INSERT INTO ".$this->m_table."(";
		$q .= $this->getKeyNameConnectString($aInsert).") ";
		$q .= $this->getValuesString($aInsert).";";

		$this->begin();
		$ret = $this->query($q, array(), false, null, MDB2_PREPARE_MANIP);
		if ( $ret === false || $this->isError())
		{
			$this->rollback();
			return false;
		}
		$this->commit();
		return true;
	}

	/**
	 * 商品IDを変更する
	 *
	 * @param int	 $currentID 現在の商品ID
	 * @param int	 $newID	 新しい商品ID
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return mixed
	 */
	public function changeProductID($currentID, $newID, $isTransaction=false)
	{
		$currentID = intval($currentID);
		$newID = intval($newID);

		// 置き換えるのが面倒なので！
		// キャッシュディレクトリから削除,
		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		$knUtil->globDeleteCashImages('*$*$'.$currentID.'$*',false);

		$this->begin($isTransaction);

		$q  = 'UPDATE '.$this->m_table." SET product_id = {$newID} ";
		$q .= "WHERE product_id = {$currentID}";


		$ret = $this->query($q, array(), false, null, MDB2_PREPARE_MANIP);
		$this->commit($isTransaction);



		return $ret;
	}

	/**
	 * キャッシュ画像ファイル数を取得
	 *
	 * @return int
	 */
	public function getNum()
	{
		$ret = $this->db()->select("count(*) as cnt", $this->m_table);
		if ( count($ret) > 0 )
			return $ret[0]['cnt'];

		return 0;
	}

	/**
	 * キャッシュ商品画像を商品IDと順番の番号から取得
	 *
	 * @param int $aCol 商品画像ID,幅,高さ,エフェクト情報がある配列
	 * @return array
	 */
	public function getFromPrductImgId_W_H_E($aCol)
	{
		$where = 'img_id = '.$aCol['img_id'].' AND width = '.$aCol['width'].' AND height = '.$aCol['height']." AND effect = '".$aCol['effect']."'";
		$arrColumns = $this->db()->select("*", $this->m_table, $where);

		if ( count($arrColumns) > 0 )
		{
			return $arrColumns[0];
		}
		return array();
	}

	/**
	 * 商品画像を商品IDと順番の番号かつ、幅高さ、エフェクト値で取得する
	 *
	 * @param array	$aCol  商品画像ID,順番,幅,高さ,エフェクト情報がある配列
	 * @return array
	 */
	public function getFromProductIDAndPriority($aCol)
	{
		$aCol['product_id'] = intval($aCol['product_id']);
		$aCol['priority'] = intval($aCol['priority']);

		$where  = 'width = '.$aCol['width'].' AND height = '.$aCol['height']." AND effect = '".$aCol['effect']."'";
		$where .= " AND img_id = (";
		$where .= 'SELECT img_id FROM '.self::TABLE_IMG.' WHERE ';
		$where .= "product_id = ".$aCol['product_id']." AND priority = ".$aCol['priority'].")";
		$arrColumns = $this->db()->select('*', $this->m_table, $where);
		if ( count($arrColumns) == 0 )
		{
			return array();
		}

		$this->dataTypeCastFromColumn($arrColumns[0]);

		return $arrColumns[0];
	}

	/**
	 * 複数の商品画像IDが入ったものを削除する
	 *
	 * @param array   $aImgID		 商品画像IDが入った配列
	 * @param boolean $isCashImgDir   検索対象はがcash_imageディレクトリの場合true、そうでない場合はtemp_imageディレクトリ
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return array
	 */
	public function deleteFromImageIDs($aImgID, $isCashImgDir=true, $isTransaction=false)
	{
		$this->begin($isTransaction);

		// キャッシュディレクトリから削除
		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		foreach($aImgID as &$id )
		{
			$knUtil->globDeleteCashImages("*\${$id}\$*",$isCashImgDir);
		}
		$where = 'img_id '.$this->createString_xINx_FromAry($aImgID);
		$ret = $this->db()->delete($this->m_table, $where);
		$this->commit($isTransaction);

		return $ret;
	}

	/**
	 * 商品画像ファイル名と一致するものは削除する
	 *
	 * @param string  $fileName	   画像ファイル名
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return array
	 */
	public function deleteFromImageName($fileName, $isTransaction=false)
	{
		if ( $fileName == '' )
		{
			return false;
		}
		$this->begin($isTransaction);

		// キャッシュディレクトリから削除
		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		$knUtil->globDeleteCashImages("{$fileName}\$*");

		// 
		$where = "img_file = '{$fileName}' ";
		$ret = $this->db()->delete($this->m_table, $where);
		$this->commit($isTransaction);

		return $ret;
	}


	/**
	 * DeleteOldImgsメンバ関数内からのみ呼び出される。
	 * plg_ProductImagesAddKN_Util::ReadCashImgFileNames経由して
	 * $aVal['s']日以上アクセスされていないファイルを削除
	 *
	 * @param int    $day         任意の値
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return int 削除された数を返す。失敗、エラー時には-1が返る
	 */
	public function deleteOldImgs($day,$isTransaction=false)
	{
		$day = intval($day);
		// 先にファイル削除の方が良いので！
		// $aVal['day']日以上アクセスされていないファイルを削除
		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		$aVal['s'] = 86400*$day;
		$aVal['cnt'] = 0;
		$aVal['isTransaction'] = $isTransaction;
		$isErr = !$knUtil->readCashImgFileNames($aVal,$this,'deleteOldImgs_call',100);

		// 何らかのトラブルで残ってしまった、レコードもついでに削除
		if ( $day !== 0 && $isErr)
		{
			$where = "";
			if ( DB_TYPE  == 'pgsql')
			{
				$where = "create_tm IN ( SELECT create_tm FROM ".$this->m_table." WHERE ";
				$where .= "date_part('day',now()-create_tm) <= {$day}";
				$where .= " )";
			}
			else
			{
				$where = "timestampdiff(DAY,create_tm,now()) <= {$day}";
			}

			$this->begin($isTransaction);

			$ret = $this->db()->delete($this->m_table, $where);
			if ( $ret === false )
			{
				$this->rollback($isTransaction);
				return -1;
			}
			$this->commit($isTransaction);
		}
		return $aVal['cnt'];
	}

	/**
	 * DeleteOldImgsメンバ関数内からのみ呼び出される。
	 * chdirで既に開始場所が$dirになっている
	 * plg_ProductImagesAddKN_Util::ReadCashImgFileNames経由して
	 * $aVal['s']日以上アクセスされていないファイルを削除
	 *
	 * @param mix    $val         任意の値
	 * @param string $dir         ディレクトリパス
	 * @param array  $aFileName   ファイル名の配列
	 * @return boolean falseを返すと、処理が強制停止されます。
	 */
	public function deleteOldImgs_call(&$val,$dir,&$aFileName)
	{
		$delList = '';
		if ( $val['s'] === 0 )
		{
			foreach($aFileName as $key=>&$name)
			{
				@unlink($name);
				$delList .= "'{$name}',";
				$val['cnt']++;
			}
		}
		else
		{
			foreach($aFileName as $key=>&$name)
			{
				if ( (@fileatime($name) + $val['s']) <= time() )
				{
					@unlink($name);
					$delList .= "'{$name}',";
					$val['cnt']++;
				}
			}
		}
		if ( $delList !== '' )
		{
			$delList = rtrim( $delList, ',');
			$this->begin($val['isTransaction']);
			//
			$where = "";
			if ( $val['s'] !== 0 )
				$where = "file_name IN ({$delList}) ";
			$ret = $this->db()->delete($this->m_table, $where);
			if ( $ret === false )
			{
				$this->rollback($val['isTransaction']);
				$val['cnt'] = -1;
				return false;
			}
			$this->commit($val['isTransaction']);
		}
		return true;
	}


	/**
	 * 一時的に作られた画像が指定時間を過ぎていたら削除する
	 *
	 * @param array   $elapsedHour   経過時間
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return array
	 */
	public function deleteNegativeProductID($elapsedHour, $isTransaction=false)
	{
		// 先にファイル削除の方が良いので！
		// 商品IDがマナス値かつ$aVal['hour']時間以上経過したファイルを削除
		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		$aVal['hour'] = $elapsedHour;
		$knUtil->readCashImgFileNames($aVal,$this,'deleteNegativeProductID_call',1000,false);

		$where = "";
		if ( DB_TYPE  == 'pgsql')
		{
			$where = "create_tm IN ( SELECT create_tm FROM ".$this->m_table." WHERE ";
			$where .= "date_part('hour',now()-create_tm) <= {$elapsedHour}";
			$where .= " ) AND product_id < 0";
		}
		else
		{
			$where = "timestampdiff(HOUR,create_tm,now()) <= {$elapsedHour} AND product_id < 0";
		}

		$this->begin($isTransaction);

		$ret = $this->db()->delete($this->m_table, $where);
		if ( $ret === false )
		{
			$this->rollback($isTransaction);
			return false;
		}
		$this->commit($isTransaction);
		return $ret;
	}
	/**
	 * DeleteNegativeProductIDメンバ関数内からのみ呼び出される。
	 * plg_ProductImagesAddKN_Util::ReadCashImgFileNames経由して
	 * 商品IDがマナス値かつ$val['hour']時間以上経過したファイルを削除
	 *
	 * @param mix    $val         任意の値
	 * @param string $dir         ディレクトリパス
	 * @param array  $aFileName   ファイル名の配列
	 * @return boolean falseを返すと、処理が強制停止されます。
	 */
	public function deleteNegativeProductID_call(&$val,$dir,&$aFileName)
	{
		foreach($aFileName as &$name)
		{
			if ( (@fileatime($dir.$name) + 3600*$val['hour']) <= time() )
				@unlink($dir.$name);
		}
		return true;
	}

	/**
	 * 商品IDから削除する
	 *
	 * @param array   $product_id	 商品ID
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return array
	 */
	public function deleteFromProductID($product_id, $isTransaction=false)
	{
		$product_id = intval($product_id);
		$this->begin($isTransaction);
		// キャッシュディレクトリから削除
		$knUtil = plg_ProductImagesAddKN_Util::getMy();
		$knUtil->globDeleteCashImages('*$*$'.$product_id.'$*');
		//
		$where = "product_id = {$product_id}";
		$ret = $this->db()->delete($this->m_table, $where);
		$this->commit($isTransaction);

		return $ret;
	}
	/**
	 * [商品ID]リンクを失ったレコードを削除します
	 *
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return int
	 */
	public function deleteLostProductIdLink($isTransaction=false)
	{
		$this->begin($isTransaction);

		// 削除対象の商品IDを取得
		$selectQ  = "SELECT p.product_id FROM ".$this->m_table." as p ";
		$selectQ .= "LEFT JOIN dtb_products as d ON p.product_id = d.product_id ";
		$selectQ .= "WHERE p.product_id > 0 AND (d.del_flg = 1 OR d.product_id IS NULL) ";
		$selectQ .= "GROUP BY p.product_id";
		$ret = $this->db()->getAll($selectQ);
		if ( count($ret) > 0 )
		{
			foreach($ret as &$val)
			{
				$knUtil = plg_ProductImagesAddKN_Util::getMy();
				// キャッシュディレクトリから削除
				$knUtil->globDeleteCashImages('*$*$'.$val['product_id'].'$*');
			}
		}
		// 削除処理
		$q  = "DELETE FROM ".$this->m_table." WHERE product_id IN (SELECT product_id FROM(";
		$q .= $selectQ;
		$q .= ")as pp)";

		$ret = $this->query($q, array(), false, null, MDB2_PREPARE_MANIP);
		$this->commit($isTransaction);

		return $ret;
	}

	/**
	 * キャッシュ画像ファイル名から削除する
	 *
	 * @param string  $fileName	  キャッシュ画像ファイル名
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return int
	 */
	public function deleteFromFileName($fileName,$isTransaction=false)
	{
		$this->begin($isTransaction);

		$q  = "DELETE FROM ".$this->m_table." WHERE file_name='{$fileName}'";

		$ret = $this->query($q, array(), false, null, MDB2_PREPARE_MANIP);
		$this->commit($isTransaction);

		return $ret;
	}

	/**
	 * [商品ID]リンクを失った数を取得します。
	 *
	 * @return int
	 */
	public function geNumThatLostProductIdLink()
	{
		$q  = "SELECT count(*) as cnt FROM ".$this->m_table." as p ";
		$q .= "LEFT JOIN dtb_products as d ON p.product_id = d.product_id ";
		$q .= "WHERE p.product_id > 0 AND (d.del_flg = 1 OR d.product_id IS NULL) ";

		$ret = $this->db()->getAll($q);
		if ( count($ret) > 0 )
			return $ret[0]['cnt'];
		return 0;
	}


	/**
	 * インストール
	 * Installはプラグインのインストール時に実行されるようにしてください。
	 * 引数にはdtb_pluginのプラグイン情報が渡されます.
	 *
	 * @param  array $arrPlugin plugin_infoを元にDBに登録されたプラグイン情報(dtb_plugin)
	 * @param  array $arrTableList 存在するテーブル名の配列
	 * @return void
	 */
	public function install($arrPlugin, $arrTableList)
	{
		try {
			$sqlval = "";
			if (!in_array($this->m_table, $arrTableList))
			{
				$sqlval .= "CREATE TABLE ".$this->m_table." (";
				$sqlval .= "  file_name VARCHAR(128) NOT NULL PRIMARY KEY,";
				$sqlval .= "  img_file VARCHAR(64) NOT NULL DEFAULT '',";
				$sqlval .= "  img_id int8 NOT NULL DEFAULT 0,";
				$sqlval .= "  product_id int NOT NULL DEFAULT 0,";
				$sqlval .= "  width smallint NOT NULL,";
				$sqlval .= "  height smallint NOT NULL,";
				$sqlval .= "  effect VARCHAR(32) NOT NULL DEFAULT '',";
				$sqlval .= "  ext VARCHAR(4) NOT NULL,";
				$sqlval .= "  create_tm timestamp NOT NULL ";
				$sqlval .= ");";
				// テーブル作成
				if ( !$this->db()->exec($sqlval) )throw new Exception($this->m_table);
			}
		}
		catch (Exception $e)
		{
			SC_Utils_Ex::sfDispSiteError(FREE_ERROR_MSG, '', false,"テーブル'".$e->getMessage() . "' 作成に失敗しました。");
			return false;
		}
		return true;
	}
}
