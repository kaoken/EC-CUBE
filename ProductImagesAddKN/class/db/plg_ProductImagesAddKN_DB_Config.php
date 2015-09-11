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
 * ProductImagesAddKNプラグイン キャッシュイメージ関連のDBクラス
 *
 * @package ProductImagesAddKN
 * @author kaoken
 * @since PHP 5.3　
 * @version 1.0
 */
class plg_ProductImagesAddKN_DB_Config extends plg_ProductImagesAddKN_DB_Base
{
	/**
	 * コンストラクタ
	 */
	public function __construct()
	{
		parent::__construct();
		$this->m_table = self::TABLE_CONFIG;

		$this->initVar('id', self::TYPE_INT, 0, false, 255);
		$this->initVar('version', self::TYPE_INT, 10000, false);
		$this->initVar('compression_rate', self::TYPE_INT, 80, true);
		$this->initVar('product_img_copy', self::TYPE_INT, 0, true);
		$this->initVar('product_img_max_size', self::TYPE_INT, 1, true);
		$this->initVar('product_img_upload_max', self::TYPE_INT, 32, true);
		$this->initVar('product_img_max_width', self::TYPE_INT, 9999, true);
		$this->initVar('product_img_max_height', self::TYPE_INT, 9999, true);
	}

	/**
	 * プラグインのコンフィグ情報取得
	 *
	 * @return array
	 */
	public function get()
	{
		// 現在DBに存在するテーブル一覧を取得
		$where = "id = 0";
		$arrColumns = $this->db()->select("*", $this->m_table, $where);
		if ( count($arrColumns) != 0 )
		{
			$this->dataTypeCastFromColumn($arrColumns[0]);
			return $arrColumns[0];
		}
		return array();
	}

	/**
	 * プラグインのコンフィグ情報更新
	 *
	 * @param array $aConfig コンフィグ情報
	 * @return boolean
	 */
	public function update($aConfig)
	{
		$this->begin();
		$where = "id = 0";
		if ( $this->db()->update($this->m_table, $aConfig, $where) === false )
		{
			$this->rollback();
			return false;
		}
		else
		{
			$this->commit();
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
					$sqlval .= "  id                     smallint NOT NULL PRIMARY KEY,";
					$sqlval .= "  version                int      NOT NULL,";
					$sqlval .= "  compression_rate       smallint NOT NULL DEFAULT 80,";
					$sqlval .= "  product_img_copy       smallint NOT NULL DEFAULT 0,";
					$sqlval .= "  product_img_max_size   int      NOT NULL DEFAULT 1,";
					$sqlval .= "  product_img_upload_max smallint NOT NULL DEFAULT 32,";
					$sqlval .= "  product_img_max_width  smallint NOT NULL DEFAULT 9999,";
					$sqlval .= "  product_img_max_height smallint NOT NULL DEFAULT 9999";
					$sqlval .= ");";
				}
				else
				{
					$sqlval .= "CREATE TABLE ".$this->m_table." (";
					$sqlval .= "  id                     tinyint    NOT NULL,";
					$sqlval .= "  version                int        NOT NULL,";
					$sqlval .= "  compression_rate       smallint   NOT NULL DEFAULT 80,";
					$sqlval .= "  product_img_copy       tinyint(1) NOT NULL DEFAULT 0,";
					$sqlval .= "  product_img_max_size   int        NOT NULL DEFAULT 1,";
					$sqlval .= "  product_img_upload_max smallint   NOT NULL DEFAULT 32,";
					$sqlval .= "  product_img_max_width  smallint   NOT NULL DEFAULT 9999,";
					$sqlval .= "  product_img_max_height smallint   NOT NULL DEFAULT 9999,";
					$sqlval .= "  PRIMARY KEY (id)";
					$sqlval .= ");";
				}
				// テーブル作成
				if ( !$this->db()->exec($sqlval) )throw new Exception($this->m_table);
				// 初期コンフィグデータ 挿入
				$sqlval = array(
					"id"=>0,
					"version"=>10000,
					"compression_rate"=>80,
					"product_img_copy"=>0,
					"product_img_max_size"=>1,
					"product_img_upload_max"=>32,
					"product_img_max_width"=>9999,
					"product_img_max_height"=>9999
				);
				$this->db()->insert($this->m_table, $sqlval);
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