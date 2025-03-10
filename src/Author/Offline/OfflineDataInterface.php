<?php

namespace GisClient\Author\Offline;

use GisClient\Author\LayerLevelInterface;

interface OfflineDataInterface
{
    public const IS_TODO = 'to-do';

    public const IS_RUNNING = 'running';

    public const IS_STOPPED = 'stopped';

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
     * @return boolean
     */
    public function supports(LayerLevelInterface $layer);

    /**
     * Check if offline data already exists for the theme
     *  could be different foreach map, because of the extent
     *
     * @return string
     */
    public function exists(LayerLevelInterface $layer);

    /**
     * Start generation of offline data
     *
     * @param boolean $runInBackground
     */
    public function start(LayerLevelInterface $layer, $runInBackground = true);

    /**
     * Stop generation of offline data
     */
    public function stop(LayerLevelInterface $layer);

    /**
     * Delete offline data
     */
    public function clear(LayerLevelInterface $layer);

    /**
     * Get current state of offline data
     *
     * @return string
     */
    public function getState(LayerLevelInterface $layer);

    /**
     * Get current progress of offline data generation
     *
     * @return int|null
     */
    public function getProgress(LayerLevelInterface $layer);

    /**
     * Get list of offline files
     *
     * @return string[]
     */
    public function getOfflineFiles(LayerLevelInterface $layer);
}
