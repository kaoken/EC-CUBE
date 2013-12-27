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
require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/util/plg_ProductImagesAddKN_Util.php';
require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/plg_ProductImagesAddKN_Img.php';

/**
* ProductImagesAddKN各ページ(テンプレート)で共通して使える便利なクラス
*
* @package ProductImagesAddKN
* @author kaoken
* @since PHP 5.3　
* @version 1.0
*/
class plg_ProductImagesAddKN_PageUtil
{
	private $m_aCashImg = array();
	private static $m_objImg = null;
	
	/**
	 * コンストラクタ
	 *
	 * @return void
	 */
	public function __construct($objImg=null)
	{
		if ( !is_null($objImg) )
			$this->m_objImg = $objImg;		
		else
			$this->m_objImg = new plg_ProductImagesAddKN_Img();		
	}
	
	/**
	 * 指定した幅、高さ、画像名からURLを取得
	 * 
	 * @param int	 $w        幅
	 * @param int	 $h        高さ
	 * @param string $src_file 画像ファイル名
	 * @return 画像のあるURL
	 */
	public function GetUrlFromSaveImgName($w,$h,$src_file)
	{
		$aPara['width'] = $w;
		$aPara['height'] = $h;
		
		if (!$this->m_objImg->CheckFileName($aPara['src_file']))
			GC_Utils_Ex::gfPrintLog('invalid access :resize_image.php image=' . $aPara['src_file']);
		$aPara['src_file'] = SC_Utils_Ex::getSaveImagePath($src_file);

		return $this->m_objImg->CashImage($aPara,true,true);
	}
	
	/**
	 * 指定した幅、高さ、商品画像IDからURLを取得
	 * 
	 * @param int	 $w        幅
	 * @param int	 $h        高さ
	 * @param int    $img_id   商品画像ID
	 * @return 画像のあるURL
	 */
	public function GetUrlFromImgID($w,$h,$img_id)
	{
		$aPara['width'] = $w;
		$aPara['height'] = $h;
		$aPara['img_id'] = $img_id;
		
		return $this->m_objImg->CashImage($aPara,false,true);
	}
	
	/**
	 * 指定した幅、高さ、商品ID、順番から商品画像のURLを取得
	 * 
	 * @param int	 $w          幅
	 * @param int	 $h          高さ
	 * @param int	 $product_id 商品ID
	 * @param int	 $priority   順番
	 * @return 画像のあるURL
	 */
	public function GetUrlFromPP($w,$h,$product_id,$priority)
	{
		$aPara['width'] = $w;
		$aPara['height'] = $h;
		$aPara['product_id'] = $product_id;
		$aPara['priority'] = $priority;

		return $this->m_objImg->CashImage($aPara,false,true);
	}
	
	/**
	 * 指定した幅、高さ、商品ID、順番から商品画像のURLを取得
	 * 
	 * @param int	 $w          幅
	 * @param int	 $h          高さ
	 * @param int	 $product_id 商品ID
	 * @param string $image_key  イメージキー'list'、'main'、'large'のみ
	 * @return 画像のあるURL
	 */
	public function GetUrlFromPImgKey($w,$h,$product_id,$image_key)
	{
		$aPara['width'] = $w;
		$aPara['height'] = $h;
		if ( ($aPara['src_file'] = $this->m_objImg->GetImgNameFromImgKeyProductID($product_id,$image_key)) === false )
		{
			return $this->m_objImg->DrawErrImageURL(3);
		}

		return $this->m_objImg->CashImage($aPara,true,true);
	}
	
	
	/**
	 * 指定した商品IDから商品画像の情報が入った配列を返す。
	 * 
	 * @param int	 $product_id 商品ID
	 * @return array
	 */
	public function GetProcuctImgData($product_id)
	{
 		$knUtil = plg_ProductImagesAddKN_Util::GetMy();
		// DB
		$dbImg = $knUtil->GetDB('ProductImg');	
		// 商品画像をセット
		$aData = $dbImg->GetFromPrdouctID($product_id);
		// いらない情報は削除しておく
		foreach($aData as &$val)
		{
			unset($val['imgdat']);
			unset($val['product_id']);
			unset($val['create_tm']);
			$val['ext'] = $knUtil->GetExtFromMIME($val['mime']);
		}
		return $aData;
	}
}

?>