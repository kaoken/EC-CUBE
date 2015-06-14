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
require_once PLUGIN_UPLOAD_REALDIR . 'ProductImagesAddKN/class/plg_ProductImagesAddKN_Img.php';

/**
 * アップロードファイル管理クラス
 *
 * @package ProductImagesAddKN
 * @author kaoken
 * @since PHP 5.3　
 * @version 1.0
 */
class plg_ProductImagesAddKN_SC_UploadFile extends SC_UploadFile
{
	/**
	 * サムネイル画像の作成
	 *
	 * @param string $src_file 拡張子を含めた画像ファイル名
	 * @param int	$width	幅
	 * @param int	$height   高さ
	 * @param string $dst_file 画像ファイル名
	 * @return string ファイル名を返す
	 */
	public function makeThumb($src_file, $width, $height, $dst_file)
	{
		try {
			$knUtil = plg_ProductImagesAddKN_Util::getMy();
			$db = $knUtil->getDB('Config');
			$aConfig = $db->get();


			$imgInfo = @getimagesize($src_file);
			if ( $imgInfo === false )
				throw new RuntimeException('画像ファイルではありません。');

			switch ($imgInfo[2]) {
				case IMAGETYPE_GIF:
					$dst_file .= '.gif'; break;
				case IMAGETYPE_JPEG:
					$dst_file .= '.jpg'; break;
				case IMAGETYPE_PNG:
					$dst_file .= '.png'; break;
				default:
					throw new RuntimeException('対応している画像は(GIF, JPEG, PNG)のみです。MIME:['.$imgInfo['mime'].']は、対象外です。');
			}
			// サイズが同じ場合、そのままファイルを出力
			if ( ($width === $imgInfo[0] && $height === $imgInfo[1]) ||
			     ( $width === 0 && $height === 0 )) {
				copy($src_file, $dst_file);
			} else {
				$objImage = new Imagick($src_file);
				// 圧縮率
				$objImage->setCompressionQuality($aConfig['compression_rate']);


				if ($width < $imgInfo[0] || $height < $imgInfo[1]) {
					$aWH = plg_ProductImagesAddKN_Img::ratioFixation($width,$height,$imgInfo[0],$imgInfo[1]);
					$objImage->thumbnailImage($aWH[0],$aWH[1]);
				}
				$objImage->writeImage($dst_file);
				$objImage->destroy();
			}
		} catch (RuntimeException $e) {
			echo $e->getMessage();
			exit;
		}

		return basename($dst_file);
	}
}