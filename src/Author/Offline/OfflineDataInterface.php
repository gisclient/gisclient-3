<?php

namespace GisClient\Author\Offline;

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
     * Return the command to generate the offline data
     *
     * @return string
     */
    public function getCommand(LayerLevelInterface $layer);

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
     * @param LayerLevelInterface $layer
     * @return string
     */
    public function exists(LayerLevelInterface $layer);

    /**
     * Start generation of offline data
     *
     * @param LayerLevelInterface $layer
     * @param boolean $runInBackground
     * @return void
     */
    public function start(LayerLevelInterface $layer, $runInBackground = true);

    /**
     * Stop generation of offline data
     *
     * @param LayerLevelInterface $layer
     * @return void
     */
    public function stop(LayerLevelInterface $layer);

    /**
     * Delete offline data
     *
     * @param LayerLevelInterface $layer
     * @return void
     */
    public function clear(LayerLevelInterface $layer);

    /**
     * Get current state of offline data
     *
     * @param LayerLevelInterface $layer
     * @return string
     */
    public function getState(LayerLevelInterface $layer);

    /**
     * Get current progress of offline data generation
     *
     * @param LayerLevelInterface $layer
     * @return int|null
     */
    public function getProgress(LayerLevelInterface $layer);

    /**
     * Get list of offline files
     *
     * @param LayerLevelInterface $layer
     * @return string[]
     */
    public function getOfflineFiles(LayerLevelInterface $layer);
}
