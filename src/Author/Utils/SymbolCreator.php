<?php

namespace GisClient\Author\Utils;

use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use GisClient\Author\Symbol;

class SymbolCreator
{
    /**
     * @var \PDO
     */
    private $database;

    public function __construct()
    {
        $this->database = \GCApp::getDB();
    }
    
    /**
     * Get the symbol image
     *
     * @param string $table
     * @param integer $id
     * @return string
     */
    public function createSymbol($table, $id)
    {
        $symbol = new Symbol($table);
        switch ($symbol->table) {
            case 'class':
                $symbol->filter = "class.class_id=".$this->database->quote($id);
                break;
            case 'symbol':
                $symbol->filter = "symbol.symbol_name=".$this->database->quote($id);
                break;
        }

        return $symbol->createIcon();
    }
}
