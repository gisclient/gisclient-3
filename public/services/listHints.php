<?php
include_once "../../config/config.php";
include_once "../../lib/gcapp.class.php";

$fileName = GCAuthor::getHintsFileName();
$handle = fopen(ROOT_PATH.$fileName, "r");
$result = "";
if ($handle) {
  while (($line = fgets($handle)) !== false) {
    if(!(substr($line, 0, strlen("--")) === "--") && !(trim($line) === ""))
      $result .= processAndWriteSingleLine($line, $_POST["app"]);
  }
  fclose($handle);
}
echo $result;



function processAndWriteSingleLine($line, $expectedApp) {
  $arr = explode("#", $line);
  $currentDate = new DateTime(date("Y-m-d"));
  $expireDate = new DateTime($arr[0]);
  if($currentDate <= $expireDate) {
    $currentApp = $arr[1];
    if(strcmp($currentApp, $expectedApp) == 0) {
      echo (count($arr) == 5 && strcmp(trim($arr[4]),"")!=0) ? formatMessageWithLink($arr[2],$arr[3],$arr[4]) : formatMessage($arr[2], $arr[3]);
    }
  }
}

function formatMessage($key, $str) {
 return "<div id=\"$key\"><input type=\"checkbox\" name=\"$key\" value=\"$key\" onclick=\"checkBoxManagement('$key');\" title=\"Letto\"/> $str<hr></div>";
}

function formatMessageWithLink($key, $str, $link) {
 $begin = strpos($str , "@", 0) + 1;
 $end = strpos($str , "@", $begin);
 $arg = substr($str, 0, $begin - 1)."<a href=\"".$link."\" class=\"alert-link\">".substr($str, $begin, $end - $begin)."</a>".substr($str, $end+1);
 return formatMessage($key, $arg);
}

?>
