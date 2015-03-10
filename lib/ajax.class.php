<?php

class GCAjax {
	function __construct() {
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