<?php

namespace GisClient\MapServer\Writer;

/**
 * Adapter around the legacy mapfile generation (\GCAuthor / gcMapfile).
 * Zero behavior change: delegates to \GCAuthor::refreshMapfile().
 */
class LegacyMapfileWriter implements MapfileWriterInterface
{
    public function refreshMapset($project, $mapset, $publish, $layerMapfile)
    {
        \GCAuthor::refreshMapfile($project, $mapset, $publish, $layerMapfile);
    }
}
