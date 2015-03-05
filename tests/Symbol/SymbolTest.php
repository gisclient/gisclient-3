<?php

include_once __DIR__."/../../config/config.php";
include_once ADMIN_PATH."lib/gcSymbol.class.php";

class SymbolTest extends PHPUnit_Framework_TestCase {
    public function testSymbolList() {
		$symbol = new Symbol('symbol');
		$symbolList = $symbol->getList();
        $this->assertTrue(count($symbolList) > 0);
    }
	
    public function testSymbolImage() {
		$symbol = new Symbol('symbol');
		$symbolList = $symbol->getList(true);
		foreach($symbolList['values'] as $symbolInfo) {
			$symbol->filter="symbol.symbol_name='{$symbolInfo['symbol']}'";
			$img = $symbol->createIcon();
			$this->assertNotNull($img);
		}
    }
}
