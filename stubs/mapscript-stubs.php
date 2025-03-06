<?php

/**
 * Stub file for MapScript functions to help PHPStan recognize them.
 * Add this file to your `phpstan.neon` config under `bootstrapFiles`.
 */

class mapObj
{
    public function __construct(string $mapfile) {}
    public function draw() {}
    public function save(string $filename) {}
    public function getLayerByName(string $name): ?layerObj {}
}

class layerObj
{
    public function __construct(mapObj $map) {}
    public function draw(mapObj $map, imageObj $image) {}
    public function set(string $property, string $value) {}
}

class classObj
{
    public function __construct(layerObj $layer) {}
}

class styleObj
{
    public function __construct(classObj $class) {}
}

class symbolObj
{
    public function __construct(mapObj $map, string $symbolName) {}
}

class imageObj
{
    public function save(string $filename) {}
}

class owsRequestObj
{
    public function setParameter(string $name, string $value) {}
}

function ms_newMapObj(string $filename): mapObj { return new mapObj($filename); }
function ms_newMapObjFromString(string $mapString): mapObj { return new mapObj(''); }
function ms_newLayerObj(mapObj $map): layerObj { return new layerObj($map); }
function ms_newClassObj(layerObj $layer): classObj { return new classObj($layer); }
function ms_newStyleObj(classObj $class): styleObj { return new styleObj($class); }
function ms_newSymbolObj(mapObj $map, string $symbolName): symbolObj { return new symbolObj($map, $symbolName); }
function ms_newOwsrequestObj(): owsRequestObj { return new owsRequestObj(); }
function ms_newImageObj(int $width, int $height): imageObj { return new imageObj(); }

