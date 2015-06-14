<?php
/*
 * This is a plug-in "ProductImagesAddKN" of EC CUBE.
 *
 * Copyright(c) 2013 kaoken CO.,LTD. All Rights Reserved.
 *
 * http://www.kaoken.cg0.org/
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
// メモ：print_r($this)で見ると、Smarty.class.php 内で呼ばれている
$arrPageLayout = $this->get_template_vars('arrPageLayout');
switch($arrPageLayout['device_type_id']) {
	case 1:
		break;
	case 2:
		break;
	case 10:
		break;
	default:

		if ( preg_match("@".ADMIN_DIR."products/product.php$@",$_SERVER['PHP_SELF']) ) {
			// 管理画面：商品登録｜編集画面の時
			$knUtil = plg_ProductImagesAddKN_Util::getMy();

			$path = $knUtil->getTemplatePath('/products/admin','Header');
			$this->display($path);
		}
}
