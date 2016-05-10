<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Author</title>
	<LINK media="screen" href="css/styles.css" type="text/css" rel="stylesheet">
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=<?php echo CHAR_SET ?>">
	<link type="text/css" href="css/jquery-ui/start/jquery-ui-1.8.16.custom.css" rel="stylesheet" />
	<script type="text/javascript" src="js/jquery/jquery.js"></script>
	<script type="text/javascript" src="js/jquery/jquery-ui.js"></script>
	<script type="text/javascript" src="js/jquery/layout.js"></script>
	<script type="text/javascript" src="js/md5.js"></script>
	<script type="text/javascript" src="js/administrator.js"></script>
	<!--<script  type="text/javascript" src="./js/Author.js"></script>-->
	<script type="text/javascript">		
		$(document).ready(function() {
			
			/* jquerylayout */
			myLayout = $('#container_login').layout({
				north: { size: 55, spacing_open: 10, closable: false, resizable: false },
				//west: { size: 250, minSize: 250, maxSize: 500, spacing_open: 10, closable: false },
				south: { size: 20, spacing_open: 10, closable: false, resizable: false }
				//useStateCookie: true,
				//cookie: { name: "GisClientAuthor", expires: 10, keys: "west.size" }
			});
			
			/* ui buttons */
			$('a.button , input[type|="button"] , input[type|="submit"]').button();
			$('a.logout').button({icons: { primary: 'ui-icon-power' }});
			
			/* ui alert & info */
			$('span.alert , span.error').addClass('ui-state-error ui-corner-all').prepend('<span class="ui-icon ui-icon-alert" style="float: left; margin-right: .5em;"></span>');
			$('span.info').addClass('ui-state-highlight ui-corner-all').prepend('<span class="ui-icon ui-icon-info" style="float: left; margin-right: .5em;"></span>');
			
			/* login focus */
			document.getElementById('username').focus();
		});
	</script>
</head>
<body>
<div id="container_login">
	<div class="ui-layout-north">
		<?php
		include ADMIN_PATH."inc/inc.admin.page_header.php";
		?>
	</div>
	<div class="ui-layout-center">
		<h2>Accesso consentito agli utenti autorizzati.</h2>
		
		<form action="<?php echo $_SERVER["PHP_SELF"]?>" method="post" class="riquadro" id="frm_enter" onsubmit="">
			<div class="formRow">
				<label>&nbsp;</label>
				<?php if (isset($message)) echo "<span class=\"alert\">".$message."</span>";?>
			</div>
			<div class="formRow">
				<label>Utente:</label>
				<input name="username" type="text" id="username" tabindex=1>
			</div>
			<div class="formRow">
				<label>Password:</label>
				<input name="password" type="password" id="password" tabindex=2>
			</div>
			<div class="formRow">
				<label>&nbsp;</label>
				<input name="login" type="hidden" value="1" tabindex=2>
				<input type="submit" class="submit" name="azione" value="Entra" tabindex="3">
			</div>
		</form>
	</div>
	<div class="ui-layout-south">
		GisClient<span class="color">Author</span> - Configurazione progetti GisClient - &copy; 2011
	</div>
</div>
</body>
</html>
