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
	 */
	public function addParamNumLimit($disp_name, $keyname, $min, $max, $aCheck=array('EXIST_CHECK'))
	{
		if ( array_key_exists( $keyname, $this->m_numMinMax) ) {
			$this->m_numMinMax[$keyname][0] = $min;
			$this->m_numMinMax[$keyname][1] = $max;
			return;
		}

		$this->m_numMinMax[$keyname] = array($min,$max);
		if ( !array_key_exists( $keyname, $this->arrCheck ) ) {
			$aCheck += array('NUM_CHECK', 'SPTAB_CHECK', 'MAX_LENGTH_CHECK',"MINMAX_CHECK");
			$lenMin = strlen($min."");
			$lenMax = strlen($max."");
			$len = $lenMin > $lenMax  ? $lenMin : $lenMax;
			$this->addParam($disp_name, $keyname, $len, 'n', $aCheck);
		}
	}

}
