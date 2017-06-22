<?php

class GCAjax {
	function __construct() {
            header('Access-Control-Allow-Origin: *');
            header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
            header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
            header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
            header ("Pragma: no-cache"); // HTTP/1.0
	}
	
	public function success($data = array()) {
		if(isset($_REQUEST["callback"]))
			die($_REQUEST["callback"]."(".json_encode(array_merge(array('result'=>'ok'), $data)).")");
		else
			die(json_encode(array_merge(array('result'=>'ok'), $data)));
	}
	
	public function error($error = 'System Error') {

		if(isset($_REQUEST["callback"]))
			die($_REQUEST["callback"]."(".json_encode(array('result'=>'error', 'error'=>$error)).")");
		else
			die(json_encode(array('result'=>'error', 'error'=>$error)));
	}

}
