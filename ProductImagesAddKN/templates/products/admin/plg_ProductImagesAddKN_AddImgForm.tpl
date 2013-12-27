<!--{*
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
 *}-->
<input type="hidden" name="kn_temp_product_id" value="<!--{$arrForm.kn_temp_product_id|h}-->" />

<h2>商品画像の追加</h2> 
<div id="kn_product_text">
アップロードできる画像ファイルは、<strong>png</strong>,<strong>gif</strong>,<strong>jpeg</strong> の３つのみです。最大画像サイズは <!--{$arrForm.kn_product_img_max_size_prefix}--> 、最大幅 <!--{$arrForm.kn_product_img_max_width}--> px、最大の高さ <!--{$arrForm.kn_product_img_max_height}--> px １つの商品に最大 <!--{$arrForm.kn_product_img_upload_max}-->個アップロードできます。ファイルの複数選択も可能です。
</div>

<input id="img_upload_kn" name="img_upload_kn"type="file" name="files[]" multiple>
<br />
<br />
<div id="kn_product_img_body">
	<div id="kn_product_img_list_bottom">
        すべてにチェックを <strong><a href="javascript:;" id="kn_all_select">つける</a></strong>／<strong><a href="javascript:;" id="kn_all_deselect">外す</a></strong> 
        <a class="btn-normal" href="javascript:;" id="kn_selected_del"><span>選択した画像を削除する</span></a>   
    </div>
	<div id="kn_product_img_list">
<script id="tmpl-product-images-list" type="text/x-jquery-tmpl">
		<div class="kn_product_img_frame" id="kn_product_img_frame${file.id}">
			<div class="kn_product_image_id">
				<input name="kn_del_check[]" type="checkbox" class="kn_del_check" id="kn_del_check${file.id}" value="${file.id}" />
				<label for="kn_del_check${file.id}">ID:${file.id}</label>
			</div>
			<div class="kn_product_image">
				<a href="<!--{$smarty.const.HTTPS_URL}-->resize_image.php?img_id=${file.id}" target="new" >
					<img src="<!--{$smarty.const.HTTPS_URL}-->resize_image.php?img_id=${file.id}&width=130&height=130" style="max-width: 65px;max-height: 65px;" />
				</a>
			</div>
			<div class="kn_product_image_w_h">
				${file.width} px × ${file.height} px 
			</div>
			<div class="kn_product_image_w_h">
				${file.date}
			</div>
			<div class="kn_product_image_priority">
				<input type="hidden" name="kn_product_img_priority_now${file.id}" id="kn_product_img_priority_now${file.id}" value="${file.priority}" />
				<input type="text" name="kn_product_img_priority${file.id}" id="kn_product_img_priority${file.id}" class="box4" maxlength="4" value="${file.priority}" />
				<input type="button" name="kn_product_img_priority_btn"value="移動" onclick="ChangePriority(${file.id})" />
			</div>
		</div>
</script>
<!--{*
		<div class="kn_product_img_frame" id="${fileID}">
			<div class="kn_product_image_id" style="text-align: right;">
				<a href=\'javascript:$(\'#${instanceID}\').uploadify("cancel", "${fileID}")\'>&nbsp;</a>
			</div>
			<div class="kn_product_image">
				<img src="<!--{$smarty.const.HTTPS_URL}-->plugin/ProductImagesAddKN/img/loading65.gif"/>
			</div>
			<div class="kn_product_image_w_h">
				${fileName}
			</div>
			<div class="kn_product_image_w_h">
				${fileSize}
			</div>
			<div class="kn_product_image_priority">
				<span class="data"></span>
			</div>
		</div>
*}-->
                
        <div id="kn_product_img_frame_clear"> </div>
	</div>
</div>    

<script>
	
<!--{if $isMoveForciblyKn }-->
	<!--{* 商品登録 初回の場合、強制的に読み直す。 *}-->
    document.form1['mode'].value = 'moveForciblyKn';
    document.form1.submit();	
<!--{else}-->
	var g_url = '<!--{$smarty.const.HTTPS_URL}-->plugin/ProductImagesAddKN/img_upload.php';
	var g_para = 'product_id=<!--{$arrForm.product_id|h}-->&kn_temp_product_id=<!--{$arrForm.kn_temp_product_id|h}-->&<!--{$smarty.const.TRANSACTION_ID_NAME}-->=<!--{$transactionid}-->';
	var g_tmplImgList = "#tmpl-product-images-list";
	var g_isRunUpload = false;
	var g_isRunDel = false;
	var g_isRunMove = false;
(function ($) {
    'use strict';
	// 読み込み時、商品画像一覧を作る
	$.ajax({
		type: "POST",
		url: g_url,
		dataType: "json",
		data: 'mode=list&'+g_para,
		success: function(data) 
		{
			if (data == null)return;
			else if ( data.error )
				alert('商品画像一覧表示に関するエラー : ' + data.error);
			else if ( data.list )
			{
				for (var i =0; i<data.num; i++) 
					$.tmpl( $( g_tmplImgList ), {file:data.list[i]}).appendTo( "#kn_product_img_list" );
			}
		},
		error: function(data) 
		{
			alert('商品画像一覧表示に関するエラー : ' + data);
		}
	});
	
	// 選択された商品画像を削除する
	var g_isDeleting = false;
	$('#kn_selected_del').click(function(e) {
		if ( g_isDeleting )
		{
			alert('商品画像を削除中です。');
			return;	
		}
		var delNumList = '&nums[]=';
		var checks=[];
		var checkCnt=0;
		
		// チェックされてい商品画像IDを取得 
		$("[name='kn_del_check[]']:checked").each(function(){
			delNumList += '&nums['+checkCnt+']='+this.value;
			checks.push(this.value);
			++checkCnt;
		});

		
		if ( checkCnt == 0 )
		{
			alert('チェックが一つもありません。');
			return;
		}
		
		$.ajax({
			type: "POST",
			url: g_url,
			dataType: "json",
			data: 'mode=del&'+g_para+delNumList,
			beforeSend : function( data )
			{
				g_isDeleting = true;
			},
			success: function( data )
			{
				if (data == null)return;
				else if ( data.error )
					alert('商品画像削除に関するエラー : ' + data.error);
				else if ( data.del_img )
				{
					for( var i = 0; i<checks.length; ++i )
					{
						$('#kn_product_img_frame'+checks[i]).remove();
					}
					if ( data.list )
					{
						for (var i =0; i<data.list.length; i++)
						{
							$('#kn_product_img_priority'+data.list[i].id).prop({'value':data.list[i].priority});
						}
					}
					alert("商品画像 "+data.del_img+"個 削除されました。\n");
				}
			},
			error: function( data ) {
				alert('商品画削除に関するエラー : ' + data);
			},
			complete : function( data )
			{
				g_isDeleting = false;
			}
		});
	});
		

	$("#kn_all_select").click(function(){
		$("input:checkbox[name='kn_del_check[]']").prop({'checked':'checked'});
	});

	$("#kn_all_deselect").click(function(){
		$("input:checkbox[name='kn_del_check[]']").prop({'checked':false});
	});
	
	var g_uploadCancel = false;
	$('#img_upload_kn').uploadify({
		fileTypeDesc : 'Image Files',
        fileTypeExts : '*.gif; *.jpg; *.jpeg; *.png',
		fileObjName  : 'kn_prduct_img_files',
		fileSizeLimit: '<!--{$arrForm.kn_product_img_max_size|h}-->MB',
		queueSizeLimit : <!--{$arrForm.kn_product_img_upload_max|h}-->,
		method    : 'post',
		formData  :
			{
				'mode' : 'upload',
				'product_id' : '<!--{$arrForm.product_id|h}-->',
				'kn_temp_product_id' : '<!--{$arrForm.kn_temp_product_id|h}-->',
				'<!--{$smarty.const.TRANSACTION_ID_NAME}-->' : '<!--{$transactionid}-->'
			},
		queueID   : 'kn_product_img_list',
		itemTemplate:'<div class="kn_product_img_frame"id="${fileID}"><div class="kn_product_image_id"style="text-align: right;"><a href=\'javascript:$(\'#${instanceID}\').uploadify("cancel","${fileID}")\'>&nbsp;</a></div><div class="kn_product_image"><img src="<!--{$smarty.const.HTTPS_URL}-->plugin/ProductImagesAddKN/img/loading65.gif"/></div><div class="kn_product_image_w_h">${fileName}</div><div class="kn_product_image_w_h">${fileSize}</div><div class="kn_product_image_priority"><span class="data"></span></div></div>',
		removeTimeout:0,
		swf       : '<!--{$smarty.const.HTTPS_URL}-->plugin/ProductImagesAddKN/uploadify.swf',
		uploader  : g_url,
		multi     : true,
		onUploadSuccess : function(file, data, response) {
			file = $.parseJSON(data);
			if ( file.errorNo != 0 )
			{
				if (g_uploadCancel)return;
				g_uploadCancel = true;
				$('#img_upload_kn').uploadify('cancel', '*');
				alert(file.error);
				return;	
			}
			$.tmpl( $( g_tmplImgList ), {file:file}).appendTo( "#kn_product_img_list" );
    	},
		onQueueComplete : function(queueData) {
			g_uploadCancel = false;
        }
	});

})($j1_10_2);


function ChangePriority(img_id)
{
(function ($) {
	var priorityID = '#kn_product_img_priority'+img_id;
	var priorityNID = '#kn_product_img_priority_now'+img_id;
	var priority = parseInt($(priorityID).val()); 
	var priorityN = parseInt($(priorityNID).val()); 
	var movePara = '&img_id='+img_id+'&priority='+priority;
	var isErr = false;
	if ( isNaN(priority) )isErr=true;
	else if ( priority < 0 )isErr=true;
	
	if ( isErr ){
		$(priorityID).val(priorityN);
		alert('商品画像[ID:'+img_id+']の移動番号が不正です。');
		return;	
	}
	else if ( priority == priorityN )
		return;
	
	$.ajax({
		type: "POST",
		url: g_url,
		dataType: "json",
		data: 'mode=move&'+g_para+movePara,
		beforeSend : function( data )
		{
			$("input:button[name='kn_product_img_priority_btn']").prop({'disabled': true});
		},
		success: function( data )
		{
			if (data == null)return;
			else if ( data.error )
				alert('商品画像の順番を変更に関するエラー : ' + data.error);
				
			for (var i =0; i<data.num; i++)
			{
				$('#kn_product_img_priority'+data.list[i].id).prop({'value':data.list[i].priority});
				$('#kn_product_img_priority_now'+data.list[i].id).prop({'value':data.list[i].priority});
			}
			var frameID = '#kn_product_img_frame'+data.target.id;
			$(frameID).remove();
			if ( data.target.priority == (parseInt(data.num)-1) )
			{
				$.tmpl( $( g_tmplImgList ), {file:data.target} ).appendTo( "#kn_product_img_list" );
			}
			else
			{
				$.tmpl( $( g_tmplImgList ), {file:data.target} ).insertBefore('#kn_product_img_frame'+data.list[parseInt(data.target.priority)+1].id);
			}
			$('.kn_product_img_frame'+frameID)
			.animate( {backgroundColor: '#f19ec2'}, 500)
			.animate( {backgroundColor: '#FFFFFF'}, 500)
			.animate( {backgroundColor: '#f19ec2'}, 500)
			.animate( {backgroundColor: '#FFFFFF'}, 500);
		},
		error: function(e, data, text) {
			alert('商品画像の順番を変更に関するエラー : ' + text);
		},
		complete : function( data )
		{
			$("input:button[name='kn_product_img_priority_btn']").prop({'disabled': false});
		}
	});
})($j1_10_2);
}
<!--{/if}-->

</script>