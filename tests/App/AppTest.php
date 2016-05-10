<?php

include __DIR__."/../../config/config.db.php";
include __DIR__."/../../lib/gcapp.class.php";

class AppTest extends PHPUnit_Framework_TestCase {
    public function testConnection() {
        $connection = GCApp::getDB();
        $this->assertTrue($connection instanceof PDO);
    }
}

