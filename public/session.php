<?php
require "../config/config.php";
$sid = session_id();
if(!empty($sid)) {
  session_destroy();
}
session_name(GC_SESSION_NAME);
session_start();
if(isset($_GET['referer']))
  header("Location: ".$_GET['referer'], yes, 303);
else
  header("Location: ".PUBLIC_URL."index.php", yes, 303);
die();
?>
