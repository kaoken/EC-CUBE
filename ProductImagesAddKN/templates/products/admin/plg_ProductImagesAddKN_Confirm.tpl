<!--{*
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
 *}-->
    <table>
        <tr>
            <th>商品画像</th>
            <td>
            <!--{foreach from=$arrProductImgs item=arrProductImg name=arrProductImgs}-->
				<a href="<!--{$smarty.const.HTTPS_URL}-->resize_image.php?img_id=<!--{$arrProductImg.img_id}-->" target="new" >
					<img src="<!--{$smarty.const.HTTPS_URL}-->resize_image.php?img_id=<!--{$arrProductImg.img_id}-->&width=130&height=130" style="max-width: 65px;max-height: 65px;" />
				</a>
            <!--{/foreach}-->
            </td>
        </tr>
	</table>