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
 * ProductImagesAddKNプラグイン 商品イメージ関連のDBクラス
 *
 * @package ProductImagesAddKN
 * @author kaoken
 * @since PHP 5.3　
 * @version 1.0
 */
class plg_ProductImagesAddKN_DB_ProductImg extends plg_ProductImagesAddKN_DB_Base
{
	/**
	 * コンストラクタ
	 */
	public function __construct()
	{
		parent::__construct();
		$this->m_table = self::TABLE_IMG;

		$this->initVar('img_id', self::TYPE_INT, 0, false);
		$this->initVar('imgdat', self::TYPE_BLOB, 0, true);
		$this->initVar('width', self::TYPE_INT, 0, true);
		$this->initVar('height', self::TYPE_INT, 0, true);
		$this->initVar('mime', self::TYPE_STRING, '', true, 16);
		$this->initVar('product_id', self::TYPE_INT, 0, true);
		$this->initVar('priority', self::TYPE_INT, 0, true);
		$this->initVar('create_tm', self::TYPE_TIME, 'CURRENT_TIMESTAMP', false);
	}

	/**
	 * 指定した商品IDの画像を新たな商品IDで画像を複製する。
	 *
	 * @param int	 $product_id	コピー元の商品ID
	 * @param int	 $new_id		コピー後の新たな商品ID
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return int
	 */
	public function cloneFormProductID($product_id, $new_id,$isTransaction=false)
	{
		$this->begin($isTransaction);

		$q  = "INSERT INTO ".$this->m_table." ";
		$q .= "(imgdat,width,height,mime,product_id,priority,create_tm) ";
		$q .= "(SELECT imgdat,width,height,mime,{$new_id},priority,create_tm ";
		$q .= "FROM ".$this->m_table." ";
		$q .= "WHERE product_id={$product_id})";
		$ret = $this->query($q, array(), false, null, MDB2_PREPARE_MANIP);

		if ( $ret === false || $this->isError() )
		{
			$this->rollback($isTransaction);
			return false;
		}
		$this->commit($isTransaction);
		return $ret;
	}

	/**
	 * 商品画像を挿入する
	 *
	 * @param array   $aInsert		必須Column配列
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return int 挿入された商品画像IDを返す
	 */
	public function insert(&$aInsert,$isTransaction=false)
	{
		$this->begin($isTransaction);

		if ( DB_TYPE == 'pgsql')
		{
			$aInsert['imgdat'] = pg_escape_bytea($aInsert['imgdat']);
			if ( $isTransaction )
				$this->psqlLockMyTable('SHARE UPDATE EXCLUSIVE MODE');
		}
		else
		{
			$aInsert['imgdat'] = bin2hex($aInsert['imgdat']);
			if ( $isTransaction )
				$this->mysqlLockMyTable();
		}


		// 優先順位の番号を作る
		$where = "product_id = ".$aInsert['product_id'];
		$ret = $this->db()->select("max(priority+1) as max", $this->m_table, $where );
		if ( count($ret) > 0 )
		{
			if ( $ret[0]['max'] != '' )
				$aInsert['priority'] = $ret[0]['max'];
		}
		$aInsert['create_tm'] = 'CURRENT_TIMESTAMP';
		$this->dataTypeCastFromColumn($aInsert);

		//
		$q  = "INSERT INTO ".$this->m_table."(";
		$q .= $this->getKeyNameConnectString($aInsert).") ";
		$q .= $this->getValuesString($aInsert).";";
		$ret = $this->query($q, array(), false, null, MDB2_PREPARE_MANIP);

		if ( $ret === false || $this->isError() )
		{
			$this->rollback($isTransaction);
			return false;
		}
		if ( DB_TYPE  == 'pgsql')
			$ret = $this->db()->select("lastval() as id", $this->m_table);
		else
			$ret = $this->db()->select("last_insert_id() as id", $this->m_table);

		if ( count($ret) > 0 )
		{
			$this->commit($isTransaction);
			return intval($ret[0]['id']);
		}

		$this->rollback($isTransaction);
		return false;
	}

	/**
	 * 複数の商品画像の順番を再割り当てする
	 *
	 * @param int	 $product_id	 商品ID
	 * @param int	 $img_id		 対象とする商品画像ID
	 * @param int	 $newPriority	新しい順番の番号
	 * @param boolean $isTransaction  トランザクション処理をするか？
	 * @return mixed
	 */
	public function changePriority($product_id, $img_id, $newPriority, $isTransaction=false)
	{
		$product_id = intval($product_id);
		$this->begin($isTransaction);

		// とりあえず、挿入できるようpriorityを倍増
		$q  = 'UPDATE '.$this->m_table.' SET priority = ';
		$q .= 'priority*100+10 WHERE product_id = '.$product_id;
		$ret = $this->query($q, array(), false, null, MDB2_PREPARE_MANIP);
		if ( $ret === false || $this->isError() )
		{
			$this->rollback($isTransaction);
			return false;
		}
		// 新しい値を作る
		$q  = 'UPDATE '.$this->m_table.' SET priority = '.(($newPriority*100+10)-1);
		$q  .= ' WHERE img_id='.$img_id;
		$ret = $this->query($q, array(), false, null, MDB2_PREPARE_MANIP);
		if ( $ret === false || $this->isError() )
		{
			$this->rollback($isTransaction);
			return false;
		}
		// 複数の商品画像の順番を再割り当てする
		$ret = $this->reassignThePriority($product_id);
		if ( $ret === false || $this->isError() )
		{
			$this->rollback($isTransaction);
			return false;
		}

		$this->commit($isTransaction);
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

		$this->begin($isTransaction);

		$q  = 'UPDATE '.$this->m_table." SET product_id = {$newID} ";
		$q .= "WHERE product_id = {$currentID}";


		$ret = $this->query($q, array(), false, null, MDB2_PREPARE_MANIP);
		$this->commit($isTransaction);

		return $ret;
	}

	/**
	 * 複数の商品画像の順番を再割り当てする
	 *
	 * @param int	 $product_id 商品ID
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return mixed
	 */
	public function reassignThePriority($product_id, $isTransaction=false)
	{
		$product_id = intval($product_id);
		$this->begin($isTransaction);

		$q  = 'UPDATE '.$this->m_table.' SET priority = ( ';
		$q .= '  SELECT COUNT(DISTINCT T.priority) FROM ( ';
		$q .= '	SELECT product_id, priority FROM '.$this->m_table;
		$q .= "	WHERE product_id = {$product_id} ";
		$q .= '  ) AS T ';
		$q .= 'WHERE '.$this->m_table.'.product_id = T.product_id ';
		$q .= 'AND '.$this->m_table.'.priority > T.priority ) ';


		$ret = $this->query($q, array(), false, null, MDB2_PREPARE_MANIP);
		$this->commit($isTransaction);

		return $ret;
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
	 * 複数の商品画像IDが入ったものを削除する
	 *
	 * @param array   $aImgID		 商品画像IDが入った配列
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return array
	 */
	public function deleteFromImageIDs($aImgID, $isTransaction=false)
	{
		$this->begin($isTransaction);
		$where = 'img_id '.$this->createString_xINx_FromAry($aImgID);
		$ret = $this->db()->delete($this->m_table, $where);
		$this->commit($isTransaction);

		return $ret;
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

		$q  = "DELETE FROM ".$this->m_table." WHERE img_id IN (SELECT img_id FROM(";
		$q .= "SELECT img_id FROM ".$this->m_table." as p ";
		$q .= "LEFT JOIN dtb_products as d ON p.product_id = d.product_id ";
		$q .= "WHERE p.product_id > 0 AND (d.del_flg = 1 OR d.product_id IS NULL))as pp)";

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
	 * 商品画像を商品IDから取得
	 *
	 * @param int $product_id	  商品ID
	 * @param bool $notReadImgData 画像データを含むか
	 * @return array
	 */
	public function getFromProductID($product_id, $notReadImgData = true)
	{
		$product_id = intval($product_id);
		$col = "";
		if ( $notReadImgData )
			$col = "img_id,product_id,width,height,mime,priority,create_tm";
		else
			$col = "*";

		$where = "product_id = ".$product_id." ORDER BY priority ASC ";
		$arrColumns = $this->db()->select($col, $this->m_table, $where);
		if ( count($arrColumns) == 0 )
		{
			return array();
		}
		for($i=0;$i<count($arrColumns);++$i)
		{
			$this->dataTypeCastFromColumn($arrColumns[$i]);
			if ( DB_TYPE  == 'pgsql' && !$notReadImgData)
				$arrColumns[$i]['imgdat'] = pg_unescape_bytea($arrColumns[$i]['imgdat']);
		}
		return $arrColumns;
	}

	/**
	 * 商品画像を商品IDと順番の番号から取得
	 *
	 * @param int  $product_id	   商品ID
	 * @param int  $priority       順番
	 * @param bool $notReadImgData 画像データを含むか
	 * @return array
	 */
	public function getFromProductIDAndPriority($product_id, $priority, $notReadImgData = true)
	{
		$product_id = intval($product_id);
		$priority = intval($priority);
		$col = "";
		if ( $notReadImgData )
			$col = "img_id,product_id,width,height,mime,priority,create_tm";
		else
			$col = "*";

		$where = "product_id = {$product_id} AND priority = {$priority}";
		$arrColumns = $this->db()->select($col, $this->m_table, $where);
		if ( count($arrColumns) == 0 )
		{
			return array();
		}

		$this->dataTypeCastFromColumn($arrColumns[0]);
		if ( DB_TYPE  == 'pgsql' && !$notReadImgData)
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
	public function getXLargerPriorityFromProductID($product_id, $priority, $notReadImgData = false)
	{
		$product_id = intval($product_id);
		$priority = intval($priority);

		$col = "";
		if ( $notReadImgData )
			$col = "img_id,product_id,width,height,mime,priority,create_tm";
		else
			$col = "*";

		$where = "product_id = {$product_id} AND ";
		$where .= "priority > {$priority} ORDER BY priority";
		$arrColumns = $this->db()->select($col, $this->m_table, $where);
		if ( count($arrColumns) == 0 )
		{
			return $arrColumns;
		}
		for($i=0;$i<count($arrColumns);$i++)
		{
			if ( DB_TYPE  == 'pgsql' && !$notReadImgData)
				$arrColumns[$i]['imgdat'] = pg_unescape_bytea($arrColumns[$i]['imgdat']);
		}

		return $arrColumns;
	}

	/**
	 * 商品画像を商品IDと順番の番号から取得
	 *
	 * @param int  $img_id         商品画像ID
	 * @param bool $notReadImgData 画像データを含むか
	 * @return array
	 */
	public function get($img_id, $notReadImgData = false)
	{
		$col = "";
		if ( $notReadImgData )
			$col = "img_id,product_id,width,height,mime,priority,create_tm";
		else
			$col = "*";

		$where = "img_id = ".$img_id;
		$arrColumns = $this->db()->select($col, $this->m_table, $where);
		if ( count($arrColumns) == 0 )
		{
			return array();
		}
		if ( DB_TYPE  == 'pgsql' && !$notReadImgData)
			$arrColumns[0]['imgdat'] = pg_unescape_bytea($arrColumns[0]['imgdat']);
		return $arrColumns[0];
	}

	/**
	 * 商品画像が存在するか
	 *
	 * @param int $productImgID 商品画像ID
	 * @return boolean
	 */
	public function isExists($productImgID)
	{
		$where = "img_id = {$productImgID}";
		$ret = $this->db()->select("img_id", $this->m_table, $where);
		if ( count($ret) > 0 )
			return true;

		return false;
	}

	/**
	 * 商品画像ファイル数を取得
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
	 * 商品IDから商品画像ファイル数を取得
	 *
	 * @return int
	 */
	public function getNumFromProductID($product_id)
	{
		$ret = $this->db()->select("count(*) as cnt", $this->m_table, "product_id = {$product_id}");
		if ( count($ret) > 0 )
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
	 * @return bool 失敗した場合 falseを返す
	 */
	public function install($arrPlugin, $arrTableList)
	{
		try {
			if (!in_array($this->m_table, $arrTableList))
			{
				$sqlval = "";
				if ( DB_TYPE  == 'pgsql')
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
