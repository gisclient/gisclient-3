<?php
require "../config/config.php";

$user = new GCUser();
header("Location:".$_SERVER['REQUEST_URI'], yes, 303);
die();

?>
