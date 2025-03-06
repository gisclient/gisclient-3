<?php

use PHPUnit\Framework\TestCase;

include __DIR__."/../../config/config.db.php";

class AppTest extends TestCase {
    public function testConnection() {
        $connection = GCApp::getDB();
        $this->assertTrue($connection instanceof PDO);
    }
}

