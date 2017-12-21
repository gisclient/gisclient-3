<?php
if (!file_exists("../config/config.php")) die ("Manca setup");
include_once "../config/config.php";

header("Content-Type: text/html; Charset=".CHAR_SET);
header("Cache-Control: no-cache, must-revalidate, private, pre-check=0, post-check=0, max-age=0");
header("Expires: " . gmdate('D, d M Y H:i:s', time()) . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Pragma: no-cache");
header("HTTP/1.0 401 Unauthorized");
?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Maps</title>
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=<?php echo CHAR_SET ?>">
</head>
<body>
<div id="container">
	<div class="ui-layout-center">
		<h2><?php echo http_response_code().': Accesso non consentito alla risorsa. Validare canale di comunicazione o procedere ad accesso al sito.';?></h2>
	</div>
	<div class="ui-layout-east" id="container_login2">
	</div>
	<div class="ui-layout-south">
	</div>
</div>
</body>
</html>
