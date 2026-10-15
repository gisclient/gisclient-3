<?php

namespace GisClient\MapServer\Writer;

/**
 * Mapfile refresh shared by the `refresh_mapfile` and `api/refresh-mapfile` routes.
 */
class MapfileRefresher implements MapfileRefresherInterface
{
    /**
     * @var MapfileWriterInterface
     */
    private $writer;

    public function __construct(MapfileWriterInterface $writer)
    {
        $this->writer = $writer;
    }

    public function refresh(string $project, ?string $mapset, bool $publish): void
    {
        if (defined('PROJECT_MAPFILE') && PROJECT_MAPFILE) {
            \GCAuthor::refreshProjectMapfile($project, $publish);
        } else {
            $refreshLayerMapfile = defined('ENABLE_OGC_SINGLE_LAYER_WMS') && ENABLE_OGC_SINGLE_LAYER_WMS === true;
            if (null === $mapset) {
                if (!\GCAuthor::hasProject($project)) {
                    throw new \Exception("Project '$project' does not exist.");
                }
                foreach (\GCAuthor::getMapsets($project) as $mapsetData) {
                    $this->writer->refreshMapset($project, $mapsetData['mapset_name'], $publish, $refreshLayerMapfile);
                }
            } else {
                $this->writer->refreshMapset($project, $mapset, $publish, $refreshLayerMapfile);
            }
        }
        $errors = \GCError::get();
        if (!empty($errors)) {
            throw new \Exception("GCErrors:\n" . implode("\n", $errors));
        }
    }
}
