<?php

namespace GisClient\Author;

abstract class AbstractLayerLevel implements LayerLevelInterface
{
    /**
     * Relative map
     *
     * @var Map
     */
    private $map;

    public function getMap()
    {
        return $this->map;
    }

    public function setMap(Map $map)
    {
        $this->map = $map;
    }
}
