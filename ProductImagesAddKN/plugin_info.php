<?php
/*
 * ProductImagesAddKN
 *
 * Copyright(c) kaoken All Rights Reserved.
 *
 * http://www.kaoken.net/
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
 
/**
* ProductImagesAddKNプラグイン情報クラス
*
* @package ProductImagesAddKN
* @author kaoken
* @since PHP 5.3　
* @version 2.13.1
*/
class plugin_info{
	/** プラグインコード(必須)：プラグインを識別する為キーで、他のプラグインと重複しない一意な値である必要があります */
	static $PLUGIN_CODE	  	= "ProductImagesAddKN";
	/** プラグイン名(必須)：EC-CUBE上で表示されるプラグイン名. */
	static $PLUGIN_NAME	  	= "商品に画像を追加する";
	/** プラグインバージョン(必須)：プラグインのバージョン. */
	static $PLUGIN_VERSION	= "1.0";
	/** 対応バージョン(必須)：対応するEC-CUBEバージョン. */
	static $COMPLIANT_VERSION = "2.13.1";
	/** 作者(必須)：プラグイン作者. */
	static $AUTHOR			= "kaoken";
	/** 説明(必須)：プラグインの説明. */
	static $DESCRIPTION	  	= "商品毎に複数画像を追加できるプラグインです。さらに、リサイズした後の画像を一定期間保存し、読み込みを早くします。\n詳しくはドキュメントを見てください。";
	/** プラグイン用のサイトURL設定されている場合はプラグイン管理画面のプラグイン名がリンクになります。 */
	//static $PLUGIN_SITE_URL   = "http://www.kaoken.net/";
	/** プラグイン作者URL：プラグイン毎に設定出来るURL（説明ページなど） */
	static $AUTHOR_SITE_URL	= "http://www.kaoken.net/";
	/** 使用するフックポイント・コールバック関数。 */
	static $HOOK_POINTS		= array(
		array('loadClassFileChange', 'LoadClassFileChange'),
		array("prefilterTransform", 'PrefilterTransform'),
		array('LC_Page_preProcess', 'PagePreProcess'),
		array('LC_Page_ResizeImage_action_before', 'HookResizeImageActionBefore'),
		array('LC_Page_Admin_Products_action_after', 'PageAdminProductsActionAfter'), 
		array('LC_Page_Admin_Products_Product_action_after', 'PageAdminProductsProductActionAfter')
	);
	/** クラス名(必須)：プラグインのクラス（拡張子は含まない） */
	static $CLASS_NAME		= "ProductImagesAddKN";
	/** ライセンス */
	static $LICENSE			= "LGPL";
}
?>