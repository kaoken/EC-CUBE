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

require_once CLASS_EX_REALDIR . 'SC_FormParam_Ex.php';

/**
 * plg_ProductImagesAddKN_SC_FormParam_Ex
 * 
 * @package SC
 * @author kaoken
 * @copyright kaoken
 * @version 2013
 * @access public
 */
class plg_ProductImagesAddKN_SC_FormParam_Ex extends SC_FormParam_Ex
{
	protected $m_numMinMax = array();
	
	/**
	 * 数値かつ、範囲指定をする
	 * 
	 * @param string $disp_name
	 * @param string $keyname
	 * @param int $min 最大値
	 * @param int $max 最小値
	 * @param array $aKeyname 
	 * @return void
	 */
	public function addParamNumLimit($disp_name, $keyname,$min,$max, $aCheck=array('EXIST_CHECK'))
	{
		if( array_key_exists( $keyname, $this->m_numMinMax) ) 
		{
			$this->m_numMinMax[$keyname][0] = $min;
			$this->m_numMinMax[$keyname][1] = $max;
			return;
		}
		$this->m_numMinMax[$keyname] = array($min,$max);
		if( !array_key_exists( $keyname, $this->arrCheck ) )
		{
			$aCheck += array('NUM_CHECK', 'SPTAB_CHECK', 'MAX_LENGTH_CHECK',"MINMAX_CHECK");
			$lenMin = strlen($min+"");
			$lenMax = strlen($max+"");
			$len = $lenMin > $lenMax  ? $lenMin : $lenMax;
			$this->addParam($disp_name, $keyname, $len, 'n', $aCheck);
	   }
	}

	/**
	 * エラーチェック
	 * 
	 * @param bool $br
	 * @return
	 */
	public function checkError($br = true)
	{
		$arrErr = array();

		foreach ($this->keyname as $index => $key) {
			foreach ($this->arrCheck[$index] as $func) {
				$value = $this->getValue($key);
				switch ($func) {
					case 'EXIST_CHECK':
					case 'NUM_CHECK':
					case 'EMAIL_CHECK':
					case 'EMAIL_CHAR_CHECK':
					case 'ALNUM_CHECK':
					case 'GRAPH_CHECK':
					case 'KANA_CHECK':
					case 'URL_CHECK':
					case 'IP_CHECK':
					case 'SPTAB_CHECK':
					case 'ZERO_CHECK':
					case 'ALPHA_CHECK':
					case 'ZERO_START':
					case 'FIND_FILE':
					case 'NO_SPTAB':
					case 'DIR_CHECK':
					case 'DOMAIN_CHECK':
					case 'FILE_NAME_CHECK':
					case 'MOBILE_EMAIL_CHECK':
					case 'MAX_LENGTH_CHECK':
					case 'MIN_LENGTH_CHECK':
					case 'NUM_COUNT_CHECK':
					case 'KANABLANK_CHECK':
					case 'SELECT_CHECK':
					case 'FILE_NAME_CHECK_BY_NOUPLOAD':
					case 'NUM_POINT_CHECK':
						$this->recursionCheck($this->disp_name[$index], $func,
							$value, $arrErr, $key, $this->length[$index]);
						break;
					// 小文字に変換
					case 'CHANGE_LOWER':
						$this->toLower($key);
						break;
					// ファイルの存在チェック
					case 'FILE_EXISTS':
						if ($value != '' && !file_exists($this->check_dir . $value)) {
							$arrErr[$key] = '※ ' . $this->disp_name[$index] . 'のファイルが存在しません。<br>';
						}
						break;
					// ダウンロード用ファイルの存在チェック
					case 'DOWN_FILE_EXISTS':
						if ($value != '' && !file_exists(DOWN_SAVE_REALDIR . $value)) {
							$arrErr[$key] = '※ ' . $this->disp_name[$index] . 'のファイルが存在しません。<br>';
						}
						break;
					case 'MINMAX_CHECK':
						if( is_numeric($value) && (intval($value) < $this->m_numMinMax[$key][0] || $this->m_numMinMax[$key][1] < intval($value)) ) {
							$arrErr[$key] = '※ 数値の範囲は[' . $this->m_numMinMax[$key][0];
							$arrErr[$key] .= '～' . $this->m_numMinMax[$key][1].']でなければいけません<br>';
						}
						break;
					default:
						$arrErr[$key] = "※※　エラーチェック形式($func)には対応していません　※※ <br>";
						break;
				}
			}

			if (isset($arrErr[$key]) && !$br) {
				$arrErr[$key] = preg_replace("/<br(\s+\/)?>/i", '', $arrErr[$key]);
			}
		}

		return $arrErr;
	}
}
?>