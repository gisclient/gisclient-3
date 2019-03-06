<?php

namespace GisClient\Author\Offline;

use GisClient\Author\LayerLevelInterface;
use GisClient\Author\Map;
use GisClient\Author\Utils\SavedFilterHandler;
use GisClient\Author\Utils\TemporaryFileService;
use Symfony\Component\Filesystem\Filesystem;

class SavedFilterData implements OfflineDataInterface
{
    private $tmpService;

    public function __construct(TemporaryFileService $tmpService)
    {
        $this->tmpService = $tmpService;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'saved_filter';
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function supports(LayerLevelInterface $layer)
    {
        return $layer instanceof Map;
    }

    /**
     * {@inheritdoc}
     */
    public function getState(LayerLevelInterface $layer)
    {
        return self::IS_STOPPED;
    }

    /**
     * {@inheritdoc}
     */
    public function getProgress(LayerLevelInterface $layer)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(LayerLevelInterface $layer)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function start(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function stop(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function clear(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getOfflineFiles(LayerLevelInterface $layer)
    {
        $fs = new Filesystem();
        $handler = new SavedFilterHandler();
        $filters = $handler->getList($layer->getName());
        $savedFilter = $this->tmpService->create(sprintf('offline_%s', $this->getName()));
        $fs->dumpFile($savedFilter, json_encode($filters));

        return [
            ['file' => $savedFilter, 'filename' => 'saved_filter.json'],
        ];
    }
}
