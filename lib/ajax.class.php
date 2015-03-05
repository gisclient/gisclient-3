<?php

class GCAjax {
	function __construct() {
	}
	
	public function success($data = array()) {
		die(json_encode(array_merge(array('result'=>'ok'), $data)));
	}
	
	public function error($error = 'System Error') {
		die(json_encode(array('result'=>'error', 'error'=>$error)));
	}
}