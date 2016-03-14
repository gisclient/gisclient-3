<?php
if (!file_exists("../config/config.php")) die ("Manca setup");
include_once "../config/config.php";

header("Content-Type: text/html; Charset=".CHAR_SET);
header("Cache-Control: no-cache, must-revalidate, private, pre-check=0, post-check=0, max-age=0");
header("Expires: " . gmdate('D, d M Y H:i:s', time()) . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Pragma: no-cache");

$user = new GCUser();

if(!empty($_REQUEST["logout"])) {
    $user->logout();
}

if(!empty($_POST['username']) && !empty($_POST['password'])) {
    $user->login($_POST['username'], md5($_POST['password']));
}

$db = GCApp::getDB();
$dbSchema=DB_SCHEMA;
$sql="SELECT distinct mapset_name,mapset_title,mapset_extent,project_name,template,project_title,private FROM $dbSchema.mapset INNER JOIN $dbSchema.project using(project_name) order by mapset_title,mapset_name;";
$res = $db->query ($sql);

$mapset=array();
while($row = $res->fetch()){
	$mapset[$row["project_name"]][]=Array("name"=>$row["mapset_name"],
		"title"=>$row["mapset_title"],"template"=>$row["template"],
		"extent"=>$row["mapset_extent"],'private'=>$row['private'],
		'project_title'=>$row["project_title"]);
}

$newTable = '';
foreach($mapset as $key=>$map){
	$newTable.='
		<div>
			<div class="tableHeader ui-widget ui-widget-header ui-corner-top">'.GCAuthor::t('project').': '.$map[0]['project_title'].'</div>
			<table class="stiletabella">';
				for($j=0;$j<count($map);$j++){
					if(!isset($_SESSION["USERNAME"]) && $map[$j]['private'] == 1) {
						continue;
					}
					
					$publicLink = MAP_URL;
					if(!empty($map[$j]['template'])){
						$publicLink .= $map[$j]['template'];
					}
					$separator = strpos($publicLink, '?')?'&':'?';
					$publicLink .= $separator.'mapset='.$map[$j]['name'];
					
					if (defined('PRIVATE_MAP_URL')) {
						$privateLink = PRIVATE_MAP_URL;
						if(!empty($map[$j]['template'])){
							$privateLink .= $map[$j]['template'];
						}
						$separator = strpos($privateLink, '?')?'&':'?';
						$privateLink .= $separator.'mapset='.$map[$j]['name'];
					}
					
					$newTable.='
						<tr>';
					if(empty($map[$j]['private'])) {
						$newTable .= '<td width="1"><a href="'.$publicLink.'" class="view" target="_blank">Public map</a></td>';
					} else {
						$newTable .= '<td width="1"></td>';
					}
					if(!empty($_SESSION['USERNAME']) && defined('PRIVATE_MAP_URL')) $newTable .= '
						<td width="1"><a href="'.$privateLink.'" class="private" target="_blank">Private map</a></td>';
					$newTable .= '					
							<td class="data">'.$map[$j]["title"].'</td>
						</tr>';
				}
			$newTable.='
			</table>
		</div>
	';
}

if(!$user->isAuthenticated()){
	$logTitle="Login";
	$logJs="javascript:return encript_pwd('password','frm_enter');";
	$logout=0;
	$btn="Login";
	$usrEnabled="";
	$pwdEnabled="";
}
else{
	if(!empty($_REQUEST['to'])) {
		header('Location: '.$_REQUEST['to']);
		die();
	}
	$logTitle="Logout";
	$logJs="";
	$logout=1;
	$btn="Esci";
	$usrEnabled="disabled";
	$pwdEnabled="disabled";
}
?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Maps</title>
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=<?php echo CHAR_SET ?>">
	<LINK media="screen" href="admin/css/styles.css" type="text/css" rel="stylesheet">
	<link type="text/css" href="admin/css/jquery-ui/start/jquery-ui-1.8.16.custom.css" rel="stylesheet" />
	<script type="text/javascript" src="./admin/js/jquery/jquery.js"></script>
	<script type="text/javascript" src="./admin/js/jquery/jquery-ui.js"></script>
	<script type="text/javascript" src="./admin/js/jquery/layout.js"></script>
	<script language="javascript" src="./admin/js/administrator.js" type="text/javascript"></script>
	<script  type="text/javascript">
		function showMaps(img,id){
			if($(id).style.display=='none'){
				img.src='admin/images/plus.gif';
				$(id).style.display='';
			}
			else{
				img.src='admin/images/minus.gif';
				$(id).style.display='none';
			}
		}
		$(document).ready(function() {
			
			/* jquerylayout */
			myLayout = $('#container').layout({
				north: { size: 90, spacing_open: 10, closable: false, resizable: false },
				east: { size: 250, maxSize: 500, spacing_open: 10, closable: true, resizable: false, initClosed: <?php echo $user->isAuthenticated() ? 'true' : 'false'; ?> },
				south: { size: 20, spacing_open: 10, closable: false, resizable: false }
				//useStateCookie: true,
				//cookie: { name: "GisClientAuthor", expires: 10, keys: "west.size" }
			});
			
			/* ui buttons */
			$('a.button , input[type|="button"] , input[type|="submit"]').button();
			$('a.logout').button({icons: { primary: 'ui-icon-power' }});
			$('.stiletabella a.view').button({icons: { primary: 'ui-icon-unlocked' },text: false});
			$('.stiletabella a.private').button({icons: { primary: 'ui-icon-locked' },text: false});
			
			/* ui alert & info */
			$('span.alert , span.error').addClass('ui-state-error ui-corner-all').prepend('<span class="ui-icon ui-icon-alert" style="float: left; margin-right: .5em;"></span>');
			$('span.info').addClass('ui-state-highlight ui-corner-all').prepend('<span class="ui-icon ui-icon-info" style="float: left; margin-right: .5em;"></span>');
		});
	</script>
</head>
<body>
<div id="container">
	<div class="ui-layout-north">
		<?php include ADMIN_PATH."inc/inc.admin.page_header.php"; ?>
	</div>
	<div class="ui-layout-center">
		<h2><?php echo GCAuthor::t('List of available Maps'); ?></h2>
		<?php echo $newTable;?>
	</div>
	<div class="ui-layout-east" id="container_login2">
		<h2><?php echo $logTitle;?></h2>
		<form action="<?php echo $_SERVER["PHP_SELF"]?>" method="post" class="riquadro" id="frm_enter" onsubmit="">
			<?php
			/*
			messaggio di errore login?
			
			<div class="formRow">
				<label>&nbsp;</label>
				<?php if (isset($message)) echo "<span class=\"alert\">".$message."</span>";?>
			</div>*/
			?>
			<div class="formRow">
				<label><?php echo GCAuthor::t('Username'); ?>:</label>
				<input name="username" type="text" id="username" value="" tabindex=1 <?php echo $usrEnabled?>>
			</div>
			<div class="formRow">
				<label><?php echo GCAuthor::t('Password'); ?>:</label>
				<input name="password" type="password" id="password" tabindex=2 <?php echo $pwdEnabled?>>
			</div>
			<div class="formRow">
				<input type="submit" class="submit" name="azione" value="<?php echo $btn;?>" tabindex="3" onclick="<?php echo $logJs;?>">
			</div>
		</form>
	</div>
	<div class="ui-layout-south">
		GisClient<span class="color">Author </span>
        <?php 
        $sql="SELECT version_name FROM {$dbSchema}.vista_version ORDER BY version_id DESC LIMIT 1"; 
        $res = $db->query($sql);
        echo $res->fetchColumn(0);
        ?>
	</div>
</div>
</body>
</html>
