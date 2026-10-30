<?php

use GisClient\Author\Persistence\PdoPrimaryKeyAllocator;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/../../../config/config.db.php';

class PdoPrimaryKeyAllocatorTest extends TestCase
{
    /**
     * @var \PDO
     */
    private $db;

    protected function setUp(): void
    {
        $this->db = GCApp::getDB();
        $this->db->exec('CREATE TEMP TABLE pk_allocator_test (id integer PRIMARY KEY)');
        $this->db->exec('INSERT INTO pk_allocator_test (id) VALUES (1), (2), (4), (5), (6)');
    }

    protected function tearDown(): void
    {
        $this->db->exec('DROP TABLE IF EXISTS pk_allocator_test');
    }

    public function testFirstAllocationFillsTheLowestGap(): void
    {
        $allocator = new PdoPrimaryKeyAllocator($this->db);

        $this->assertSame(3, $allocator->allocate('pg_temp', 'pk_allocator_test', 'id'));
    }

    public function testFollowingAllocationsContinueAboveTheHighestIdWithoutRescanningGaps(): void
    {
        $allocator = new PdoPrimaryKeyAllocator($this->db);

        $this->insert($allocator->allocate('pg_temp', 'pk_allocator_test', 'id'));
        // A gap-filling allocation would return 1 now; bulk inserts must not rescan the table for it.
        $this->db->exec('DELETE FROM pk_allocator_test WHERE id = 1');

        $this->insert($second = $allocator->allocate('pg_temp', 'pk_allocator_test', 'id'));
        $this->insert($third = $allocator->allocate('pg_temp', 'pk_allocator_test', 'id'));

        $this->assertSame(7, $second);
        $this->assertSame(8, $third);
    }

    public function testSkipsIdsTakenByOtherWritersSinceTheLastAllocation(): void
    {
        $allocator = new PdoPrimaryKeyAllocator($this->db);

        $this->insert($allocator->allocate('pg_temp', 'pk_allocator_test', 'id'));
        $this->insert($allocator->allocate('pg_temp', 'pk_allocator_test', 'id'));
        $this->insert(8);

        $this->assertSame(9, $allocator->allocate('pg_temp', 'pk_allocator_test', 'id'));
    }

    public function testTracksTablesIndependently(): void
    {
        $this->db->exec('CREATE TEMP TABLE pk_allocator_other_test (id integer PRIMARY KEY)');
        $this->db->exec('INSERT INTO pk_allocator_other_test (id) VALUES (1), (3)');

        try {
            $allocator = new PdoPrimaryKeyAllocator($this->db);
            $this->insert($allocator->allocate('pg_temp', 'pk_allocator_test', 'id'));
            $this->insert($allocator->allocate('pg_temp', 'pk_allocator_test', 'id'));

            $this->assertSame(2, $allocator->allocate('pg_temp', 'pk_allocator_other_test', 'id'));
        } finally {
            $this->db->exec('DROP TABLE IF EXISTS pk_allocator_other_test');
        }
    }

    private function insert(int $id): void
    {
        $stmt = $this->db->prepare('INSERT INTO pk_allocator_test (id) VALUES (:id)');
        $stmt->execute([
            ':id' => $id,
        ]);
    }
}
