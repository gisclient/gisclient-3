<?php

include __DIR__."/../../config/config.db.php";

class AppTest extends PHPUnit_Framework_TestCase {
    public function testConnection() {
        $connection = GCApp::getDB();
        $this->assertTrue($connection instanceof PDO);
    }
}

