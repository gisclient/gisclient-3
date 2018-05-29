<?
if(isset($_GET["variable"])) {
  include_once "../../config/config.ext.php";
  $reqVar = $_GET["variable"];
  echo defined($reqVar)? constant($reqVar) : "";
} else {
  include_once "../../config/config.php";
  $user = new GCUser();
  echo json_encode($user->getClientConfiguration());
}
?>
