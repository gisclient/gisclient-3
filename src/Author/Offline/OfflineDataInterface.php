<?php

namespace GisClient\Author\Offline;

use GisClient\Author\Map;
use GisClient\Author\Theme;

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
     * @param Theme $theme
     * @return boolean
     */
    public function supports(Theme $theme);

    /**
     * Check if offline data already exists for the theme
     *  could be different foreach map, because of the extent
     *
     * @param Map $map
     * @param Theme $theme
     * @return string
     */
    public function exists(Map $map, Theme $theme);

    /**
     * Start generation of offline data
     *
     * @param Map $map
     * @param Theme $theme
     * @return void
     */
    public function start(Map $map, Theme $theme);

    /**
     * Stop generation of offline data
     *
     * @param Map $map
     * @param Theme $theme
     * @return void
     */
    public function stop(Map $map, Theme $theme);

    /**
     * Delete offline data
     *
     * @param Map $map
     * @param Theme $theme
     * @return void
     */
    public function clear(Map $map, Theme $theme);

    /**
     * Get current state of offline data
     *
     * @param Map $map
     * @param Theme $theme
     * @return string
     */
    public function getState(Map $map, Theme $theme);

    /**
     * Get current progress of offline data generation
     *
     * @param Map $map
     * @param Theme $theme
     * @return int|null
     */
    public function getProgress(Map $map, Theme $theme);

    /**
     * Get list of offline files
     *
     * @param Map $map
     * @param Theme $theme
     * @return string[]
     */
    public function getOfflineFiles(Map $map, Theme $theme);
}
