<?php

namespace GisClient\Author\Controller;

use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use GisClient\Author\Symbol;

class SymbolController
{
    /**
     * @var \PDO
     */
    private $database;

    public function __construct()
    {
        $this->database = \GCApp::getDB();
    }

    private function getOutputHeaders()
    {
        return [
            'Content-type' => 'image/png',
            'Cache-Control' => [
                'no-store, no-cache, must-revalidate', // HTTP/1.1
                'post-check=0, pre-check=0'
            ],
            'Pragma' => 'no-cache' // HTTP/1.0
        ];
    }
    
    /**
     * Get the symbol image
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSymbolAction(Request $request)
    {
        $table = $request->query->get('table');
        $id = $request->query->get('id');

        $symbol = new Symbol($table);
        switch ($symbol->table) {
            case 'class':
                $symbol->filter = "class.class_id=".$this->database->quote($id);
                break;
            case 'symbol':
                $symbol->filter = "symbol.symbol_name=".$this->database->quote($id);
                break;
        }

        $image = $symbol->createIcon();

        return new Response($image, Response::HTTP_OK, $this->getOutputHeaders());
    }
}
