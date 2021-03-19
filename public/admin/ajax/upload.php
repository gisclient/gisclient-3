<?php
include_once "../../../config/config.php";

if ( 0 < $_FILES['file']['error'] ) {
  error_log("Error while loading file");
  echo 'Error: ' . $_FILES['file']['error'] . '<br>';
} else
  error_log("Uploading file ".ADMIN_PATH.'export/' . $_FILES['file']['name']);
  move_uploaded_file($_FILES['file']['tmp_name'], ADMIN_PATH.'export/' . $_FILES['file']['name']);
?>
