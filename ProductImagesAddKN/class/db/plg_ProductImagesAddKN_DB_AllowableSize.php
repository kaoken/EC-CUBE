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
* ProductImagesAddKNプラグイン キャッシュイメージの許容サイズ関連のDBクラス
*
* @package ProductImagesAddKN
* @author kaoken
* @since PHP 5.3　
* @version 0.1
*/
class plg_ProductImagesAddKN_DB_AllowableSize extends plg_ProductImagesAddKN_DB_Base
{
	/**
	 * コンストラクタ
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->m_table = self::TABLE_ALLOWABLE_SIZE;			
	}
	
		
	/**
	 * 許容サイズか？
	 * 
	 * @param int $width 幅
	 * @param int $height 高さ
	 * @return 許容サイズならtrueを返す
	 */
	public function Check($width, $height)
	{
		$col = "*";
		$where = "width = ".$width." AND height = ".$height;
		$arrColumns = $this->DB()->select($col, $this->m_table, $where);
		if( count($arrColumns) > 0 )
			return true;
		return false;
	}
	
	/**
	 * 許容サイズリストを取得する
	 * 
	 * @param array $aAddSize 追加サイズ
	 * @param array $aErrAddSize error情報
	 * @return boolean
	 */
	public function GetList()
	{
		$where = "width != 0 ORDER BY width ASC"; 
		$ret = $this->DB()->select("*", $this->m_table, $where);
  		if( count($ret) > 0 )
			return $ret;
		return array();					 
	}
	
	/**
	 * 許容サイズを削除する
	 * 
	 * @param array $aAddSize 追加サイズ
	 * @param array $aErrAddSize error情報
	 * @return boolean
	 */
	public function Delete($aAddSize, &$aErrAddSize)
	{
		$this->Begin();
		$where = "width=".$aAddSize['width']." AND height=".$aAddSize['height']." AND not_del = 0";
		$ret = $this->DB()->delete($this->m_table, $where );

		if( !is_numeric($ret) || $ret == 0 || $this->IsError())
		{
			$aErrAddSize["insert_failure"]="幅：".$aAddSize['width']." 高さ：".$aAddSize['height']."px は削除できませんでした。";
			$this->Rollback();
			return false;
		} 
		else
		{
			$this->Commit();
		}   
		return true;					 
	}

	/**
	 * 許容サイズを挿入する
	 * 
	 * @param array $aAddSize 追加サイズ
	 * @param array $aErrAddSize error情報
	 * @return boolean
	 */
	public function Insert($aAddSize, &$aErrAddSize)
	{
		if( $this->DB()->select("*", $this->m_table, "width=".$aAddSize['width']." AND height=".$aAddSize['height']) == null)
		{
			$this->Begin();
			if( $this->DB()->insert($this->m_table, $aAddSize) == false || $this->IsError())
			{
				$aErrAddSize["insert_failure"]="幅：".$aAddSize['width']."px 高さ：".$aAddSize['height']."px は、追加に失敗しました。";
				$this->Rollback();
				return false;
			}
			else
			{
				$this->Commit();
			}
		} 
		else
		{
			$aErrAddSize["insert_failure"]="幅：".$aAddSize['width']."px 高さ：".$aAddSize['height']."px はすでに登録されています。";	
			return false;															 
		} 
		return true;					 
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
			// リサイズ画像の許可するサイズ
			$sqlval = "";
			if (!in_array($this->m_table, $arrTableList))
			{
				if( DB_TYPE  == 'pgsql')
				{
					$sqlval .= "CREATE TABLE ".$this->m_table." (";
					$sqlval .= "  width smallint NOT NULL,";
					$sqlval .= "  height smallint NOT NULL,";
					$sqlval .= "  not_del smallint NOT NULL DEFAULT 0";
					$sqlval .= ");";
				}
				else
				{
					$sqlval .= "CREATE TABLE ".$this->m_table." (";
					$sqlval .= "  width smallint NOT NULL,";
					$sqlval .= "  height smallint NOT NULL,";
					$sqlval .= "  not_del tinyint(1) NOT NULL DEFAULT 0,";
					$sqlval .= "  CONSTRAINT uc_".$this->m_table."_id UNIQUE(width,height)";
					$sqlval .= ");";
				}
				// テーブル作成
				if( !$this->DB()->exec($sqlval) )throw new Exception($this->m_table);
				if( DB_TYPE  == 'pgsql')
				{
					$sqlval = "CREATE UNIQUE INDEX uc_".$this->m_table."_id ON ".$this->m_table." USING btree (width,height);";
					if( !$this->DB()->exec($sqlval) )throw new Exception($this->m_table.":CREATE UNIQUE INDEX]");
				}
				// 初期コンフィグデータ 挿入
				$aVal["not_del"] = 1;	// 基本サイズは削除できないようにする			
				$aVal["width"] = LARGE_IMAGE_WIDTH; $aVal["height"] =LARGE_IMAGE_HEIGHT;
				$this->DB()->insert($this->m_table, $aVal);
				$aVal["width"] = SMALL_IMAGE_WIDTH; $aVal["height"] =SMALL_IMAGE_HEIGHT;
				$this->DB()->insert($this->m_table, $aVal);
				$aVal["width"] = NORMAL_IMAGE_WIDTH; $aVal["height"] =NORMAL_IMAGE_HEIGHT;
				$this->DB()->insert($this->m_table, $aVal);
				$aVal["width"] = NORMAL_SUBIMAGE_WIDTH; $aVal["height"] =NORMAL_SUBIMAGE_HEIGHT;
				$this->DB()->insert($this->m_table, $aVal);
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