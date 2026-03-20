<?php

/**
 * Stub file for MapScript functions to help PHPStan recognize them.
 * Add this file to your `phpstan.neon` config under `bootstrapFiles`.
 */

if (!class_exists('mapObj')) {
    class mapObj
    {
        public function __construct(string $mapfile) {}
        public function draw() {}
        public function save(string $filename) {}
        public function getLayerByName(string $name): ?layerObj {}
    }
}

if (!class_exists('layerObj')) {
    class layerObj
    {
        public function __construct(mapObj $map) {}
        public function draw(mapObj $map, imageObj $image) {}
        public function set(string $property, string $value) {}
    }
}

if (!class_exists('classObj')) {
    class classObj
    {
        public function __construct(layerObj $layer) {}
    }
}

if (!class_exists('styleObj')) {
    class styleObj
    {
        public function __construct(classObj $class) {}
    }
}

if (!class_exists('symbolObj')) {
    class symbolObj
    {
        public function __construct(mapObj $map, string $symbolName) {}
    }
}

if (!class_exists('imageObj')) {
    class imageObj
    {
        public function save(string $filename) {}
    }
}

if (!class_exists('owsRequestObj')) {
    class owsRequestObj
    {
        public function setParameter(string $name, string $value) {}
    }
}

if (!function_exists('ms_newMapObj')) {
    function ms_newMapObj(string $filename): mapObj { return new mapObj($filename); }
}
if (!function_exists('ms_newMapObjFromString')) {
    function ms_newMapObjFromString(string $mapString, ?string $newMapPath = null): mapObj { return new mapObj(''); }
}
if (!function_exists('ms_newLayerObj')) {
    function ms_newLayerObj(mapObj $map): layerObj { return new layerObj($map); }
}
if (!function_exists('ms_newClassObj')) {
    function ms_newClassObj(layerObj $layer): classObj { return new classObj($layer); }
}
if (!function_exists('ms_newStyleObj')) {
    function ms_newStyleObj(classObj $class): styleObj { return new styleObj($class); }
}
if (!function_exists('ms_newSymbolObj')) {
    function ms_newSymbolObj(mapObj $map, string $symbolName): symbolObj { return new symbolObj($map, $symbolName); }
}
if (!function_exists('ms_newOwsrequestObj')) {
    function ms_newOwsrequestObj(): owsRequestObj { return new owsRequestObj(); }
}
if (!function_exists('ms_newImageObj')) {
    function ms_newImageObj(int $width, int $height): imageObj { return new imageObj(); }
}
