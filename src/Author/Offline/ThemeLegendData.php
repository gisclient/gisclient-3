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

    public function getName()
    {
        return 'theme_legend';
    }

    public function getCommand(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    public function supports(LayerLevelInterface $layer)
    {
        return $layer instanceof Theme && $layer->getSymbolName() !== null;
    }

    public function getState(LayerLevelInterface $layer)
    {
        return self::IS_STOPPED;
    }

    public function getProgress(LayerLevelInterface $layer)
    {
        return null;
    }

    public function exists(LayerLevelInterface $layer)
    {
        return true;
    }

    public function start(LayerLevelInterface $layer, $runInBackground = true)
    {
        throw new \RuntimeException('Method not supported');
    }

    public function stop(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    public function clear(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    public function getOfflineFiles(LayerLevelInterface $layer)
    {
        $fs = new Filesystem();
        $symbolCreator = new SymbolCreator();
        $themeLegend = $this->tmpService->create(sprintf('offline_%s', $this->getName()));
        $fs->dumpFile($themeLegend, $symbolCreator->createSymbol('symbol', $layer->getSymbolName()));

        return [
            [
                'file' => $themeLegend,
                'filename' => sprintf('%s.png', $layer->getName()),
            ],
        ];
    }
}
