<?php

namespace GisClient\MapServer\Writer;

/**
 * Adapter around the legacy mapfile generation (gcMapfile).
 * Preserves the original \GCAuthor::refreshMapfile() behavior exactly.
 *
 * @deprecated Use OptimizedMapfileWriter instead.
 */
class LegacyMapfileWriter implements MapfileWriterInterface
{
    public function refreshMapset($project, $mapset, $publish, $layerMapfile)
    {
        include_once ADMIN_PATH . 'lib/functions.php';
        include_once ADMIN_PATH . 'lib/gcFeature.class.php';
        include_once ADMIN_PATH . 'lib/gcMapfile.class.php';
        include_once ROOT_PATH . 'lib/i18n.php';

        $target = $publish ? 'public' : 'tmp';

        if (!\GCAuthor::hasProject($project)) {
            throw new \Exception("Project '$project' does not exist.");
        }

        if (!\GCAuthor::hasProjectWithMapset($project, $mapset)) {
            throw new \Exception("Project '$project' does not have a mapset named '$mapset'.");
        }

        $mapfile = new \gcMapfile(null, $target);
        $mapfile->writeMap('mapset', $mapset);

        if ($layerMapfile) {
            foreach (\GCAuthor::getLayerList($project, $mapset) as $mapsetData) {
                $mapfile = new \gcMapfile(null, 'layer');
                $mapfile->writeMap('layer', "{$mapset}.{$mapsetData['feature_type']}");
            }
        }

        $localization = new \GCLocalization($project);
        $alternativeLanguages = $localization->getAlternativeLanguages();
        if ($alternativeLanguages) {
            foreach (array_keys($alternativeLanguages) as $languageId) {
                $mapfile = new \gcMapfile($languageId, $target);
                $mapfile->writeMap('mapset', $mapset);

                if ($layerMapfile) {
                    foreach (\GCAuthor::getLayerList($project, $mapset) as $mapsetData) {
                        $mapfile = new \gcMapfile($languageId, 'layer');
                        $mapfile->writeMap('layer', "{$mapset}.{$mapsetData['feature_type']}");
                    }
                }
            }
        }
    }
}
