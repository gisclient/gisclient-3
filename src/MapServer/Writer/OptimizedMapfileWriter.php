<?php

namespace GisClient\MapServer\Writer;

/**
 * Optimized mapfile writer.
 *
 * Port of \GCAuthor::refreshMapfile() (lib/gcapp.class.php) that generates
 * the same files with ~10 batched queries per mapset instead of several
 * queries per layer/class (see MapfileDataCache / MapfileGenerator).
 *
 * Output equality with the LegacyMapfileWriter can be verified with
 * `gisclient:refresh-mapfile --compare`.
 */
class OptimizedMapfileWriter implements MapfileWriterInterface
{
    public function refreshMapset($project, $mapset, $publish, $layerMapfile)
    {
        include_once ADMIN_PATH . 'lib/functions.php';
        include_once ROOT_PATH . 'lib/i18n.php';

        $target = $publish ? 'public' : 'tmp';

        if (!\GCAuthor::hasProject($project)) {
            throw new \Exception("Project '$project' does not exist.");
        }

        if (!\GCAuthor::hasProjectWithMapset($project, $mapset)) {
            throw new \Exception("Project '$project' does not have a mapset named '$mapset'.");
        }

        // one shared prefetch cache per refresh: the language variants and the
        // per-layer mapfiles reuse the rows loaded for the first writeMap call
        $cache = new MapfileDataCache(\GCApp::getDB());
        $cache->loadTemplateData($project);

        $mapfile = new MapfileGenerator($cache, null, $target);
        $mapfile->writeMap('mapset', $mapset);

        if ($layerMapfile) {
            foreach (\GCAuthor::getLayerList($project, $mapset) as $mapsetData) {
                $mapfile = new MapfileGenerator($cache, null, 'layer');
                $mapfile->writeMap('layer', "{$mapset}.{$mapsetData['feature_type']}");
            }
        }

        $localization = new \GCLocalization($project);
        $alternativeLanguages = $localization->getAlternativeLanguages();
        if ($alternativeLanguages) {
            foreach (array_keys($alternativeLanguages) as $languageId) {
                $mapfile = new MapfileGenerator($cache, $languageId, $target);
                $mapfile->writeMap('mapset', $mapset);

                if ($layerMapfile) {
                    foreach (\GCAuthor::getLayerList($project, $mapset) as $mapsetData) {
                        $mapfile = new MapfileGenerator($cache, $languageId, 'layer');
                        $mapfile->writeMap('layer', "{$mapset}.{$mapsetData['feature_type']}");
                    }
                }
            }
        }
    }
}
