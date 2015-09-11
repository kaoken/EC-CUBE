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

require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/db/plg_ProductImagesAddKN_DB_Base.php';

/**
 * ProductImagesAddKNプラグイン 一時ID作成関連のDBクラス
 *
 * @package ProductImagesAddKN
 * @author kaoken
 * @since PHP 5.3　
 * @version 1.0
 */
class plg_ProductImagesAddKN_DB_TmpID extends plg_ProductImagesAddKN_DB_Base
{
	const DELETE_TIME = 24;
	/**
	 * コンストラクタ
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		$this->m_table = self::TABLE_TMP_ID;

		$this->initVar('tmp_id', self::TYPE_INT, 0, true);
		$this->initVar('mode', self::TYPE_STRING, '', true, 32);
		$this->initVar('expiry_date', self::TYPE_TIME, 'CURRENT_TIMESTAMP', true);
		$this->initVar('create_tm', self::TYPE_TIME, 'CURRENT_TIMESTAMP', false);
	}


	/**
	 * 一時キーを削除する
	 * 有効期限が切れたRecordも削除する
	 *
	 * @param boolean $id			一時ID
	 * @param string  $mode		  キータイプの名前
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return bool 成功した場合はtrueを返す
	 */
	public function delete($id, $mode, $isTransaction=false)
	{
		$this->begin($isTransaction);
		$where = "mode = '{$mode}' AND tmp_id = {$id}";
		$ret = $this->db()->delete($this->m_table, $where );

		if ( $ret === false || $this->isError())
		{
			$this->rollback($isTransaction);
			return false;
		}
		$this->commit($isTransaction);
		return true;
	}

	/**
	 * 一時キーを作成する
	 * 有効期限が切れたRecordも削除する
	 *
	 * @param string $mode キータイプの名前
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return int
	 */
	public function getCreateID($mode, $isTransaction=false)
	{
		$this->begin();
		$id = 0;
		// ロック
		if ( DB_TYPE == 'pgsql')
			$this->psqlLockMyTable('SHARE UPDATE EXCLUSIVE MODE');
		else if ( $isTransaction )
			$this->mysqlLockMyTable();

		// このタイミングで古いIDを削除
		if ( !$this->deleteExpiryDateRecord() )
		{
			$this->rollback();
			return 0;
		}

		for($i=0;$i<100;$i++)
		{
			$id = rand(-2147483648, -1);
			$where = "mode = '{$mode}' AND tmp_id = {$id}";
			$arrColumns = $this->db()->select('*', $this->m_table, $where);
			if ( count($arrColumns) == 0 )
			{
				$aInsert['tmp_id'] = $id;
				$aInsert['mode'] = $mode;
				if ( DB_TYPE  == 'pgsql')
					$aInsert['expiry_date'] = "current_timestamp + '".self::DELETE_TIME." HOUR'";
				else
					$aInsert['expiry_date'] = 'date_add(now(),interval '.self::DELETE_TIME.' HOUR)';

				$q  = "INSERT INTO ".$this->m_table."(";
				$q .= $this->getKeyNameConnectString($aInsert).") ";
				$q .= $this->getValuesString($aInsert).";";

				$ret = $this->db()->query($q, array(), false, null, MDB2_PREPARE_MANIP);
				if ( $ret === false || $this->isError())
				{
					$this->rollback();
					return 0;
				}
				break;
			}
		}

		$this->commit();
		return $id;
	}

	/**
	 * 有効期限が切れたRecordを削除する
	 *
	 * @param boolean $isTransaction トランザクション処理をするか？
	 * @return void
	 */
	public function deleteExpiryDateRecord($isTransaction=false)
	{
		$this->begin($isTransaction);
		$where = "expiry_date <= now()";
		$ret = $this->db()->delete($this->m_table, $where );

		if ( $ret === false || $this->isError())
		{
			$this->rollback($isTransaction);
			return false;
		}
		$this->commit($isTransaction);
		return true;
	}

	/**
	 * インストール
	 * Installはプラグインのインストール時に実行されるようにしてください。
	 * 引数にはdtb_pluginのプラグイン情報が渡されます.
	 *
	 * @param  array $arrPlugin plugin_infoを元にDBに登録されたプラグイン情報(dtb_plugin)
	 * @param  array $arrTableList 存在するテーブル名の配列
	 * @return bool 失敗した場合 falseを返す。
	 */
	public function install($arrPlugin, $arrTableList)
	{
		try {
			// プラグイン設定テーブル
			$sqlval = "";
			if (!in_array($this->m_table, $arrTableList))
			{
				if ( DB_TYPE  == 'pgsql')
				{
					$sqlval .= "CREATE TABLE ".$this->m_table." (";
					$sqlval .= "  tmp_id int NOT NULL DEFAULT 0,";
					$sqlval .= "  mode varchar(32) NOT NULL DEFAULT '',";
					$sqlval .= "  expiry_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,";
					$sqlval .= "  create_tm timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP";
					$sqlval .= ");";
				}
				else
				{
					$sqlval .= "CREATE TABLE ".$this->m_table." (";
					$sqlval .= "  tmp_id int NOT NULL DEFAULT 0,";
					$sqlval .= "  mode varchar(32) NOT NULL DEFAULT '',";
					$sqlval .= "  expiry_date timestamp NOT NULL DEFAULT 0,";
					$sqlval .= "  create_tm timestamp NOT NULL DEFAULT 0";
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
