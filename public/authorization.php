<?php
require "../config/config.php";

$user = new GCUser();
header("Location:".$_SERVER['REQUEST_URI'], true, 303);
die();

?>
