<?php

include_once __DIR__."/../../config/config.php";
include_once ADMIN_PATH."lib/gcSymbol.class.php";

class SymbolTest extends PHPUnit_Framework_TestCase {
    public function testSymbol() {
		$symbol = new Symbol('symbol');
		$symbolList = $symbol->getList();
        $this->assertTrue(is_array($symbolList));
    }
}

