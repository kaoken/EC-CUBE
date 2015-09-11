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

/**
 * ProductImagesAddKNプラグインDB 基本クラス
 *
 * @package ProductImagesAddKN
 * @author kaoken
 * @since PHP 5.3　
 * @version 1.0
 */
abstract class plg_ProductImagesAddKN_DB_Base
{
	const TABLE_CONFIG		 = 'plg_productimagesaddkn_config';
	const TABLE_ALLOWABLE_SIZE = 'plg_productimagesaddkn_allowable_size';
	const TABLE_CASH		   = 'plg_productimagesaddkn_cash';
	const TABLE_IMG			= 'plg_productimagesaddkn_images';
	const TABLE_TMP_ID		 = 'plg_productimagesaddkn_tmp_id';

	const TYPE_BOOL   = 1;
	const TYPE_INT	= 2;
	const TYPE_FLOAT  = 3;
	const TYPE_STRING = 4;
	const TYPE_TEXT   = 5;
	const TYPE_TIME   = 6;
	const TYPE_BLOB   = 7;
	const TYPE_DUMY   = 8;

	protected $m_Vars = array();
	/**
	 * @var null|SC_Query
	 */
	protected $m_objQuery = null;
	protected $m_table = "";	// テーブル名
	protected $m_tmpAutoCommit = 0;
	protected $m_tmpTblLock = null;

	/**
	 * コンストラクタ
	 */
	public function __construct()
	{
		$this->m_objQuery = SC_Query_Ex::getSingletonInstance();
	}

	/**
	 * デスストラクタ
	 */
	public function __destruct()
	{
		if ( !is_null($this->m_tmpTblLock) )
			plg_ProductImagesAddKN_Util::getMy()->tmpFileUnlock($this->m_tmpTblLock);
	}

	/**
	 * Mysqlのオートコミットが有効か？
	 *
	 * @return bool
	 */
	public function getMysqlAutoCommit()
	{
		if ( DB_TYPE == 'mysql')
		{
			$ret = $this->db()->getAll("SELECT @@autocommit as flg");
			if ( count($ret) == 0 ) return false;
			return intval($ret[0]['flg']);
		}
		return false;
	}

	/**
	 * Mysqlのオートコミットの状態をセットする
	 *
	 * @param boolean	$isAutocommit trueで有効
	 * @return bool
	 */
	public function setMysqlAutoCommit($isAutocommit=false)
	{
		if ( DB_TYPE == 'mysql')
		{
			$ret = $this->db()->getAll("SET AUTOCOMMIT = ".($isAutocommit?'1':'0'));

			if ( $ret === false ) return false;

			return true;
		}
		return false;
	}

	/**
	 * Mysqlの自身のテーブルをロックする
	 *
	 * @param boolean   $isWrite trueでWRITE、falseでREAD
	 * @return bool
	 */
	public function mysqlLockMyTable($isWrite=true)
	{
		return $this->mysqlLockTable($this->m_table,$isWrite);
	}

	/**
	 * Mysqlの指定したテーブルをロックする
	 *
	 * @param string	$tblName ロックするテーブル名
	 * @param boolean   $isWrite trueでWRITE、falseでREAD
	 * @return bool
	 */
	public function mysqlLockTable($tblName, $isWrite=true)
	{
		if ( DB_TYPE == 'mysql')
		{
			// LOCK TABLES を使いたいが、できない環境がそこそこあるため
			// 代替案として、ファイルロックでごまかす。
			// コミット、ロールバックで、ロック解除させる。
			//---------------------------------------------------------
			// ※ポスグレはOKで、Mysql駄目っていやな感じ
//			$q = "LOCK TABLES {$tblName} ".($isWrite?'WRITE;':'READ;');
//			$c = $this->MDB2()->getConnection();
//			$ret = mysql_query($q,$c);
//			if ( $ret === false )
//			{
//				GC_Utils_Ex::gfPrintLog( "Error: SQL[{$q}]Mysqlのテーブルロックを失敗しました。\nエラー内容：ErrNo[".mysql_errno($c)."] ".mysql_error($c));
//				return false;
//			}
			$knUtil = plg_ProductImagesAddKN_Util::getMy();
			$this->m_tmpTblLock = $knUtil->tmpFileLock($tblName.'.tbl',$isWrite);
			if ( is_null($this->m_tmpTblLock) )
			{
				GC_Utils_Ex::gfPrintLog( "Error: Mysqlのテーブル'{$tblName}'ロックに失敗しました。");
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * psqlの自身のテーブルをロックする
	 *
	 * @param boolean   $isWrite trueでWRITE、falseでREAD
	 * @return bool
	 */
	public function psqlLockMyTable($mode='ACCESS EXCLUSIVE')
	{
		return $this->psqlLockTable($this->m_table,$mode);
	}

	/**
	 * psqlの指定したテーブルをロックする
	 *
	 * @param string	$tblName ロックするテーブル名
	 * @param boolean   $isWrite trueでWRITE、falseでREAD
	 * @return bool
	 */
	public function psqlLockTable($tblName, $mode='ACCESS EXCLUSIVE')
	{
		if ( DB_TYPE == 'mysql')
		{
			$ret = $this->db()->getAll("LOCK TABLES {$tblName} IN {$mode}");
			if ( $ret === false ) return false;
			return true;
		}
		return false;
	}

	/**
	 * 必須カラム配列取得
	 *
	 * @return array
	 */
	public function getRequiredColumn($aAdd=array())
	{
		$aColumn = array();
		foreach( $this->m_Vars as $key => $val )
		{

			if ( $val['required'] === true)
			{
				if ( array_key_exists($key, $aAdd) )
					$aColumn[$key] = $this->valueCastFromKey($key, $aAdd[$key]);
				else
					$aColumn[$key] = $val['value'];
			}
		}
		return $aColumn;
	}

	/**
	 * テーブルのカラムごとの設定
	 *
	 * @param  string $key フィルド名
	 * @param  int $type 型
	 * @param  mixed $value デフォルト値
	 * @param  bool  $required 必須か
	 * @param  int   $size バイト数
	 * @return string
	 */
	protected function initVar($key, $type, $value = null, $required = false, $size = null )
	{
		if ( $type > 7 || $type < self::TYPE_BOOL )
			die("plg_ProductImagesAddKN_DB_Base::initVar(),存在しないデータタイプを宣言した");
		$this->m_Vars[$key] = array(
			'data_type' => $type,
			'value' => null,
			'required' => $required ? true : false,
			'maxlength' => $size ? intval($size) : null
		);
		$this->m_Vars[$key]['value'] = self::valueCastFromKey($key,$value);
	}

	/**
	 * キーからキャストした値を返す
	 *
	 * @param  int　$type	  型
	 * @param  mixed $value	 値
	 * @return int|float|string
	 */
	public function valueCastFromKey($key, $value)
	{
		if ( !isset($value) ) $value = "";
		return self::valueCastFromDataType($this->m_Vars[$key]['data_type'],$value,$this->m_Vars[$key]['maxlength']);
	}

	/**
	 * データの型からキャスとしたデーターを返す
	 *
	 * @param  int　$type	  型
	 * @param  mixed $value	 値
	 * @param  int　$maxlength 長さ
	 * @return int|float|string
	 */
	protected static function valueCastFromDataType($type, $value, $maxlength)
	{
		$ret = null;
		switch( $type )
		{
			case self::TYPE_BOOL:
				$ret = $value ? 1:0;
				break;
			case self::TYPE_INT:
				$ret = is_null($value) ? null:intval($value);
				break;
			case self::TYPE_FLOAT:
				$ret = is_null($value) ? null:floatval($value);
				break;
			case self::TYPE_STRING:
				if ( !is_null($maxlength) && strlen($value) > $maxlength )
					$ret = mb_substr($value, 0, $maxlength);
			case self::TYPE_TEXT:
			case self::TYPE_TIME:
			case self::TYPE_BLOB:
				$ret = $value."";
		}
		return $ret;
	}

	/**
	 * データの型からキャスとしたデーター配列を返す
	 *
	 * @param  array　$aCol
	 * @return void
	 */
	public function dataTypeCastFromColumn(&$aCol)
	{
		foreach( $this->m_Vars as $key => $val )
		{
			if ( array_key_exists($key, $aCol) )
			{
				$aCol[$key] = $this->valueCastFromKey($key,$aCol[$key]);
			}
			else if ( $val['required'] === true )
			{
				$aCol[$key] = $val['value'];
			}
		}
	}

	/**
	 * 配列からキーを区切りとした文字列を返す　例："(col1,col2..."
	 *
	 * @param  array　$aCol
	 * @return string
	 */
	protected function getKeyNameConnectString($aCol)
	{
		$cnt=0;
		$ret = '';
		foreach( $aCol as $key => $val)
		{
			$ret .=  $cnt>0?',':'';
			$ret .= $key;
			++$cnt;
		}
		return $ret;
	}

	/**
	 * 配列から値をと型から区切りとした文字列を返す　例："(0,'hoge',3.14, ..."
	 *
	 * @param  array　$aCol
	 * @return string
	 */
	protected function getValuesString($aCol)
	{
		$cnt=0;
		$ret = "VALUES(";
		foreach( $this->m_Vars as $key => $val )
		{
			if ( array_key_exists($key, $aCol) )
			{
				$ret .=  $cnt>0?',':'';
				$value = $aCol[$key];
				switch( $val['data_type'] )
				{
					case self::TYPE_BOOL:
						$ret .=  $value ? '1':'0';
						break;
					case self::TYPE_INT:
						$ret .= intval($value)."";
						break;
					case self::TYPE_FLOAT:
						$ret .= floatval($value)."";
						break;
					case self::TYPE_STRING:
						if ( $val['maxlength'] != null && strlen($value) > $val['maxlength'] )
							$ret .= "'".mb_substr($value, 0, $val['maxlength'])."'";
						else
							$ret .= "'{$value}'";
						break;
					case self::TYPE_TEXT:
						$ret .= "'{$value}'";
						break;
					case self::TYPE_TIME:
						$ret .= "{$value}";
						break;
					case self::TYPE_BLOB:
						if ( DB_TYPE == 'pgsql')
							$ret .= "'{$value}'";
						else
							$ret .= "X'{$value}'";
				}
				++$cnt;
			}
		}
		$ret.= ')';
		return $ret;
	}
	/**
	 * テーブル名を返す
	 *
	 * @return string
	 */
	public function getTableName(){return $this->m_table;}

	/**
	 * テーブルのサイズを取得する
	 *
	 * @return int
	 */
	public function getTableSize()
	{
		$ret = array();
		if ( DB_TYPE  == 'pgsql')
			$ret = $this->db()->select("pg_size_pretty(pg_total_relation_size('".$this->m_table."')) as size;");
		if ( count($ret) > 0 )
			return $ret[0]['size'];
		else
		{
			$ret = $this->db()->select('(data_length+index_length) as size','information_schema.tables',"table_name='".$this->m_table."'");
			$knUtil = plg_ProductImagesAddKN_Util::getMy();
			return $knUtil->getFileSizePrefix($ret[0]['size']);
		}
		return 'Err Byte';
	}
	/**
	 * SC_Query_Exを返す
	 *
	 * @return SC_Query_Ex
	 */
	public function db(){ return $this->m_objQuery; }
	/**
	 * MDB2を返す
	 *
	 * @return MDB2
	 */
	public function MDB2(){ return $this->m_objQuery->conn; }


	/**
	 * Begin Mysqlの場合autocommitを無効にする
	 *
	 * @param  boolean　$isTransaction トランザクション処理をするか
	 * @return void
	 */
	public function begin($isTransaction=true)
	{
		if ($isTransaction)
		{
			$this->m_tmpAutoCommit = $this->getMysqlAutoCommit();
			$this->setMysqlAutoCommit(false);
			if ( !is_null($this->m_tmpTblLock) )
				plg_ProductImagesAddKN_Util::getMy()->tmpFileUnlock($this->m_tmpTblLock);
			$this->m_objQuery->begin();
		}
	}

	/**
	 * commit Mysqlの場合autocommitを元の状態に戻す
	 *
	 * @param  boolean　$isTransaction トランザクション処理をするか
	 * @return void
	 */
	public function commit($isTransaction=true)
	{
		if ($isTransaction)
		{
			$this->m_objQuery->commit();
			if ( $this->m_tmpAutoCommit != 0 )
				$this->setMysqlAutoCommit(true);
			if ( !is_null($this->m_tmpTblLock) )
				plg_ProductImagesAddKN_Util::getMy()->tmpFileUnlock($this->m_tmpTblLock);
		}
	}

	/**
	 * rollback Mysqlの場合autocommitを元の状態に戻す
	 *
	 * @param  boolean　$isTransaction トランザクション処理をするか
	 * @return void
	 */
	public function rollback($isTransaction=true)
	{
		if ($isTransaction)
		{
			$this->m_objQuery->rollback();
			if ( $this->m_tmpAutoCommit != 0 )
				$this->setMysqlAutoCommit(true);
			if ( !is_null($this->m_tmpTblLock) )
				plg_ProductImagesAddKN_Util::getMy()->tmpFileUnlock($this->m_tmpTblLock);
		}
	}

	/**
	 * SQL を実行する.
	 *
	 * FIXME $ignore_errが無視されるようになっているが互換性として問題が無いか確認が必要
	 *
	 * @param  string  $n			実行する SQL 文
	 * @param  array   $arr		  プレースホルダに挿入する値
	 * @param  boolean $ignore_err   MDB2切替で無効化されている (エラーが発生しても処理を続行する場合 true)
	 * @param  mixed   $types		プレースホルダの型指定 デフォルトnull = string
	 * @param  mixed   $result_types 返値の型指定またはDML実行(MDB2_PREPARE_MANIP)
	 * @return array   SQL の実行結果の配列
	 */
	public function query($n, $arr = array(), $ignore_err = false, $types = null, $result_types = MDB2_PREPARE_RESULT)
	{
		return $this->db()->query($n , $arr, $ignore_err, $types, $result_types);
	}

	/**
	 * エラーか？
	 *
	 * @return bool
	 */
	public function isError(){ return $this->m_objQuery->isError(); }

	/**
	 * OR 文字列を作る hoge=0 OR hoge 2 ...
	 *
	 * @param  array $ary 数値または文字列の配列
	 * @return string
	 */
	protected function createString_xORx_FromAry($item, $ary)
	{
		$cnt = 0;
		$ret = '';
		foreach($ary as $val )
		{
			if ( $cnt > 0 ) $ret .= 'OR ';
			$ret .= $item.'=';
			if ( is_numeric($val) )
				$ret .= $val.' ';
			else if ( is_string($val) )
				$ret .= "'{$val}' ";
			++$cnt;
		}
		return $ret;
	}

	/**
	 * AND 文字列を作る hoge=0 AND hoge 2 ...
	 *
	 * @param  array $ary 数値または文字列の配列
	 * @return string
	 */
	protected function createString_xANDx_FromAry($item, $ary)
	{
		$cnt = 0;
		$ret = '';
		foreach($ary as $val )
		{
			if ( $cnt > 0 ) $ret .= 'AND ';
			$ret .= $item.'=';
			if ( is_numeric($val) )
				$ret .= $val.' ';
			else if ( is_string($val) )
				$ret .= "'{$val}' ";
			++$cnt;
		}
		return $ret;
	}

	/**
	 * 検索値の列挙文字列を作る IN (0,1,3,...)
	 *
	 * @param  array $ary 数値または文字列の配列
	 * @return string
	 */
	protected function createString_xINx_FromAry($ary)
	{
		$cnt = 0;
		$ret = 'IN (';
		foreach($ary as $val )
		{
			if ( $cnt > 0 ) $ret .= ',';

			if ( is_numeric($val) )
				$ret .= $val.' ';
			else if ( is_string($val) )
				$ret .= "'{$val}' ";
			++$cnt;
		}
		return $ret.')';
	}
	/*
		public function myLock()
		{
			if ( DB_TYPE == 'pgsql')
			{
				$this->db()->query('LOCK '.$this->m_table.' IN SHARE UPDATE EXCLUSIVE MODE');
			}
			else
			{
				$this->db()->query('SELECT '.$this->m_table.'FROM FOR UPDATE');
				$addWhere = " FOR UPDATE";
			}
		}*/

	/**
	 * テーブルを削除
	 *
	 * @param  string $table テーブル名
	 * @return boolean 削除に成功した場合true
	 */
	protected function dropTable($table)
	{
		$this->db()->exec("DROP TABLE {$table};");
		if ( $this->db()->isError() )
		{
			SC_Utils_Ex::sfDispSiteError(FREE_ERROR_MSG, '', false,"テーブル'".$table . "' 削除に失敗しました。");
			return false;
		}
		return true;
	}

	/**
	 * インストール
	 * installはプラグインのインストール時に実行されるようにしてください。
	 * 引数にはdtb_pluginのプラグイン情報が渡されます.
	 *
	 * @param  array $arrPlugin plugin_infoを元にDBに登録されたプラグイン情報(dtb_plugin)
	 * @param  array $arrTableList 存在するテーブル名の配列
	 * @return void
	 */
	abstract public function install($arrPlugin, $arrTableList);

	/**
	 * アンインストール
	 * uninstallはアンインストール時に実行されるようにしてください。
	 * 引数にはdtb_pluginのプラグイン情報が渡されます.
	 *
	 * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
	 * @param  array $arrTableList 存在するテーブル名の配列
	 * @return boolean
	 */
	public function uninstall($arrPlugin, $arrTableList)
	{
		if (in_array($this->m_table, $arrTableList))
			return $this->dropTable($this->m_table);
		return true;
	}
}
