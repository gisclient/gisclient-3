<?php

use GisClient\Author\Command\ApiTokenListCommand;
use GisClient\Author\Security\ApiToken\ApiTokenGenerator;
use GisClient\Author\Security\ApiToken\GeneratedApiToken;
use GisClient\Author\Security\ApiToken\PdoApiTokenStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

require_once __DIR__ . '/../Security/Support/SecurityTestDatabase.php';

class ApiTokenListCommandTest extends TestCase
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var array<string, GeneratedApiToken>
     */
    private $tokens = [];

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required.');
        }

        $this->db = SecurityTestDatabase::create();
        SecurityTestDatabase::addUser($this->db, 'admin');
        SecurityTestDatabase::addUser($this->db, 'editor');
    }

    public function testListShowsAllTokensWithoutHashesOrSecrets()
    {
        $this->addToken('admin', 'client-app');
        $this->addToken('admin', 'other-app');
        $this->addToken('editor', 'editor-app');
        $tester = $this->createTester();

        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        foreach ($this->tokens as $name => $generated) {
            $stored = (new PdoApiTokenStore($this->db))->find($generated->getId());
            $columns = [$generated->getId(), $name, $stored->getUsername(), $stored->getCreatedAt()];
            $this->assertMatchesRegularExpression(
                '/' . implode('\s*\|\s*', array_map(fn (string $column) => preg_quote($column, '/'), $columns)) . '/',
                $display
            );
            $this->assertStringNotContainsString($generated->getHash(), $display);
            $this->assertStringNotContainsString($generated->getToken(), $display);
        }
    }

    public function testListFiltersByUsername()
    {
        $this->addToken('admin', 'client-app');
        $this->addToken('editor', 'editor-app');
        $tester = $this->createTester();

        $exitCode = $tester->execute([
            'username' => 'editor',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString($this->tokens['editor-app']->getId(), $tester->getDisplay());
        $this->assertStringNotContainsString($this->tokens['client-app']->getId(), $tester->getDisplay());
        $this->assertStringNotContainsString($this->tokens['editor-app']->getHash(), $tester->getDisplay());
    }

    public function testListWithoutTokensSaysSo()
    {
        $tester = $this->createTester();

        $exitCode = $tester->execute([
            'username' => 'admin',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString("No tokens found for user 'admin'.", $tester->getDisplay());
    }

    private function addToken(string $username, string $name): void
    {
        $generated = (new ApiTokenGenerator())->generate();
        (new PdoApiTokenStore($this->db))->register($generated->getId(), $username, $name, $generated->getHash());
        $this->tokens[$name] = $generated;
    }

    private function createTester(): CommandTester
    {
        return new CommandTester(new ApiTokenListCommand(new PdoApiTokenStore($this->db)));
    }
}
