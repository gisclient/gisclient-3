<?php

use GisClient\Author\Command\DbUpgradeCommand;
use PHPUnit\Framework\TestCase;

class DbUpgradeCommandTest extends TestCase
{
    public function testGetCurrentVersionComparesVersionsNumerically()
    {
        $db = $this->createVersionDatabase([
            ['3.6.9', 'author'],
            ['3.6.10', 'author'],
            ['3.6.4', 'author'],
            ['9.9.9', 'other'],
        ]);

        $this->assertSame('3.6.10', $this->getCurrentVersion($db));
    }

    public function testGetCurrentVersionReturnsZeroWithoutAuthorVersions()
    {
        $db = $this->createVersionDatabase([
            ['9.9.9', 'other'],
        ]);

        $this->assertSame('0', $this->getCurrentVersion($db));
    }

    /**
     * @param array<array{string, string}> $rows [version_name, version_key]
     */
    private function createVersionDatabase(array $rows): \PDO
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required.');
        }

        $db = new \PDO('sqlite::memory:');
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $db->exec("ATTACH DATABASE ':memory:' AS gisclient_34");
        $db->exec('CREATE TABLE gisclient_34.version (version_name VARCHAR NOT NULL, version_key VARCHAR NOT NULL)');

        $insert = $db->prepare('INSERT INTO gisclient_34.version (version_name, version_key) VALUES (?, ?)');
        foreach ($rows as $row) {
            $insert->execute($row);
        }

        return $db;
    }

    private function getCurrentVersion(\PDO $db): string
    {
        $method = new \ReflectionMethod(DbUpgradeCommand::class, 'getCurrentVersion');
        $method->setAccessible(true);

        return $method->invoke(new DbUpgradeCommand(), $db);
    }
}
