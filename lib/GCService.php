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
		return self::$instance;
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
	
	public function startSession($allowTokenFromRequest = false) {
// for PHP >= 5.4, see http://php.net/manual/en/function.session-status.php
		if (session_id() === '') {
			// start the sessione
			if (defined('GC_SESSION_NAME')) {
				print_debug('set session name to ' . GC_SESSION_NAME, null, 'system');
				session_name(GC_SESSION_NAME);
			}
			if ($allowTokenFromRequest && isset($_REQUEST['GC_SESSION_ID']) && !empty($_REQUEST['GC_SESSION_ID'])) {
				print_debug('set session id to ' . $_REQUEST['GC_SESSION_ID'], null, 'system');
				session_id($_REQUEST['GC_SESSION_ID']);
			}
			print_debug('start new session', null, 'system');
			session_start();
		} else {
			print_debug('session already started', null, 'system');
		}
	}

}
