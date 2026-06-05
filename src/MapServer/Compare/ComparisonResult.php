<?php

namespace GisClient\MapServer\Compare;

/**
 * Per-file verdicts of a legacy-vs-optimized mapfile writer comparison.
 */
class ComparisonResult
{
    public const IDENTICAL = 'identical';
    public const DIFFERS = 'differs';
    public const ONLY_IN_LEGACY = 'only-in-legacy';
    public const ONLY_IN_OPTIMIZED = 'only-in-optimized';
    public const EXPECTED_REMOVED = 'expected-removed';

    /**
     * relative path => one of the verdict constants
     *
     * @var array<string, string>
     */
    private $verdicts = [];

    public function add($relativePath, $verdict)
    {
        $this->verdicts[$relativePath] = $verdict;
    }

    /**
     * @return array<string, string> relative path => verdict
     */
    public function getVerdicts()
    {
        return $this->verdicts;
    }

    /**
     * @param string $verdict one of the verdict constants
     * @return string[] relative paths
     */
    public function getPathsByVerdict($verdict)
    {
        return array_keys(array_filter($this->verdicts, fn ($v) => $v === $verdict));
    }

    /**
     * Unexpected = anything that is not byte-identical and not an
     * expected-removed cross-project template.
     *
     * @return bool
     */
    public function hasUnexpectedDifferences()
    {
        foreach ($this->verdicts as $verdict) {
            if ($verdict !== self::IDENTICAL && $verdict !== self::EXPECTED_REMOVED) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<string, int> verdict => count
     */
    public function getCounts()
    {
        $counts = [
            self::IDENTICAL => 0,
            self::DIFFERS => 0,
            self::ONLY_IN_LEGACY => 0,
            self::ONLY_IN_OPTIMIZED => 0,
            self::EXPECTED_REMOVED => 0,
        ];
        foreach ($this->verdicts as $verdict) {
            $counts[$verdict]++;
        }
        return $counts;
    }
}
