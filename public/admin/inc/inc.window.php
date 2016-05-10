<!--<div id="dwindow" style="position:absolute;background-color:#EBEBEB;cursor:hand;left:0px;top:0px;display:none;z-index:0">-->
<div id="dwindow" style="position:absolute;background-color:#EBEBEB;cursor:hand;left:0px;top:0px;display:none">
	<div align="right" style="background-color:navy"><img src="images/wclose.gif" onClick="closeWin()"></div>
	<div id="dwindowcontent" style="height:100%">
		<iframe id="cframe" src="" width=100% height=100%></iframe>
	</div>
	<div id="i18n_dialog">
		<table cellpadding="5">
		</table>
		<input type="button" name="submit" value="<?php echo GCAuthor::t('button_save') ?>">
	</div>
</div>