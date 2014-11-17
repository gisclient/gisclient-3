<?php

/**
 * GCService initialized correctly the pages used as a service within GisClient
 * Technically it is implemented as a Singleton.
 */
class GCService {
	private static $instance;
	
	private function __construct() {
		
	}
	
	/**
	 * Get an instance of service. There is axactly one instance of this class
	 * in the application context.
	 */
	static public function instance(){
		if (is_null(self::$instance)) {
			self::$instance = new GCService();
			self::$instance->setExceptionHandler();
		}
	}
	
	private function setExceptionHandler() {
		$handler = function(Exception $e) {
			print_debug($e->getMessage() . "\n" . $e->getTraceAsString(),
				null,'service');
			header("HTTP/1.0 500 Internal Server Error");
			echo $e->getMessage();
			exit(1);
		};
		set_exception_handler($handler);
	}
}
