<?php
/*
 * This is a plug-in "ProductImagesAddKN" of EC CUBE.
 *
 * Copyright(c) 2013 kaoken CO.,LTD. All Rights Reserved.
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
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->m_table = self::TABLE_CASH;
		
		$this->InitVar('file_name', self::TYPE_STRING, '', true, 128);
		$this->InitVar('img_file', self::TYPE_STRING, '', true, 64);
		$this->InitVar('img_id', self::TYPE_INT, 0, true);
		$this->InitVar('product_id', self::TYPE_INT, 0, true);
		$this->InitVar('width', self::TYPE_INT, 0, true);
		$this->InitVar('height', self::TYPE_INT, 0, true);
		$this->InitVar('effect', self::TYPE_STRING, '', true, 32);
		$this->InitVar('ext', self::TYPE_STRING, '', true, 4);
		$this->InitVar('create_tm', self::TYPE_TIME, 'CURRENT_TIMESTAMP', false);
   }
	/**
	 * カラムからキャッシュ用ファイル名を作る
	 *
	 * @param int $srcFile 画像ファイル名
	 * @param int $img_id  商品画像ID
	 * @param int $width   幅
	 * @param int $height  高さ
	 * @param int $eff	 エフェクト名（予約名）
	 * @return array
	 */
	public function CreateCashFileName($aInfo)
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
	public function Get(&$aInfo)
	{	
		if ( $aInfo['src_file'] != '' )
			$aInfo['img_file'] = basename($aInfo['src_file']);

		$where = "file_name = '".$this->CreateCashFileName($aInfo)."'";
		
		$arrColumns = $this->DB()->select("*", $this->m_table, $where);
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
	public function Insert(&$aInsert)
	{
		$aInsert['file_name'] = $this->CreateCashFileName($aInsert);
		$aInsert['create_tm'] = 'CURRENT_TIMESTAMP';
		$this->DataTypeCastFromColumn($aInsert);
		  
		$q  = "INSERT INTO ".$this->m_table."(";
		$q .= $this->GetKeyNameConnectString($aInsert).") ";
		$q .= $this->GetValuesString($aInsert).";";

		$this->Begin();
		$ret = $this->Query($q, array(), false, null, MDB2_PREPARE_MANIP);
		if ( $ret === false || $this->IsError())
		{
			$this->Rollback();
			return false;	
		}
		$this->Commit();
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
	public function ChangeProductID($currentID, $newID, $isTransaction=false)
	{
		$currentID = intval($currentID);
		$newID = intval($newID);
		
		// 置き換えるのが面倒なので！
		// キャッシュディレクトリから削除,
		$knUtil = plg_ProductImagesAddKN_Util::GetMy();
		$knUtil->GlobDeleteCashImages('*$*$'.$currentID.'$*',false);
		
		$this->Begin($isTransaction);
		
		$q  = 'UPDATE '.$this->m_table." SET product_id = {$newID} ";
		$q .= "WHERE product_id = {$currentID}";
				  
				  
		$ret = $this->Query($q, array(), false, null, MDB2_PREPARE_MANIP);
		$this->Commit($isTransaction);
		

		
		return $ret;
	}
			
	/**
	 * キャッシュ画像ファイル数を取得
	 * 
	 * @return int
	 */
	public function GetNum()
	{
		$ret = $this->DB()->select("count(*) as cnt", $this->m_table);
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
	public function GetFromPrductImgId_W_H_E($aCol)
	{
		$where = 'img_id = '.$aCol['img_id'].' AND width = '.$aCol['width'].' AND height = '.$aCol['height']." AND effect = '".$aCol['effect']."'";
		$arrColumns = $this->DB()->select("*", $this->m_table, $where);

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
	public function GetFromPrdouctIDAndPriority($aCol)
	{
        $aCol['product_id'] = intval($aCol['product_id']);
        $aCol['priority'] = intval($aCol['priority']);
		
		$where  = 'width = '.$aCol['width'].' AND height = '.$aCol['height']." AND effect = '".$aCol['effect']."'";
		$where .= " AND img_id = (";
		$where .= 'SELECT img_id FROM '.self::TABLE_IMG.' WHERE ';
		$where .= "product_id = ".$aCol['product_id']." AND priority = ".$aCol['priority'].")";
		$arrColumns = $this->DB()->select('*', $this->m_table, $where);
		if ( count($arrColumns) == 0 )
		{
			return array();
		}
		
		$this->DataTypeCastFromColumn($arrColumns[0]);
			
		return $arrColumns[0];
	}
	
	/**
	 * 複数の商品画像IDが入ったものを削除する
	 * 
	 * @param array   $aImgID		 商品画像IDが入った配列
	 * @param boolean $isCashImgDir   検索対象はがcash_imageディレクトリの場合true、そうでない場合はtmp_imageディレクトリ
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return array
	 */
	public function DeleteFromImageIDs($aImgID, $isCashImgDir=true, $isTransaction=false)
	{
		$this->Begin($isTransaction);
		
		// キャッシュディレクトリから削除
		$knUtil = plg_ProductImagesAddKN_Util::GetMy();
		foreach($aImgID as &$id )
		{
			$knUtil->GlobDeleteCashImages("*\${$id}\$*",$isCashImgDir);
		}
		$where = 'img_id '.$this->CreateString_xINx_FromAry($aImgID);
		$ret = $this->DB()->delete($this->m_table, $where);
		$this->Commit($isTransaction);
		
		return $ret;
	}
	
	/**
	 * 商品画像ファイル名と一致するものは削除する
	 * 
	 * @param string  $fileName	   画像ファイル名
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return array
	 */
	public function DeleteFromImageName($fileName, $isTransaction=false)
	{
		if ( $fileName == '' )
		{
			return false;	
		}
		$this->Begin($isTransaction);
		
		// キャッシュディレクトリから削除
		$knUtil = plg_ProductImagesAddKN_Util::GetMy();
		$knUtil->GlobDeleteCashImages("{$fileName}\$*");
		
		// 
		$where = "img_file = '{$fileName}' ";
		$ret = $this->DB()->delete($this->m_table, $where);
		$this->Commit($isTransaction);
		
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
	public function DeleteOldImgs($day,$isTransaction=false)
	{
		$day = intval($day);
		// 先にファイル削除の方が良いので！
		// $aVal['day']日以上アクセスされていないファイルを削除
		$knUtil = plg_ProductImagesAddKN_Util::GetMy();
		$aVal['s'] = 86400*$day;
        $aVal['cnt'] = 0;
        $aVal['isTransaction'] = $isTransaction;
		$isErr = !$knUtil->ReadCashImgFileNames($aVal,$this,'DeleteOldImgs_Call',100);
		
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
	
			$this->Begin($isTransaction);
			
			$ret = $this->DB()->delete($this->m_table, $where);	 
			if ( $ret === false )
			{
				$this->Rollback($isTransaction);
				return -1;	
			}
			$this->Commit($isTransaction);
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
	public function DeleteOldImgs_Call(&$val,$dir,&$aFileName)
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
			$this->Begin($val['isTransaction']);
			//
			$where = "";
			if ( $val['s'] !== 0 )
				$where = "file_name IN ({$delList}) ";
			$ret = $this->DB()->delete($this->m_table, $where);
			if ( $ret === false )
			{
				$this->Rollback($val['isTransaction']);
				$val['cnt'] = -1;
				return false;	
			}
			$this->Commit($val['isTransaction']);
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
	public function DeleteNegativeProductID($elapsedHour, $isTransaction=false)
	{
		// 先にファイル削除の方が良いので！
		// 商品IDがマナス値かつ$aVal['hour']時間以上経過したファイルを削除
		$knUtil = plg_ProductImagesAddKN_Util::GetMy();
		$aVal['hour'] = $elapsedHour;
		$knUtil->ReadCashImgFileNames($aVal,$this,'DeleteNegativeProductID_Call',1000,false);
		
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

		$this->Begin($isTransaction);
		
		$ret = $this->DB()->delete($this->m_table, $where);	 
		if ( $ret === false )
		{
			$this->Rollback($isTransaction);
			return false;	
		}
		$this->Commit($isTransaction);
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
	public function DeleteNegativeProductID_Call(&$val,$dir,&$aFileName)
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
	public function DeleteFromProductID($product_id, $isTransaction=false)
	{
		$product_id = intval($product_id);
		$this->Begin($isTransaction);
		// キャッシュディレクトリから削除
		$knUtil = plg_ProductImagesAddKN_Util::GetMy();
		$knUtil->GlobDeleteCashImages('*$*$'.$product_id.'$*');
		//
		$where = "product_id = {$product_id}";
		$ret = $this->DB()->delete($this->m_table, $where);
		$this->Commit($isTransaction);
		
		return $ret;
	}
	/**
	 * [商品ID]リンクを失ったレコードを削除します
	 * 
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return int
	 */
	public function DeleteLostProductIdLink($isTransaction=false)
	{
		$this->Begin($isTransaction);
		
		// 削除対象の商品IDを取得
		$selectQ  = "SELECT p.product_id FROM ".$this->m_table." as p ";
		$selectQ .= "LEFT JOIN dtb_products as d ON p.product_id = d.product_id ";
		$selectQ .= "WHERE p.product_id > 0 AND (d.del_flg = 1 OR d.product_id IS NULL) ";
		$selectQ .= "GROUP BY p.product_id";
		$ret = $this->DB()->getAll($selectQ);
		if ( count($ret) > 0 )
		{
			foreach($ret as &$val)
			{
				$knUtil = plg_ProductImagesAddKN_Util::GetMy();
				// キャッシュディレクトリから削除
				$knUtil->GlobDeleteCashImages('*$*$'.$val['product_id'].'$*');
			}
		}
		// 削除処理
		$q  = "DELETE FROM ".$this->m_table." WHERE product_id IN (SELECT product_id FROM(";
		$q .= $selectQ;
		$q .= ")as pp)";
							  
		$ret = $this->Query($q, array(), false, null, MDB2_PREPARE_MANIP);
		$this->Commit($isTransaction);
		
		return $ret;
	}
	
	/**
	 * キャッシュ画像ファイル名から削除する
	 * 
	 * @param string  $fileName	  キャッシュ画像ファイル名
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return int
	 */
	public function DeleteFromFileName($fileName,$isTransaction=false)
	{
		$this->Begin($isTransaction);
		
		$q  = "DELETE FROM ".$this->m_table." WHERE file_name='{$fileName}'";
							  
		$ret = $this->Query($q, array(), false, null, MDB2_PREPARE_MANIP);
		$this->Commit($isTransaction);
		
		return $ret;
	}
	
	/**
	 * [商品ID]リンクを失った数を取得します。
	 * 
	 * @return int
	 */
	public function GeNumThatLostProductIdLink()
	{
		$q  = "SELECT count(*) as cnt FROM ".$this->m_table." as p ";
		$q .= "LEFT JOIN dtb_products as d ON p.product_id = d.product_id ";
		$q .= "WHERE p.product_id > 0 AND (d.del_flg = 1 OR d.product_id IS NULL) ";
			
		$ret = $this->DB()->getAll($q);
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
	public function Install($arrPlugin, $arrTableList)
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
				if ( !$this->DB()->exec($sqlval) )throw new Exception($this->m_table);
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
?>