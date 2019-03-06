<?php

namespace GisClient\Author\Offline;

use GisClient\Author\LayerLevelInterface;
use GisClient\Author\Theme;
use GisClient\Author\Utils\SymbolCreator;
use GisClient\Author\Utils\TemporaryFileService;
use Symfony\Component\Filesystem\Filesystem;

class ThemeLegendData implements OfflineDataInterface
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
        return 'theme_legend';
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
        return $layer instanceof Theme && $layer->getSymbolName() !== null;
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
        $symbolCreator = new SymbolCreator();
        $themeLegend = $this->tmpService->create(sprintf('offline_%s', $this->getName()));
        $fs->dumpFile($themeLegend, $symbolCreator->createSymbol('symbol', $layer->getSymbolName()));

        return [
            ['file' => $themeLegend, 'filename' => sprintf('%s.png', $layer->getName())],
        ];
    }
}
