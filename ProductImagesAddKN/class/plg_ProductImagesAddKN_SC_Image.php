<?php
/*
 * ProductImagesAddKN
 *
 * Copyright(c) kaoken All Rights Reserved.
 *
 * http://www.kaoken.cg0.org/
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
require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/util/plg_ProductImagesAddKN_Util.php';

/**
 * アップロードファイル加工クラス(thumb.phpとセットで使用する)
 *
 * @package ProductImagesAddKN
 * @author kaoken
 * @since PHP 5.3　
 * @version 1.0
 */
class plg_ProductImagesAddKN_SC_Image extends SC_Image
{
	/**
	 * 指定ファイルを削除
	 *
	 * @param string $filename ファイル名
	 * @param string $dir	  ディレクトリ
	 * @return void
	 */
	public function deleteImage($filename, $dir)
	{
		$dbCash = plg_ProductImagesAddKN_Util::getMy()->getDB('CashImg');
		$dbCash->deleteFromImageName($filename,true);
		parent::deleteImage($filename, $dir);
	}

}
