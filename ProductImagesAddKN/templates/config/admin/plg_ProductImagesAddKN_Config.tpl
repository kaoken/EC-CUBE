<!--{include file="`$smarty.const.TEMPLATE_ADMIN_REALDIR`admin_popup_header.tpl"}-->

<!--{if !$plgEnable}-->
<p style=" color:#F00">プラグインが有効ではありません。</p>
<!--{else}-->
<script type="text/javascript">
	
	$(function(){
		moveTo(0,0);
		resizeTo(screen.availWidth, screen.availHeight);
		var $x= <!--{$aInfo.scroll_x}-->;
		var $y= <!--{$aInfo.scroll_y}-->;
		if($x || $y) {
			window.scroll($x,$y);
		}
		<!--{if $alertMsg != "" }-->
		alert("<!--{$alertMsg}-->");
		<!--{/if}-->
	});

	function ShareSubmit(fm,name)
	{
		var scroll_y = document.documentElement.scrollTop || document.body.scrollTop;
		var scroll_x = document.documentElement.scrollLeft || document.body.scrollLeft;
		$('<input />').attr('type', 'hidden')
					.attr('name', 'scroll_x')
					.attr('value', scroll_x)
					.appendTo('form#'+name);
		$('<input />').attr('type', 'hidden')
					.attr('name', 'scroll_y')
					.attr('value', scroll_y)
					.appendTo('form#'+name);
		fm.submit();
		return true;
	}
    function DeleteSizeKn(w,h)
    {
        document['frmDel']['width'].value = w;
        document['frmDel']['height'].value = h;
    }
</script>
<style>
.kn_text
{
    font-size: 90%;
    color: #333;
    line-height: 200%;
}
.kn_title
{
    color: #FFF;
    font-weight: bold;
    
}
.kn_explanation
{
	font-size: 90%;
	color: #420000;
	border-radius: 3px 3px 3px 3px;
	border: 1px dashed #FC0;
	padding:5px;
	margin:5px;
}
</style>

<h1><!--{$tpl_subtitle|h}--></h1>
<br />

<h2><a name="info">情報</a></h2>
<table border="0" cellspacing="1" cellpadding="8">
    <tr>
		<th bgcolor="#f3f3f3">商品画像</th>
		<td><!--{$aInfo.img_id}--> 個、 <!--{$aInfo.tb_size_img}--></td>
	</tr>
    <tr>
        <th bgcolor="#f3f3f3">キャッシュ画像数</th>
        <td><!--{$aInfo.cash_img_num}--> 個、 <!--{$aInfo.chas_imgs_size}--></td>
    </tr>
</table>
<br />
<br />



<h2><a name="lost">指定期間を過ぎたキャッシュ画像ファイルの削除</a></h2>

<form name="frmOldFileClean" id="frmLinkClean" method="post" action="<!--{$smarty.server.REQUEST_URI|h}-->" >
<input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
<input type="hidden" name="mode" value="old_chas_del">
<table border="0" cellspacing="1" cellpadding="8">
    <tr>
	<td bgcolor="#f3f3f3">日数<br />
		<span class="kn_text">(0～9999) 0で全て削除</span>
	</td>
	<td>
		<!--{assign var=key value="days"}-->
		<span class="red"><!--{$aErrOldCash[$key]}--></span><br />
		<input type="text" name="days" value="<!--{$aInfo.days|h}-->" maxlength="3" style="<!--{if $aErrOldCash.days != ""}-->
		background-color: <!--{$smarty.const.ERR_COLOR}-->;<!--{/if}-->" size="3" class="box6" />日アクセスされていないファイルを削除対象とする

	</td>
	</tr>
</table>
<!--{assign var=key value="lost_clean_failure"}-->
<!--{if $arrErr[$key]}-->
<span class="red">削除に失敗しました</span><br />
<!--{/if}-->
<div class="btn-area">
    <ul>
        <li>
            <a class="btn-action" href="javascript:void(0);" onclick="return ShareSubmit(document.frmOldFileClean, 'frmOldFileClean');"><span class="btn-next">削除開始</span></a>
        </li>
    </ul>
</div>

</form>
<br />
<br />




<h2><a name="lost">商品とリンク切れ</a></h2>
<div class="kn_explanation">
このプラグインが有効でない状態で商品を削除することによる、リンク切れ画像を削除する操作をします。
</div>

<form name="frmLinkClean" id="frmLinkClean" method="post" action="<!--{$smarty.server.REQUEST_URI|h}-->" >
<input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
<input type="hidden" name="mode" value="lost_clean">
<table border="0" cellspacing="1" cellpadding="8">
    <tr>
        <td colspan="2" align="center" bgcolor="#333333">
            <span class="kn_title">リンク切れ情報</span></td>
    </tr>
    <tr>
        <td bgcolor="#f3f3f3">商品画像</td>
        <td>
        <!--{$aInfo.lost_product_img_num}--> 個 
        </td>
    </tr>
    <tr>
        <td bgcolor="#f3f3f3">キャッシュ画像</td>
        <td>
        <!--{$aInfo.lost_cash_num}--> 個
        </td>
    </tr>
</table>
<!--{assign var=key value="lost_clean_failure"}-->
<!--{if $arrErr[$key]}-->
<span class="red">削除に失敗しました</span><br />
<!--{/if}-->
<div class="btn-area">
    <ul>
        <li>
<!--{if $aInfo.lost_product_img_num != 0 || $aInfo.lost_cash_num != 0}-->
            <a class="btn-action" href="javascript:void(0);" onclick="return ShareSubmit(document.frmLinkClean, 'frmLinkClean');"><span class="btn-next">クリーン</span></a>
<!--{else}-->
			<span class="btn-next">リンク切れはありません</span>
<!--{/if}-->
        </li>
    </ul>
</div>

</form>


<h2><a name="def">基本設定</a></h2>
<form name="form1" id="form1" method="post" action="<!--{$smarty.server.REQUEST_URI|h}-->" >
<input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
<input type="hidden" name="mode" value="edit">
<table border="0" cellspacing="1" cellpadding="8">
    <tr>
        <td bgcolor="#f3f3f3">リサイズ時の圧縮率
            <br />
            <span class="kn_text">(10～100の値)</span></td>
        <td>
            <!--{assign var=key value="compression_rate"}-->
            <span class="red"><!--{$arrErr[$key]}--></span><br />
            <input type="text" name="compression_rate" value="<!--{$arrForm.compression_rate|h}-->" maxlength="3" style="<!--{if $arrErr.compression_rate != ""}-->background-color: <!--{$smarty.const.ERR_COLOR}-->;<!--{/if}-->" size="3" class="box6" />
        </td>
    </tr>
    <tr>
        <td colspan="2" align="center" bgcolor="#333333">
            <span class="kn_title">商品画像に関すること</span></td>
    </tr>
    <tr>
        <td bgcolor="#f3f3f3">商品の複製時<br /></td>
        <td>
            <!--{assign var=key value="product_img_copy"}-->
            <input name="product_img_copy" type="radio" id="product_img_copy1" value="1" <!--{if $arrForm.product_img_copy == 1}-->checked="checked"<!--{/if}--> />
            <label for="product_img_copy1">この商品の画像も複製する。</label><br />
            <input type="radio" name="product_img_copy" id="product_img_copy0" value="0" <!--{if $arrForm.product_img_copy == 0}-->checked="checked"<!--{/if}--> />
            <label for="product_img_copy0">この商品の画像は複製しない。</label>
        </td>
    </tr>
    <tr>
        <td bgcolor="#f3f3f3">1つの商品にアップロードできる画像の最大数<br />
		<span class="kn_text">(1～64 個)</span>
		</td>
        <td>
        <!--{assign var=key value="product_img_upload_max"}-->
            <span class="red"><!--{$arrErr[$key]}--></span><br />
            <input type="text" name="product_img_upload_max" value="<!--{$arrForm.product_img_upload_max|h}-->" maxlength="4" style="<!--{if $arrErr.retention_period != ""}-->
            background-color: <!--{$smarty.const.ERR_COLOR}-->;<!--{/if}-->" size="4" class="box6" /> 個
        </td>
    </tr>
    <tr>
        <td bgcolor="#f3f3f3">画像の最大データサイズ<br />
		<span class="kn_text">(1～15 MB)</span>
		</td>
        <td>
        <!--{assign var=key value="product_img_max_size"}-->
            <span class="red"><!--{$arrErr[$key]}--></span><br />
            <input type="text" name="product_img_max_size" value="<!--{$arrForm.product_img_max_size|h}-->" maxlength="4" style="<!--{if $arrErr.retention_period != ""}-->
            background-color: <!--{$smarty.const.ERR_COLOR}-->;<!--{/if}-->" size="4" class="box6" /> MB
        </td>
    </tr>
    <tr>
        <td bgcolor="#f3f3f3">画像の最大幅<br />
		<span class="kn_text">(1～9999px)</span>
		</td>
        <td>
        <!--{assign var=key value="product_img_max_width"}-->
            <span class="red"><!--{$arrErr[$key]}--></span><br />
            <input type="text" name="product_img_max_width" value="<!--{$arrForm.product_img_max_width|h}-->" maxlength="4" style="<!--{if $arrErr.retention_period != ""}-->
            background-color: <!--{$smarty.const.ERR_COLOR}-->;<!--{/if}-->" size="4" class="box6" /> px
        </td>
    </tr>
    <tr>
        <td bgcolor="#f3f3f3">画像の最大の高さ<br />
		<span class="kn_text">(1～9999px)</span></td>
        <td>
        <!--{assign var=key value="product_img_max_height"}-->
            <span class="red"><!--{$arrErr[$key]}--></span><br />
            <input type="text" name="product_img_max_height" value="<!--{$arrForm.product_img_max_height|h}-->" maxlength="4" style="<!--{if $arrErr.retention_period != ""}-->
            background-color: <!--{$smarty.const.ERR_COLOR}-->;<!--{/if}-->" size="4" class="box6" /> px
        </td>
    </tr>
</table>
<!--{assign var=key value="update_failure"}-->
<!--{if $arrErr[$key]}-->
<span class="red">更新に失敗しました</span><br />
<!--{/if}-->
<div class="btn-area">
    <ul>
        <li>
            <a class="btn-action" href="javascript:void(0);" onclick="return ShareSubmit(document.form1,'form1');"><span class="btn-next">更新する</span></a>
        </li>
    </ul>
</div>

</form>


<h2><a name="resize">リサイズ可能のサイズ一覧</a></h2>
<div class="kn_explanation">
<!--{$smarty.const.HTTPS_URL}-->resize_image.php を使用するとき、下記リストの指定サイズのみスケール変更が可能です。<br />
これは、外部から無差別な値の幅高さをリクエストしたとき、リクエストしただけ画像を作らさせないためです。
</div>
<!--{assign var=key value="insert_failure"}-->
<!--{if $aErrAddSize[$key] != ""}-->
<span class="red"><!--{$aErrAddSize[$key]}--></span><br />
<!--{/if}-->
<table border="0" cellspacing="1" cellpadding="8">
    <tr bgcolor="#f3f3f3">
        <td style="text-align: center;"><strong>幅 (px)</strong></td>
        <td style="text-align: center;"><strong>高さ (px)</strong></td>
        <td></td>
    </tr>
    <form name="fmAdd" id="fmAdd" method="post" action="<!--{$smarty.server.REQUEST_URI|h}-->" onsubmit="return ShareSubmit(document.form1,'fmAdd');" >
    <input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
    <input type="hidden" name="mode" value="add">
    <tr bgcolor="#FFFFCC">
        <td><!--{assign var=key value="width"}-->
            <span class="red"><!--{$aErrAddSize[$key]}--></span><br />
            <input type="text" name="width" value="<!--{$aAddSize.width|h}-->" maxlength="<!--{$smarty.const.PERCENTAGE_LEN}-->" style="<!--{if $aErrAddSize.width != ""}-->
            background-color: <!--{$smarty.const.ERR_COLOR}-->;<!--{/if}-->" size="4" class="box6" /></td>
        <td>
            <!--{assign var=key value="height"}-->
            <span class="red"><!--{$aErrAddSize[$key]}--></span><br />
            <input type="text" name="height" value="<!--{$aAddSize.height|h}-->" maxlength="<!--{$smarty.const.PERCENTAGE_LEN}-->" style="<!--{if $aErrAddSize.height != ""}-->
            background-color: <!--{$smarty.const.ERR_COLOR}-->;<!--{/if}-->" size="4" class="box6" /></td>
        <td style="text-align: center;">
            <input name="" type="submit" value="追加" />
        </td>
    </tr>
    </form>
    
    <form name="frmDel" id="frmDel" method="post" action="<!--{$smarty.server.REQUEST_URI|h}-->" onsubmit="return ShareSubmit(document.form1,'fmAdd');" >
    <input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
    <input type="hidden" name="mode" value="del">
    <input type="hidden" name="width" value="">
    <input type="hidden" name="height" value="">
    <!--{foreach from=$aSizeList item=arrColumn}-->
    <tr>
        <td bgcolor="#FAFAFA"><!--{$arrColumn.width}--> </td>         
        <td><!--{$arrColumn.height}--> </td>
        <td style="text-align: center;">
            <!--{if $arrColumn.not_del == 0}-->
                <input type="submit" value="削除" onclick="DeleteSizeKn(<!--{$arrColumn.width}-->,<!--{$arrColumn.height}-->);"/>
            <!--{else}-->
            <span class="red">削除不可</span>
            <!--{/if}-->
        </td>
    </tr>    
    <!--{/foreach}-->
    </form>
</table>
<br />
<br />
<br />

<!--{/if}-->

<!--{include file="`$smarty.const.TEMPLATE_ADMIN_REALDIR`admin_popup_footer.tpl"}-->
