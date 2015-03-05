<?php
header("Content-Type: application/json; Charset=UTF-8");
echo '{"scales":[{"name":"1:1,000","value":1000},{"name":"1:2,000","value":2000},{"name":"1:5,000","value":5000},{"name":"1:10,000","value":10000},{"name":"1:25,000","value":25000},{"name":"1:50,000","value":50000},{"name":"1:100,000","value":100000},{"name":"1:500,000","value":500000},{"name":"1:1,000,000","value":1000000},{"name":"1:1,500,000","value":1500000},{"name":"1:2,000,000","value":2000000},{"name":"1:2,500,000","value":2500000},{"name":"1:5,000,000","value":5000000},{"name":"1:10,000,000","value":10000000}],"dpis":[{"name":"75","value":"75"},{"name":"150","value":"150"},{"name":"300","value":"300"}],"layouts":[{"name":"A4","map":{"width":440,"height":600},"rotation":true},{"name":"Legal","map":{"width":440,"height":650},"rotation":true},{"name":"Letter","map":{"width":440,"height":550},"rotation":true}],"printURL":"/gisclient/services/gcprint.php","createURL":"/gisclient/services/gcprint.php?format=HTML"}'
//echo  json_encode($result,JSON_NUMERIC_CHECK);


?>