<?php

use GisClient\MapServer\Compare\ComparisonResult;
use GisClient\MapServer\Compare\MapfileComparator;
use PHPUnit\Framework\TestCase;

class MapfileComparatorTest extends TestCase
{
    /**
     * @var MapfileComparator
     */
    private $comparator;

    protected function setUp(): void
    {
        $this->comparator = new MapfileComparator();
    }

    public function testIdenticalSnapshots(): void
    {
        $snapshot = [
            'mapset.map' => 'MAP END',
            'template_wms/header.html' => '<html>',
        ];

        $result = $this->comparator->compare($snapshot, $snapshot, []);

        $this->assertFalse($result->hasUnexpectedDifferences());
        $this->assertSame(
            [
                ComparisonResult::IDENTICAL => 2,
                ComparisonResult::DIFFERS => 0,
                ComparisonResult::ONLY_IN_LEGACY => 0,
                ComparisonResult::ONLY_IN_OPTIMIZED => 0,
                ComparisonResult::EXPECTED_REMOVED => 0,
            ],
            $result->getCounts()
        );
    }

    public function testDifferingContentIsUnexpected(): void
    {
        $legacy = [
            'mapset.map' => 'MAP A END',
        ];
        $optimized = [
            'mapset.map' => 'MAP B END',
        ];

        $result = $this->comparator->compare($legacy, $optimized, []);

        $this->assertTrue($result->hasUnexpectedDifferences());
        $this->assertSame(['mapset.map'], $result->getPathsByVerdict(ComparisonResult::DIFFERS));
    }

    public function testMissingMapfileIsUnexpected(): void
    {
        $legacy = [
            'mapset.map' => 'MAP END',
        ];
        $optimized = [];

        $result = $this->comparator->compare($legacy, $optimized, []);

        $this->assertTrue($result->hasUnexpectedDifferences());
        $this->assertSame(['mapset.map'], $result->getPathsByVerdict(ComparisonResult::ONLY_IN_LEGACY));
    }

    public function testExtraFileFromOptimizedIsUnexpected(): void
    {
        $legacy = [];
        $optimized = [
            'mapset.yaml' => 'services:',
        ];

        $result = $this->comparator->compare($legacy, $optimized, []);

        $this->assertTrue($result->hasUnexpectedDifferences());
        $this->assertSame(['mapset.yaml'], $result->getPathsByVerdict(ComparisonResult::ONLY_IN_OPTIMIZED));
    }

    public function testForeignTemplateRemovalIsExpected(): void
    {
        $legacy = [
            'template_wms/own_group.own_layer.html' => '<table>',
            'template_wms/foreign_group.foreign_layer.html' => '<table>',
        ];
        $optimized = [
            'template_wms/own_group.own_layer.html' => '<table>',
        ];

        $result = $this->comparator->compare($legacy, $optimized, ['own_group.own_layer']);

        $this->assertFalse($result->hasUnexpectedDifferences());
        $this->assertSame(
            ['template_wms/foreign_group.foreign_layer.html'],
            $result->getPathsByVerdict(ComparisonResult::EXPECTED_REMOVED)
        );
    }

    public function testForeignTemplateWithLanguageSuffixIsExpected(): void
    {
        $legacy = [
            'template_wms/foreign_group.foreign_layer_en.html' => '<table>',
        ];
        $optimized = [];

        $result = $this->comparator->compare($legacy, $optimized, ['own_group.own_layer']);

        $this->assertFalse($result->hasUnexpectedDifferences());
    }

    public function testMissingOwnTemplateIsUnexpected(): void
    {
        $legacy = [
            'template_wms/own_group.own_layer.html' => '<table>',
        ];
        $optimized = [];

        $result = $this->comparator->compare($legacy, $optimized, ['own_group.own_layer']);

        $this->assertTrue($result->hasUnexpectedDifferences());
        $this->assertSame(
            ['template_wms/own_group.own_layer.html'],
            $result->getPathsByVerdict(ComparisonResult::ONLY_IN_LEGACY)
        );
    }

    public function testOwnTemplateWithLanguageSuffixIsMatched(): void
    {
        $legacy = [
            'template_wms/own_group.own_layer_de.html' => '<table>',
        ];
        $optimized = [];

        $result = $this->comparator->compare($legacy, $optimized, ['own_group.own_layer']);

        // own template missing from optimized output -> unexpected
        $this->assertTrue($result->hasUnexpectedDifferences());
    }

    public function testMissingHeaderFooterIsNeverExpectedRemoved(): void
    {
        $legacy = [
            'template_wms/header.html' => '<html>',
            'template_wms/footer_en.html' => '</html>',
        ];
        $optimized = [];

        $result = $this->comparator->compare($legacy, $optimized, []);

        $this->assertTrue($result->hasUnexpectedDifferences());
        $this->assertSame(
            ['template_wms/footer_en.html', 'template_wms/header.html'],
            $result->getPathsByVerdict(ComparisonResult::ONLY_IN_LEGACY)
        );
    }

    public function testNonTemplateFileOnlyInLegacyIsUnexpected(): void
    {
        $legacy = [
            'other/foreign_group.foreign_layer.html' => '<table>',
        ];
        $optimized = [];

        $result = $this->comparator->compare($legacy, $optimized, []);

        $this->assertTrue($result->hasUnexpectedDifferences());
    }

    public function testSnapshotReadsFilesRecursively(): void
    {
        $dir = sys_get_temp_dir() . '/gc_comparator_test_' . getmypid();
        mkdir($dir . '/template_wms', 0777, true);
        file_put_contents($dir . '/mapset.map', 'MAP END');
        file_put_contents($dir . '/template_wms/header.html', '<html>');

        try {
            $snapshot = $this->comparator->snapshot($dir);

            $this->assertSame(
                [
                    'mapset.map' => 'MAP END',
                    'template_wms/header.html' => '<html>',
                ],
                $snapshot
            );
        } finally {
            unlink($dir . '/mapset.map');
            unlink($dir . '/template_wms/header.html');
            rmdir($dir . '/template_wms');
            rmdir($dir);
        }
    }

    public function testSnapshotOfMissingDirectoryIsEmpty(): void
    {
        $this->assertSame([], $this->comparator->snapshot('/nonexistent/path/for/sure'));
    }
}
