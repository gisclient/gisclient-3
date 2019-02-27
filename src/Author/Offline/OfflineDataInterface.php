<?php

namespace GisClient\Author\Offline;

use GisClient\Author\Map;
use GisClient\Author\LayerLevelInterface;

interface OfflineDataInterface
{
    const IS_TODO = 'to-do';

    const IS_RUNNING = 'running';

    const IS_STOPPED = 'stopped';

    /**
     * Return name of offline data format
     *
     * @return string
     */
    public function getName();

    /**
     * Check if theme supports this offline format
     *
     * @param LayerLevelInterface $layer
     * @return boolean
     */
    public function supports(LayerLevelInterface $layer);

    /**
     * Check if offline data already exists for the theme
     *  could be different foreach map, because of the extent
     *
     * @param Map $map
     * @param LayerLevelInterface $layer
     * @return string
     */
    public function exists(Map $map, LayerLevelInterface $layer);

    /**
     * Start generation of offline data
     *
     * @param Map $map
     * @param LayerLevelInterface $layer
     * @return void
     */
    public function start(Map $map, LayerLevelInterface $layer);

    /**
     * Stop generation of offline data
     *
     * @param Map $map
     * @param LayerLevelInterface $layer
     * @return void
     */
    public function stop(Map $map, LayerLevelInterface $layer);

    /**
     * Delete offline data
     *
     * @param Map $map
     * @param LayerLevelInterface $layer
     * @return void
     */
    public function clear(Map $map, LayerLevelInterface $layer);

    /**
     * Get current state of offline data
     *
     * @param Map $map
     * @param LayerLevelInterface $layer
     * @return string
     */
    public function getState(Map $map, LayerLevelInterface $layer);

    /**
     * Get current progress of offline data generation
     *
     * @param Map $map
     * @param LayerLevelInterface $layer
     * @return int|null
     */
    public function getProgress(Map $map, LayerLevelInterface $layer);

    /**
     * Get list of offline files
     *
     * @param Map $map
     * @param LayerLevelInterface $layer
     * @return string[]
     */
    public function getOfflineFiles(Map $map, LayerLevelInterface $layer);
}
