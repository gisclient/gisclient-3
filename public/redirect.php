<?php
require "../config/config.php";
include_once ROOT_PATH."lib/redirectUrl.class.php";

$manager = new RedirectUrl();
$result = $manager->checkUrlRedirect($_SERVER['REQUEST_URI'],$_SERVER['QUERY_STRING']);
if ($result != 1) {
  $user = new GCUser();
  header("Location: ".PUBLIC_URL."unauthorized.php", yes, 303);
}
die();
?>
