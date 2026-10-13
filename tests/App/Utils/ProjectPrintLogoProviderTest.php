<?php

use GisClient\Author\Utils\ProjectPrintLogoProvider;
use PHPUnit\Framework\TestCase;

class ProjectPrintLogoProviderTest extends TestCase
{
    // PNG 1x1
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    /**
     * @var \PDO
     */
    private $db;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required');
        }

        $this->db = new \PDO('sqlite::memory:');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->db->exec("ATTACH DATABASE ':memory:' AS gisclient_34");
        $this->db->exec('CREATE TABLE gisclient_34.project_print_logo (project_name TEXT, logo_position TEXT, logo_data TEXT)');
    }

    public function testReturnsDataUrisOfTheProjectOnly(): void
    {
        $this->insertLogo('cliente_a', 'sx', self::PNG);
        $this->insertLogo('cliente_a', 'dx', self::PNG);
        $this->insertLogo('cliente_b', 'sx', self::PNG);

        $dataUris = (new ProjectPrintLogoProvider($this->db, 'gisclient_34'))->getDataUris('cliente_a');

        $this->assertSame([
            'sx' => 'data:image/png;base64,' . self::PNG,
            'dx' => 'data:image/png;base64,' . self::PNG,
        ], $dataUris);
    }

    public function testSkipsLogosThatAreNotImages(): void
    {
        $this->insertLogo('cliente_a', 'sx', base64_encode('hello world'));

        $dataUris = (new ProjectPrintLogoProvider($this->db, 'gisclient_34'))->getDataUris('cliente_a');

        $this->assertSame([], $dataUris);
    }

    public function testReturnsNothingForProjectWithoutLogos(): void
    {
        $dataUris = (new ProjectPrintLogoProvider($this->db, 'gisclient_34'))->getDataUris('cliente_a');

        $this->assertSame([], $dataUris);
    }

    private function insertLogo(string $projectName, string $position, string $logoData): void
    {
        $stmt = $this->db->prepare('INSERT INTO gisclient_34.project_print_logo VALUES (?, ?, ?)');
        $stmt->execute([$projectName, $position, $logoData]);
    }
}
