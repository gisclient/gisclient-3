<?php

namespace GisClient\Author;

interface LayerLevelInterface
{
    /**
     * Return the machine readble name of the layer
     *
     * @return string
     */
    public function getName();

    /**
     * Return the human readable name of the layer
     *
     * @return string
     */
    public function getTitle();

    /**
     * Return the children of the layer
     *
     * @return LayerLevelInterface[]
     */
    public function getChildren();

    /**
     * Return the relative map
     *
     * @return Map|null
     */
    public function getMap();
}
