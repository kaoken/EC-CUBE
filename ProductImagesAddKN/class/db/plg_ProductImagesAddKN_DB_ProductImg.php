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

require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/db/plg_ProductImagesAddKN_DB_Base.php';

/**
* ProductImagesAddKNプラグイン 商品イメージ関連のDBクラス
*
* @package ProductImagesAddKN
* @author kaoken
* @since PHP 5.3　
* @version 0.1
*/
class plg_ProductImagesAddKN_DB_ProductImg extends plg_ProductImagesAddKN_DB_Base
{
	/**
	 * コンストラクタ
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->m_table = self::TABLE_IMG;			

		$this->InitVar('img_id', self::TYPE_INT, 0, false);
		$this->InitVar('imgdat', self::TYPE_BLOB, 0, true);
		$this->InitVar('width', self::TYPE_INT, 0, true);
		$this->InitVar('height', self::TYPE_INT, 0, true);
		$this->InitVar('mime', self::TYPE_STRING, '', true, 16);
		$this->InitVar('product_id', self::TYPE_INT, 0, true);
		$this->InitVar('priority', self::TYPE_INT, 0, true);
		$this->InitVar('create_tm', self::TYPE_TIME, 'CURRENT_TIMESTAMP', false);
	}
	
	/**
	 * 指定した商品IDの画像を新たな商品IDで画像を複製する。
	 * 
	 * @param int	 $product_id	コピー元の商品ID
	 * @param int	 $new_id		コピー後の新たな商品ID
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return int
	 */
	public function CloneFormProductID($product_id, $new_id,$isTransaction=false)
	{
		$this->Begin($isTransaction);
		
		$q  = "INSERT INTO ".$this->m_table." ";
		$q .= "(imgdat,width,height,mime,product_id,priority,create_tm) ";
		$q .= "(SELECT imgdat,width,height,mime,{$new_id},priority,create_tm ";
		$q .= "FROM ".$this->m_table." ";
		$q .= "WHERE product_id={$product_id})";
		$ret = $this->Query($q, array(), false, null, MDB2_PREPARE_MANIP);
		
		if( $ret === false || $this->IsError() )
		{
			$this->Rollback($isTransaction);
			return false;	
		}
		$this->Commit($isTransaction);
		return $ret;
	}
	
	/**
	 * 商品画像を挿入する
	 * 
	 * @param array   $aInsert		必須Column配列
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return int 挿入された商品画像IDを返す
	 */
	public function Insert(&$aInsert,$isTransaction=false)
	{
		$this->Begin($isTransaction);

		if( DB_TYPE == 'pgsql')
		{
			$aInsert['imgdat'] = pg_escape_bytea($aInsert['imgdat']); 
			if( $isTransaction )
				$this->PsqlLockMyTable('SHARE UPDATE EXCLUSIVE MODE');
		}
		else
		{
			$aInsert['imgdat'] = bin2hex($aInsert['imgdat']);
			if( $isTransaction )
				$this->MysqlLockMyTable();
		}
		
			
		// 優先順位の番号を作る
		$where = "product_id = ".$aInsert['product_id'];
		$ret = $this->DB()->select("max(priority+1) as max", $this->m_table, $where );
		if( count($ret) > 0 )
		{
			if( $ret[0]['max'] != '' )
				$aInsert['priority'] = $ret[0]['max'];
		}
		$aInsert['create_tm'] = 'CURRENT_TIMESTAMP';
		$this->DataTypeCastFromColumn($aInsert);
		 
		//
		$q  = "INSERT INTO ".$this->m_table."(";
		$q .= $this->GetKeyNameConnectString($aInsert).") ";
		$q .= $this->GetValuesString($aInsert).";";
		$ret = $this->Query($q, array(), false, null, MDB2_PREPARE_MANIP);
		
		if( $ret === false || $this->IsError() )
		{
			$this->Rollback($isTransaction);
			return false;	
		}
		if( DB_TYPE  == 'pgsql')
			$ret = $this->DB()->select("lastval() as id", $this->m_table);
		else
			$ret = $this->DB()->select("last_insert_id() as id", $this->m_table);

		if( count($ret) > 0 )
		{
			$this->Commit($isTransaction);
			return intval($ret[0]['id']);
		}
		
		$this->Rollback($isTransaction);
		return false;
	}
	
	/**
	 * 複数の商品画像の順番を再割り当てする
	 * 
	 * @param int	 $product_id	 商品ID
	 * @param int	 $img_id		 対象とする商品画像ID
	 * @param int	 $newPriority	新しい順番の番号
	 * @param boolean $isTransaction  トランザクション処理をするか？
	 * @return mixi
	 */
	public function ChangePriority($product_id, $img_id, $newPriority, $isTransaction=false)
	{
		$product_id = intval($product_id);
		$this->Begin($isTransaction);
		
		// とりあえず、挿入できるようpriorityを倍増
		$q  = 'UPDATE '.$this->m_table.' SET priority = ';
		$q .= 'priority*100+10 WHERE product_id = '.$product_id;
		$ret = $this->Query($q, array(), false, null, MDB2_PREPARE_MANIP);
		if( $ret === false || $this->IsError() )
		{
			$this->Rollback($isTransaction);
			return false;
		}
		// 新しい値を作る
		$q  = 'UPDATE '.$this->m_table.' SET priority = '.(($newPriority*100+10)-1);
		$q  .= ' WHERE img_id='.$img_id;
		$ret = $this->Query($q, array(), false, null, MDB2_PREPARE_MANIP);
		if( $ret === false || $this->IsError() )
		{
			$this->Rollback($isTransaction);
			return false;
		}
		// 複数の商品画像の順番を再割り当てする
		$ret = $this->ReassignThePriority($product_id);
		if( $ret === false || $this->IsError() )
		{
			$this->Rollback($isTransaction);
			return false;
		}
		
		$this->Commit($isTransaction);
		return true;
	}
	
	/**
	 * 商品IDを変更する
	 * 
	 * @param int	 $currentID 現在の商品ID
	 * @param int	 $newID	 新しい商品ID
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return mixi
	 */
	public function ChangeProductID($currentID, $newID, $isTransaction=false)
	{
		$currentID = intval($currentID);
		$newID = intval($newID);
		
		$this->Begin($isTransaction);
		
		$q  = 'UPDATE '.$this->m_table." SET product_id = {$newID} ";
		$q .= "WHERE product_id = {$currentID}";
				  
				  
		$ret = $this->Query($q, array(), false, null, MDB2_PREPARE_MANIP);
		$this->Commit($isTransaction);
		
		return $ret;
	}
	
	/**
	 * 複数の商品画像の順番を再割り当てする
	 * 
	 * @param int	 $product_id 商品ID
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return mixi
	 */
	public function ReassignThePriority($product_id, $isTransaction=false)
	{
		$product_id = intval($product_id);
		$this->Begin($isTransaction);
		
		$q  = 'UPDATE '.$this->m_table.' SET priority = ( ';
		$q .= '  SELECT COUNT(DISTINCT T.priority) FROM ( ';
		$q .= '	SELECT product_id, priority FROM '.$this->m_table;
		$q .= "	WHERE product_id = {$product_id} ";
		$q .= '  ) AS T ';
		$q .= 'WHERE '.$this->m_table.'.product_id = T.product_id ';
		$q .= 'AND '.$this->m_table.'.priority > T.priority ) ';
				  
				  
		$ret = $this->Query($q, array(), false, null, MDB2_PREPARE_MANIP);
		$this->Commit($isTransaction);
		
		return $ret;
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
		$where = "";
		if( DB_TYPE  == 'pgsql')
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
		if( $ret === false )
		{
			$this->Rollback($isTransaction);
			return false;	
		}
		$this->Commit($isTransaction);
		return $ret;
	}
	
	/**
	 * 複数の商品画像IDが入ったものを削除する
	 * 
	 * @param array   $aImgID		 商品画像IDが入った配列
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return array
	 */
	public function DeleteFromImageIDs($aImgID, $isTransaction=false)
	{
		$this->Begin($isTransaction);
		$where = 'img_id '.$this->CreateString_xINx_FromAry($aImgID);
		$ret = $this->DB()->delete($this->m_table, $where);
		$this->Commit($isTransaction);
		
		return $ret;
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
		
		$q  = "DELETE FROM ".$this->m_table." WHERE img_id IN (SELECT img_id FROM(";
		$q .= "SELECT img_id FROM ".$this->m_table." as p ";
		$q .= "LEFT JOIN dtb_products as d ON p.product_id = d.product_id ";
		$q .= "WHERE p.product_id > 0 AND (d.del_flg = 1 OR d.product_id IS NULL))as pp)";
				  
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
		if( count($ret) > 0 )
			return $ret[0]['cnt'];
		return 0;
	}
		
	/**
	 * 商品画像を商品IDから取得
	 * 
	 * @param int $prdouct_id	  商品ID
	 * @param int $notReadImgData 画像データを含むか
	 * @return array
	 */
	public function GetFromPrdouctID($prdouct_id, $notReadImgData = true)
	{
		$prdouct_id = intval($prdouct_id);
		$col = "";
		if( $notReadImgData )
			$col = "img_id,product_id,width,height,mime,priority,create_tm";
		else
			$col = "*";
			
		$where = "product_id = ".$prdouct_id." ORDER BY priority ASC ";
		$arrColumns = $this->DB()->select($col, $this->m_table, $where);
		if( count($arrColumns) == 0 )
		{
			return array();
		}
		for($i=0;$i<count($arrColumns);++$i)
		{
			$this->DataTypeCastFromColumn($arrColumns[$i]);
			if( DB_TYPE  == 'pgsql' && !$notReadImgData)
				$arrColumns[$i]['imgdat'] = pg_unescape_bytea($arrColumns[$i]['imgdat']);
		}
		return $arrColumns;
	}
	
	/**
	 * 商品画像を商品IDと順番の番号から取得
	 * 
	 * @param int $prdouct_id	  商品ID
	 * @param int $priority		  順番
	 * @param int $notReadImgData 画像データを含むか
	 * @return array
	 */
	public function GetFromPrdouctIDAndPriority($prdouct_id, $priority, $notReadImgData = true)
	{
		$product_id = intval($product_id);
		$priority = intval($priority);
		$col = "";
		if( $notReadImgData )
			$col = "img_id,product_id,width,height,mime,priority,create_tm";
		else
			$col = "*";
			
		$where = "product_id = {$prdouct_id} AND priority = {$priority}";
		$arrColumns = $this->DB()->select($col, $this->m_table, $where);
		if( count($arrColumns) == 0 )
		{
			return array();
		}

		$this->DataTypeCastFromColumn($arrColumns[0]);
		if( DB_TYPE  == 'pgsql' && !$notReadImgData)
			$arrColumns[0]['imgdat'] = pg_unescape_bytea($arrColumns[0]['imgdat']);
			
		return $arrColumns[0];
	}
	
	/**
	 * 商品画像を商品IDと順番の番号から取得
	 * 
	 * @param int $product_id 商品ID
	 * @param int $priority	  順番
	 * @return array
	 */
	public function GetXLargerPriorityFromProductID($product_id, $priority, $notReadImgData = false)
	{
		$product_id = intval($product_id);
		$priority = intval($priority);
		
		$col = "";
		if( $notReadImgData )
			$col = "img_id,product_id,width,height,mime,priority,create_tm";
		else
			$col = "*";
			
		$where = "product_id = {$product_id} AND ";
		$where .= "priority > {$priority} ORDER BY priority";
		$arrColumns = $this->DB()->select($col, $this->m_table, $where);
		if( count($arrColumns) == 0 )
		{
			return $arrColumns;
		}
		for($i=0;$i<count($arrColumns);$i++)
		{
			if( DB_TYPE  == 'pgsql' && !$notReadImgData)
				$arrColumns[$i]['imgdat'] = pg_unescape_bytea($arrColumns[$i]['imgdat']);
		}
		
		return $arrColumns;
	}
		
	/**
	 * 商品画像を商品IDと順番の番号から取得
	 * 
	 * @param int $productImgID 商品画像ID
	 * @return array
	 */
	public function Get($img_id, $notReadImgData = false)
	{
		$col = "";
		if( $notReadImgData )
			$col = "img_id,product_id,width,height,mime,priority,create_tm";
		else
			$col = "*";
			
		$where = "img_id = ".$img_id;
		$arrColumns = $this->DB()->select($col, $this->m_table, $where);
		if( count($arrColumns) == 0 )
		{
			return array();
		}
		if( DB_TYPE  == 'pgsql' && !$notReadImgData)
			$arrColumns[0]['imgdat'] = pg_unescape_bytea($arrColumns[0]['imgdat']);
		return $arrColumns[0];
	}
	
	/**
	 * 商品画像が存在するか
	 * 
	 * @param int $productImgID 商品画像ID
	 * @return boolean
	 */
	public function IsExists($productImgID)
	{
		$where = "img_id = {$productImgID}";
		$ret = $this->DB()->select("img_id", $this->m_table, $where);
		if( count($ret) > 0 )
			return true;
		
		return false;
	}
	
	/**
	 * 商品画像ファイル数を取得
	 * 
	 * @return int
	 */
	public function GetNum()
	{
		$ret = $this->DB()->select("count(*) as cnt", $this->m_table);
		if( count($ret) > 0 )
			return $ret[0]['cnt'];
		
		return 0;
	}
	
	/**
	 * 商品IDから商品画像ファイル数を取得
	 * 
	 * @return int
	 */
	public function GetNumFromProductID($product_id)
	{
		$ret = $this->DB()->select("count(*) as cnt", $this->m_table, "product_id = {$product_id}");
		if( count($ret) > 0 )
			return intval($ret[0]['cnt']);
		
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
			if (!in_array($this->m_table, $arrTableList))
			{
				$sqlval = "";
				if( DB_TYPE  == 'pgsql')
				{
					$sqlval .= "CREATE TABLE ".$this->m_table." (";
					$sqlval .= "  img_id bigserial NOT NULL PRIMARY KEY,";
					$sqlval .= "  imgdat bytea NOT NULL,";
					$sqlval .= "  width smallint NOT NULL,";
					$sqlval .= "  height smallint NOT NULL,";
					$sqlval .= "  mime VARCHAR(16) NOT NULL,";
					$sqlval .= "  product_id int NOT NULL DEFAULT 0,";
					$sqlval .= "  priority int NOT NULL DEFAULT 0,";
					$sqlval .= "  create_tm timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP";
					$sqlval .= ");";
				}
				else
				{
					$sqlval .= "CREATE TABLE ".$this->m_table." (";
					$sqlval .= "  img_id bigint NOT NULL AUTO_INCREMENT,";
					$sqlval .= "  imgdat mediumblob NOT NULL,";
					$sqlval .= "  width smallint NOT NULL,";
					$sqlval .= "  height smallint NOT NULL,";
					$sqlval .= "  mime VARCHAR(16) NOT NULL,";
					$sqlval .= "  product_id int NOT NULL DEFAULT 0,";
					$sqlval .= "  priority int NOT NULL DEFAULT 0,";
					$sqlval .= "  create_tm timestamp NOT NULL DEFAULT 0 ,";
					$sqlval .= "  PRIMARY KEY (img_id)";
					$sqlval .= ");";
				}
				// テーブル作成
				if( !$this->DB()->exec($sqlval) )throw new Exception($this->m_table);
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