<?php

namespace GisClient\MapServer\Writer;

interface MapfileWriterInterface
{
    /**
     * Regenerate the mapfiles (and related mapproxy/tinyows/template files)
     * for the given project and mapset, including all language variants.
     *
     * @param string $project       The project_name
     * @param string $mapset        The mapset_name
     * @param bool   $publish       If false, generate a temporary mapfile (tmp. prefix)
     * @param bool   $layerMapfile  If true, additionally generate one mapfile per layer
     *
     * @throws \Exception
     */
    public function refreshMapset($project, $mapset, $publish, $layerMapfile);
}
