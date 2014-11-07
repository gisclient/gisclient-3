<?php

include_once __DIR__."/../../config/config.php";
include_once __DIR__."/../lib/HttpUtils.php";

class LoginTest extends PHPUnit_Framework_TestCase {
	
    public function testLoginFailure() {
		$cookieJar = GC_WEB_TMP_DIR.'test_cookies.txt';
		unlink($cookieJar);
		$postParameters = array(
			'username' => 'admin',
			'password' => 'wrongpassword',
			'azione'   => 'Entra',
		);
		$loginResult = HttpUtils::post(PUBLIC_URL.'index.php', $postParameters, $cookieJar);
		$this->assertEquals($loginResult[0], 200);
		$this->assertTrue(strpos($loginResult[1], 'LogOut') === false);
    }
	
    public function testLogin() {
		$cookieJar = GC_WEB_TMP_DIR.'test_cookies.txt';
		
		$postParameters = array(
			'username' => 'admin',
			'password' => 'gisclient',
			'azione'   => 'Entra',
		);
		$loginResult = HttpUtils::post(PUBLIC_URL.'index.php', $postParameters, $cookieJar);
		$this->assertEquals($loginResult[0], 200);
		$this->assertTrue(strpos($loginResult[1], 'LogOut') !== false);
    }
	
	
}

