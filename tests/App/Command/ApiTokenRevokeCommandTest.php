<?php

use GisClient\Author\Command\ApiTokenRevokeCommand;
use GisClient\Author\Security\ApiToken\ApiTokenGenerator;
use GisClient\Author\Security\ApiToken\PdoApiTokenStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

require_once __DIR__ . '/../Security/Support/SecurityTestDatabase.php';

class ApiTokenRevokeCommandTest extends TestCase
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

    public function testRevokeDeletesTokenAndConfirms()
    {
        $store = new PdoApiTokenStore($this->db);
        $revoked = (new ApiTokenGenerator())->generate();
        $kept = (new ApiTokenGenerator())->generate();
        $store->register($revoked->getId(), 'admin', 'client-app', $revoked->getHash());
        $store->register($kept->getId(), 'admin', 'other-app', $kept->getHash());
        $tester = $this->createTester();

        $exitCode = $tester->execute([
            'id' => $revoked->getId(),
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(
            "Revoked token '{$revoked->getId()}' ('client-app' of user 'admin').",
            $tester->getDisplay()
        );
        $this->assertNull($store->find($revoked->getId()));
        $this->assertNotNull($store->find($kept->getId()));
    }

    public function testRevokeUnknownIdFails()
    {
        $tester = $this->createTester();

        $exitCode = $tester->execute([
            'id' => '0123456789abcdef',
        ]);

        $this->assertNotSame(0, $exitCode);
        $this->assertStringContainsString("No token with id '0123456789abcdef' exists.", $tester->getDisplay());
    }

    private function createTester(): CommandTester
    {
        return new CommandTester(new ApiTokenRevokeCommand(new PdoApiTokenStore($this->db)));
    }
}
