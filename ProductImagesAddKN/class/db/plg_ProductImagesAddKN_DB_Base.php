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
 
/**
* ProductImagesAddKNプラグインDB 基本クラス
*
* @package ProductImagesAddKN
* @author kaoken
* @since PHP 5.3　
* @version 0.1
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
	protected $m_objQuery = null;
	protected $m_table = "";	// テーブル名
	protected $m_tmpAuttoCommit = 0;
	protected $m_tmpTblLock = null;

	/**
	 * コンストラクタ
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->m_objQuery = SC_Query_Ex::getSingletonInstance();			
	}

	/**
	 * デスストラクタ
	 *
	 * @return void
	 */
	public function __destruct()
	{
		if( !is_null($this->m_tmpTblLock) )
			plg_ProductImagesAddKN_Util::GetMy()->TmpFileUnlock($this->m_tmpTblLock);
	}
	
	/**
	 * Mysqlのオートコミットが有効か？
	 * 
	 * @return bool
	 */
	public function GetMysqlAutoCommit()
	{
		if( DB_TYPE == 'mysql')
		{
			$ret = $this->DB()->getAll("SELECT @@autocommit as flg");
			if( count($ret) == 0 ) return false;
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
	public function SetMysqlAutoCommit($isAutocommit=false)
	{
		if( DB_TYPE == 'mysql')
		{
			$ret = $this->DB()->getAll("SET AUTOCOMMIT = ".($isAutocommit?'1':'0'));
			
			if( $ret === false ) return false;
						
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
	public function MysqlLockMyTable($isWrite=true)
	{
		return $this->MysqlLockTable($this->m_table,$isWrite);
	}	
	
	/**
	 * Mysqlの指定したテーブルをロックする
	 * 
	 * @param string	$tblName ロックするテーブル名
	 * @param boolean   $isWrite trueでWRITE、falseでREAD
	 * @return bool
	 */
	public function MysqlLockTable($tblName, $isWrite=true)
	{
		if( DB_TYPE == 'mysql')
		{
			// LOCK TABLES を使いたいが、できない環境がそこそこあるため
			// 代替案として、ファイルロックでごまかす。
			// コミット、ロールバックで、ロック解除させる。
			//---------------------------------------------------------
			// ※ポスグレはOKで、Mysql駄目っていやな感じ
//			$q = "LOCK TABLES {$tblName} ".($isWrite?'WRITE;':'READ;');
//			$c = $this->MDB2()->getConnection();
//			$ret = mysql_query($q,$c);
//			if( $ret === false )
//			{
//				GC_Utils_Ex::gfPrintLog( "Error: SQL[{$q}]Mysqlのテーブルロックを失敗しました。\nエラー内容：ErrNo[".mysql_errno($c)."] ".mysql_error($c));
//				return false;
//			}
			$knUtil = plg_ProductImagesAddKN_Util::GetMy();
			$this->m_tmpTblLock = $knUtil->TmpFileLock($tblName.'.tbl',$isWrite);
			if( is_null($this->m_tmpTblLock) )
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
	public function PsqlLockMyTable($mode='ACCESS EXCLUSIVE')
	{
		return $this->PsqlLockTable($this->m_table,$mode);
	}	
	
	/**
	 * psqlの指定したテーブルをロックする
	 * 
	 * @param string	$tblName ロックするテーブル名
	 * @param boolean   $isWrite trueでWRITE、falseでREAD
	 * @return bool
	 */
	public function PsqlLockTable($tblName, $mode='ACCESS EXCLUSIVE')
	{
		if( DB_TYPE == 'mysql')
		{
			$ret = $this->DB()->getAll("LOCK TABLES {$tblName} IN {$mode}");
			if( $ret === false ) return false;
			return true;
		}
		return false;
	}	

	/**
	 * 必須カラム配列取得
	 * 
	 * @return array
	 */
	public function GetRequiredColumn($aAdd=array())
	{
		$aColumn = array();
		foreach( $this->m_Vars as $key => $val )
		{
			
			if( $val['required'] === true)
			{
				if( array_key_exists($key, $aAdd) )
					$aColumn[$key] = $this->ValueCastFromKey($key, $aAdd[$key]);	
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
	 * @param  mixi $value デフォルト値
	 * @param  int $required 必須か
	 * @param  int $size バイト数
	 * @return string
	 */
	protected function InitVar($key, $type, $value = null, $required = false, $size = null )
	{
		if( $type > 7 || $type < self::TYPE_BOOL )
			die("plg_ProductImagesAddKN_DB_Base::InitVar(),存在しないデータタイプを宣言した");
		$this->m_Vars[$key] = array(
			'data_type' => $type,
			'value' => null,
			'required' => $required ? true : false,
			'maxlength' => $size ? intval($size) : null
		);
		$this->m_Vars[$key]['value'] = self::ValueCastFromKey($key,$value);
	}

	/**
	 * キーからキャストした値を返す
	 * 
	 * @param  int　$type	  型
	 * @param  mixi $value	 値
	 * @return int|float|string
	 */
	public function ValueCastFromKey($key, $value)
	{
		if( !isset($value) ) $value = "";
		return self::ValueCastFromDataType($this->m_Vars[$key]['data_type'],$value,$this->m_Vars[$key]['maxlength']);	
	}
	
	/**
	 * データの型からキャスとしたデーターを返す
	 * 
	 * @param  int　$type	  型
	 * @param  mixi $value	 値
	 * @param  int　$maxlength 長さ
	 * @return int|float|string
	 */
	protected static function ValueCastFromDataType($type, $value, $maxlength)
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
				if( !is_null($maxlength) && strlen($value) > $maxlength )
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
	public function DataTypeCastFromColumn(&$aCol)
	{
		foreach( $this->m_Vars as $key => $val )
		{
			if( array_key_exists($key, $aCol) )
			{
				$aCol[$key] = $this->ValueCastFromKey($key,$aCol[$key]);	
			}
			else if( $val['required'] === true )
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
	protected function GetKeyNameConnectString($aCol)
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
	protected function GetValuesString($aCol)
	{
		$cnt=0;
		$ret = "VALUES(";
		foreach( $this->m_Vars as $key => $val )
		{
			if( array_key_exists($key, $aCol) )
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
						if( $val['maxlength'] != null && strlen($value) > $val['maxlength'] )
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
						if( DB_TYPE == 'pgsql')
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
	public function GetTableName(){return $this->m_table;}
	
	/**
	 * テーブルのサイズを取得する
	 * 
	 * @return int
	 */
	public function GetTableSize()
	{
		$ret = array();
		if( DB_TYPE  == 'pgsql')
			$ret = $this->DB()->select("pg_size_pretty(pg_total_relation_size('".$this->m_table."')) as size;");
			if( count($ret) > 0 )
				return $ret[0]['size'];
		else
		{
			$ret = $this->DB()->select('(data_length+index_length) as size','information_schema.tables',"table_name='".$this->m_table."'");
			$knUtil = plg_ProductImagesAddKN_Util::GetMy();
			return $knUtil->GetFileSizePrefix($ret[0]['size']);
		}
		return 'Err Byte';
	}
	/**
	 * SC_Query_Exを返す
	 * 
	 * @return SC_Query_Ex
	 */
	public function DB(){ return $this->m_objQuery; }
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
	public function Begin($isTransaction=true)
	{
		if($isTransaction)
		{
			$this->m_tmpAuttoCommit = $this->GetMysqlAutoCommit();
			$this->SetMysqlAutoCommit(false);
			if( !is_null($this->m_tmpTblLock) )
				plg_ProductImagesAddKN_Util::GetMy()->TmpFileUnlock($this->m_tmpTblLock);
			$this->m_objQuery->begin();
		}
	}
	
	/**
	 * commit Mysqlの場合autocommitを元の状態に戻す
	 * 
	 * @param  boolean　$isTransaction トランザクション処理をするか	  
	 * @return void
	 */
	public function Commit($isTransaction=true)
	{
		if($isTransaction)
		{
			$this->m_objQuery->commit();
			if( $this->m_tmpAuttoCommit != 0 )
				$this->SetMysqlAutoCommit(true);
			if( !is_null($this->m_tmpTblLock) )
				plg_ProductImagesAddKN_Util::GetMy()->TmpFileUnlock($this->m_tmpTblLock);
		}
	}
	
	/**
	 * rollback Mysqlの場合autocommitを元の状態に戻す
	 * 
	 * @param  boolean　$isTransaction トランザクション処理をするか	  
	 * @return void
	 */
	public function Rollback($isTransaction=true)
	{
		if($isTransaction)
		{
			$this->m_objQuery->rollback();
			if( $this->m_tmpAuttoCommit != 0 )
				$this->SetMysqlAutoCommit(true);
			if( !is_null($this->m_tmpTblLock) )
				plg_ProductImagesAddKN_Util::GetMy()->TmpFileUnlock($this->m_tmpTblLock);
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
	public function Query($n, $arr = array(), $ignore_err = false, $types = null, $result_types = MDB2_PREPARE_RESULT)
	{
		return $this->DB()->query($n , $arr, $ignore_err, $types, $result_types);
	}
	
	/**
	 * エラーか？
	 * 
	 * @return bool
	 */
	public function IsError(){ return $this->m_objQuery->isError(); }

	/**
	 * OR 文字列を作る hoge=0 OR hoge 2 ...
	 *
	 * @param  array $ary 数値または文字列の配列
	 * @return string 
	 */
	protected function CreateString_xORx_FromAry($item, $ary)
	{
		$cnt = 0;
		$ret = '';
		foreach($ary as $val )
		{
			if( $cnt > 0 ) $ret .= 'OR ';
			$ret .= $item.'=';
			if( is_numeric($val) )
				$ret .= $val.' ';
			else if( is_string($val) )
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
	protected function CreateString_xANDx_FromAry($item, $ary)
	{
		$cnt = 0;
		$ret = '';
		foreach($ary as $val )
		{
			if( $cnt > 0 ) $ret .= 'AND ';
			$ret .= $item.'=';
			if( is_numeric($val) )
				$ret .= $val.' ';
			else if( is_string($val) )
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
	protected function CreateString_xINx_FromAry($ary)
	{
		$cnt = 0;
		$ret = 'IN (';
		foreach($ary as $val )
		{
			if( $cnt > 0 ) $ret .= ',';
			
			if( is_numeric($val) )
				$ret .= $val.' ';
			else if( is_string($val) )
				$ret .= "'{$val}' ";
			++$cnt;
		}
		return $ret.')';
	}
/*	
	public function MyLock()
	{
		if( DB_TYPE == 'pgsql')
		{
			$this->DB()->query('LOCK '.$this->m_table.' IN SHARE UPDATE EXCLUSIVE MODE');
		}
		else
		{
			$this->DB()->query('SELECT '.$this->m_table.'FROM FOR UPDATE');
			$addWhere = " FOR UPDATE";
		}
	}*/

	/**
	 * テーブルを削除
	 *
	 * @param  string $table テーブル名
	 * @return boolean 削除に成功した場合true
	 */
	protected function DropTable($table)
	{
		$this->DB()->exec("DROP TABLE {$table};");
		if( $this->DB()->isError() )
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
	abstract public function Install($arrPlugin, $arrTableList);
	
	/**
	 * アンインストール
	 * uninstallはアンインストール時に実行されるようにしてください。
	 * 引数にはdtb_pluginのプラグイン情報が渡されます.
	 *
	 * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
	 * @param  array $arrTableList 存在するテーブル名の配列
	 * @return boolean
	 */
	public function Uninstall($arrPlugin, $arrTableList)
	{
		if (in_array($this->m_table, $arrTableList))
			return $this->DropTable($this->m_table);
		return true;
	}
}
?>