<?php

namespace GisClient\Author\Offline;

use GisClient\Author\Layer;
use GisClient\Author\LayerLevelInterface;
use GisClient\Author\Utils\LookupUtils;
use Symfony\Component\Filesystem\Filesystem;

class LookupData implements OfflineDataInterface
{
    private $tmpDir;

    public function __construct($tmpDir)
    {
        $this->tmpDir = $tmpDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'lookup';
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
        return $layer instanceof Layer;
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
        $files = [];

        $fs = new Filesystem();
        $utils = new LookupUtils();
        $catalogId = $layer->getCatalogId();
        foreach ($layer->getFields() as $field) {
            $lookupTable = $field->getLookupTable();
            $lookupId = $field->getLookupId();
            $lookupName = $field->getLookupName();
            if ($catalogId && $lookupTable && $lookupId && $lookupName) {
                $json = json_encode($utils->getList($catalogId, $lookupTable, $lookupId, $lookupName));
                $lookupFile = $fs->tempnam($this->tmpDir, sprintf('offline_%s', $this->getName()));
                $fs->dumpFile($lookupFile, $json);
                $files[] = [
                    'file' => $lookupFile,
                    'filename' => sprintf('%d%s.json', $catalogId, $lookupTable)
                ];
            }
        }

        return $files;
    }
}