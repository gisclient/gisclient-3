<?php
require "../config/config.php";
header("Cache-Control: no-cache, must-revalidate, private, pre-check=0, post-check=0, max-age=0");
$user = new GCUser();
$user->logout();
session_destroy();
header("HTTP/1.1 401 Unauthorized");
$referer = $_SERVER["HTTP_REFERER"];
echo '<html><meta http-equiv="refresh" content="0;url=/gisclient3/session.php?referer='.$referer.'"></html>';
die();
?>
