<?php

namespace GisClient\Author;

class OfflineMap
{
    /**
     * List of available offline data formats
     *
     * @var Offline\OfflineDataInterface[]
     */
    private $offlineDataFormats = [];

    /**
     * Constructor
     *
     * @param Offline\OfflineDataInterface[] $offlineDataFormats
     */
    public function __construct(\Traversable $offlineDataFormats)
    {
        $this->offlineDataFormats = $offlineDataFormats;
    }

    /**
     * Run the operation on the offline data format
     *
     * @param string $operation
     * @param LayerLevelInterface $layer
     * @param string|null $only
     */
    private function run($operation, LayerLevelInterface $layer, $only = null)
    {
        foreach ($this->offlineDataFormats as $offlineDataFormat) {
            $format = $offlineDataFormat->getName();

            // check the format has to be processed
            if ((empty($only) || $only === $format)
                && $offlineDataFormat->supports($layer)
            ) {
                call_user_func([$offlineDataFormat, $operation], $layer);
            }
        }

        $children = $layer->getChildren();
        foreach ($children as $layer) {
            $this->run($operation, $layer, $only);
        }
    }

    /**
     * Start offline data generation
     *
     * @param LayerLevelInterface $layer
     * @param string|null $only
     */
    public function start(LayerLevelInterface $layer, $only = null)
    {
        $this->run('start', $layer, $only);
    }

    /**
     * Stop offline data generation
     *
     * @param LayerLevelInterface $layer
     * @param string|null $only
     */
    public function stop(LayerLevelInterface $layer, $only = null)
    {
        $this->run('stop', $layer, $only);
    }

    /**
     * Delete offline data
     *
     * @param LayerLevelInterface $layer
     * @param string|null $only
     */
    public function clear(LayerLevelInterface $layer, $only = null)
    {
        $this->run('clear', $layer, $only);
    }

    /**
     * Get current status of offline data
     *
     * @param LayerLevelInterface $layer
     * @return array
     */
    public function status(LayerLevelInterface $layer)
    {
        $result = array();

        $category = strtolower(substr(strrchr(get_class($layer), '\\'), 1));
        if (!isset($result[$category])) {
            $result[$category] = [];
        }
        $hasOfflineData = false;
        $layerResult = [
            'name' => $layer->getName(),
            'title' => $layer->getTitle(),
        ];

        foreach ($this->offlineDataFormats as $offlineDataFormat) {
            $format = $offlineDataFormat->getName();
            $layerResult[$format] = [];

            // check the format has to be processed
            if ($offlineDataFormat->supports($layer)) {
                $hasOfflineData = true;
                $layerResult[$format] = [
                    'state' => $offlineDataFormat->getState($layer),
                    'progress' => $offlineDataFormat->getProgress($layer),
                ];
            }
        }

        if ($hasOfflineData) {
            $result[$category][] = $layerResult;
        }

        $children = $layer->getChildren();
        foreach ($children as $layer) {
            $result = array_merge_recursive($result, $this->status($layer));
        }

        return $result;
    }

    private function getOfflineFiles(LayerLevelInterface $layer, array $formats)
    {
        $files = [];

        foreach ($this->offlineDataFormats as $offlineDataFormat) {
            $format = $offlineDataFormat->getName();

            // check the format has to be processed
            if (in_array($format, $formats)
                && $formats[$format] === true
                && $offlineDataFormat->supports($layer)
            ) {
                // TODO: check if status is not running??

                $files = array_merge($files, $offlineDataFormat->getOfflineFiles($layer));
            }
        }

        $children = $layer->getChildren();
        foreach ($children as $layer) {
            $files = array_merge($files, $this->getOfflineFiles($layer, $formats));
        }

        return $files;
    }

    /**
     * Create zip containing the offline data
     *
     * @param LayerLevelInterface $layer
     * @param string $zipFile
     * @param array $formats
     */
    public function createZip(LayerLevelInterface $layer, $zipFile, array $formats = [])
    {
        $supportedFormats = [];
        foreach ($this->offlineDataFormats as $offlineDataFormat) {
            $supportedFormats[$offlineDataFormat->getName()] = true;
        }
        $requestFormats = array_merge($supportedFormats, $formats);

        $zip = new \ZipArchive();

        if ($zip->open($zipFile, \ZipArchive::CREATE) !== true) {
            throw new \Exception("Failed to create zip file '{$zipFile}'", 1);
        }

        // add data
        $files = $this->getOfflineFiles($layer, $requestFormats);
        foreach ($files as $file) {
            if (is_array($file)) {
                $zip->addFile($file['file'], $file['filename']);
            } else {
                $zip->addFile($file, basename($file));
            }
        }

        $zip->close();
    }
}
