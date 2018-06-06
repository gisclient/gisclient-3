<?php
if(isset($_GET["variable"])) {
  include_once "../../config/config.ext.php";
  $reqVar = $_GET["variable"];
  echo defined($reqVar)? constant($reqVar) : "";
} else {
  include_once "../../config/config.php";
  $user = new GCUser();
  $result = $user->getClientConfiguration();
  $result["SCRIPT_PLUGINS"] = $SCRIPT_PLUGINS;
  echo json_encode($result);
}
?>
