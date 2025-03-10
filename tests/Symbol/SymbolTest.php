<?php

use GisClient\Author\Symbol;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . "/../../bootstrap.php";

class SymbolTest extends TestCase
{
    public function testSymbolList()
    {
        $symbol = new Symbol('symbol');
        $symbolList = $symbol->getList();
        $this->assertTrue(count($symbolList) > 0);
    }
    
    public function testSymbolImage()
    {
        $symbol = new Symbol('symbol');
        $symbolList = $symbol->getList(true);
        foreach ($symbolList['values'] as $symbolInfo) {
            $symbol->filter = "symbol.symbol_name='{$symbolInfo['symbol']}'";
            $img = $symbol->createIcon();
            $this->assertNotNull($img);
        }
    }
}
