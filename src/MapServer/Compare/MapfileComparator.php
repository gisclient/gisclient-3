<?php

namespace GisClient\MapServer\Compare;

/**
 * Compares the files produced by two mapfile writer runs over the same
 * project directory (map/<project>/).
 *
 * Usage: snapshot() after the legacy run, snapshot() again after the
 * optimized run, then compare() the two snapshots.
 *
 * The only intentional output difference of the optimized writer is in
 * template_wms/: the legacy writer (gcMapfile::_writeTemplateWms) writes the
 * GetFeatureInfo templates of ALL projects into every project directory,
 * the optimized writer only writes the current project's ones. Files that
 * disappear and do not belong to the current project are classified as
 * EXPECTED_REMOVED.
 */
class MapfileComparator
{
    /**
     * Recursively read all files under $dir.
     *
     * @param string $dir absolute path of the project map directory
     * @return array<string, string> relative path => file content
     */
    public function snapshot($dir)
    {
        $snapshot = [];
        if (!is_dir($dir)) {
            return $snapshot;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $relativePath = ltrim(substr($file->getPathname(), strlen($dir)), '/');
            $snapshot[$relativePath] = (string) file_get_contents($file->getPathname());
        }
        ksort($snapshot);
        return $snapshot;
    }

    /**
     * @param array<string, string> $legacy           snapshot taken after the legacy run
     * @param array<string, string> $optimized        snapshot taken after the optimized run
     * @param string[]              $ownTemplateNames "<layergroup_name>.<layer_name>" keys of the
     *                                                current project's queryable template layers
     * @return ComparisonResult
     */
    public function compare(array $legacy, array $optimized, array $ownTemplateNames)
    {
        $result = new ComparisonResult();

        $paths = array_unique(array_merge(array_keys($legacy), array_keys($optimized)));
        sort($paths);

        foreach ($paths as $path) {
            if (array_key_exists($path, $legacy) && array_key_exists($path, $optimized)) {
                $result->add($path, $legacy[$path] === $optimized[$path]
                    ? ComparisonResult::IDENTICAL
                    : ComparisonResult::DIFFERS);
            } elseif (array_key_exists($path, $legacy)) {
                if ($this->isForeignTemplate($path, $ownTemplateNames)) {
                    $result->add($path, ComparisonResult::EXPECTED_REMOVED);
                } else {
                    $result->add($path, ComparisonResult::ONLY_IN_LEGACY);
                }
            } else {
                $result->add($path, ComparisonResult::ONLY_IN_OPTIMIZED);
            }
        }

        return $result;
    }

    /**
     * Save the legacy/optimized contents of every differing file below
     * $compareDir for manual inspection.
     *
     * @param array<string, string> $legacy
     * @param array<string, string> $optimized
     * @param string                $compareDir e.g. ROOT_PATH . 'map/.compare/<project>'
     * @return string[] written file paths
     */
    public function dumpDifferences(ComparisonResult $result, array $legacy, array $optimized, $compareDir)
    {
        $written = [];
        foreach ($result->getPathsByVerdict(ComparisonResult::DIFFERS) as $path) {
            foreach ([
                'legacy' => $legacy,
                'optimized' => $optimized,
            ] as $suffix => $snapshot) {
                $target = $compareDir . '/' . $path . '.' . $suffix;
                if (!is_dir(dirname($target))) {
                    mkdir(dirname($target), 0777, true);
                }
                file_put_contents($target, $snapshot[$path]);
                $written[] = $target;
            }
        }
        return $written;
    }

    /**
     * A template_wms file that does not belong to the current project.
     *
     * Template file names are "<layergroup_name>.<layer_name>[_<lang>].html"
     * plus the shared "header[_<lang>].html" / "footer[_<lang>].html".
     *
     * @param string   $path             path relative to the project map directory
     * @param string[] $ownTemplateNames
     * @return bool
     */
    private function isForeignTemplate($path, array $ownTemplateNames)
    {
        if (strpos($path, 'template_wms/') !== 0 || substr($path, -5) !== '.html') {
            return false;
        }
        $base = substr(basename($path), 0, -5); // strip ".html"

        // header/footer are shared and must stay identical
        if ($base === 'header' || $base === 'footer'
            || strpos($base, 'header_') === 0 || strpos($base, 'footer_') === 0) {
            return false;
        }

        foreach ($ownTemplateNames as $name) {
            if ($base === $name || strpos($base, $name . '_') === 0) {
                return false;
            }
        }

        return true;
    }
}
