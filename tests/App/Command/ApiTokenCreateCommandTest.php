<?php

use GisClient\Author\Command\ApiTokenCreateCommand;
use GisClient\Author\Security\ApiToken\PdoApiTokenStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

require_once __DIR__ . '/../Security/Support/SecurityTestDatabase.php';

class ApiTokenCreateCommandTest extends TestCase
{
    /**
     * @var \PDO
     */
    private $db;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required.');
        }

        $this->db = SecurityTestDatabase::create();
        SecurityTestDatabase::addUser($this->db, 'admin');
    }

    public function testCreatePrintsTokenOnceAndStoresOnlyItsHash()
    {
        $tester = $this->createTester();

        $exitCode = $tester->execute([
            'username' => 'admin',
            'name' => 'client-app',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(1, preg_match_all('/gca_([0-9a-f]{16})_([A-Za-z0-9_-]{43})(?![A-Za-z0-9_-])/', $tester->getDisplay(), $matches));
        $token = $matches[0][0];
        $id = $matches[1][0];

        $stored = (new PdoApiTokenStore($this->db))->find($id);
        $this->assertNotNull($stored);
        $this->assertSame('admin', $stored->getUsername());
        $this->assertSame('client-app', $stored->getName());
        $this->assertSame(hash('sha256', $token), $stored->getTokenHash());
        $this->assertStringNotContainsString($matches[2][0], $this->dumpTokenTable());
    }

    public function testCreateRejectsUnknownUser()
    {
        $tester = $this->createTester();

        $exitCode = $tester->execute([
            'username' => 'nobody',
            'name' => 'client-app',
        ]);

        $this->assertNotSame(0, $exitCode);
        $this->assertStringContainsString("User 'nobody' does not exist", $tester->getDisplay());
        $this->assertStringNotContainsString('gca_', $tester->getDisplay());
        $this->assertSame('', $this->dumpTokenTable());
    }

    public function testCreateRejectsDuplicateNameForSameUser()
    {
        $this->createTester()->execute([
            'username' => 'admin',
            'name' => 'client-app',
        ]);
        $tester = $this->createTester();

        $exitCode = $tester->execute([
            'username' => 'admin',
            'name' => 'client-app',
        ]);

        $this->assertNotSame(0, $exitCode);
        $this->assertStringContainsString("User 'admin' already has a token named 'client-app'", $tester->getDisplay());
        $this->assertStringNotContainsString('gca_', $tester->getDisplay());
    }

    public function testCreateAllowsSameNameForAnotherUser()
    {
        SecurityTestDatabase::addUser($this->db, 'editor');
        $this->createTester()->execute([
            'username' => 'admin',
            'name' => 'client-app',
        ]);

        $exitCode = $this->createTester()->execute([
            'username' => 'editor',
            'name' => 'client-app',
        ]);

        $this->assertSame(0, $exitCode);
    }

    private function createTester(): CommandTester
    {
        return new CommandTester(new ApiTokenCreateCommand(new PdoApiTokenStore($this->db)));
    }

    private function dumpTokenTable(): string
    {
        $rows = $this->db->query('SELECT * FROM ' . DB_SCHEMA . '.api_token')->fetchAll(\PDO::FETCH_ASSOC);

        return implode("\n", array_map(fn (array $row) => implode(' ', $row), $rows));
    }
}
