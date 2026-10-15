<?php

use GisClient\Author\Command\ApiTokenRegisterCommand;
use GisClient\Author\Security\ApiToken\PdoApiTokenStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

require_once __DIR__ . '/../Security/Support/SecurityTestDatabase.php';

class ApiTokenRegisterCommandTest extends TestCase
{
    private const ID = '0123456789abcdef';
    private const HASH = 'e7d3fe086d7203229f92eb56ced97b436b9d6fb51118bc761f21417eb627bc8d';

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

    public function testRegisterStoresTokenAndPrintsConfirmationWithoutHash()
    {
        $tester = $this->createTester();

        $exitCode = $tester->execute($this->input());

        $this->assertSame(0, $exitCode);
        $stored = (new PdoApiTokenStore($this->db))->find(self::ID);
        $this->assertNotNull($stored);
        $this->assertSame('admin', $stored->getUsername());
        $this->assertSame('client-app', $stored->getName());
        $this->assertSame(self::HASH, $stored->getTokenHash());
        $this->assertStringContainsString("Registered token 'client-app' (id " . self::ID . ") for user 'admin'", $tester->getDisplay());
        $this->assertStringNotContainsString(self::HASH, $tester->getDisplay());
    }

    /**
     * @dataProvider invalidInputProvider
     *
     * @param array<string, string> $input
     */
    public function testRegisterRejectsInvalidInput(array $input, string $expectedMessage)
    {
        $tester = $this->createTester();

        $exitCode = $tester->execute($this->input($input));

        $this->assertNotSame(0, $exitCode);
        $this->assertStringContainsString($expectedMessage, $tester->getDisplay());
        $this->assertSame(0, $this->countTokens());
    }

    public function invalidInputProvider(): array
    {
        return [
            'id too short' => [
                [
                    '--id' => '0123456789abcde',
                ],
                "Malformed token id '0123456789abcde'",
            ],
            'id with uppercase hex' => [
                [
                    '--id' => '0123456789ABCDEF',
                ],
                "Malformed token id '0123456789ABCDEF'",
            ],
            'id not hex' => [
                [
                    '--id' => '0123456789abcdeg',
                ],
                "Malformed token id '0123456789abcdeg'",
            ],
            'hash too short' => [
                [
                    '--hash' => substr(self::HASH, 1),
                ],
                'Malformed token hash',
            ],
            'hash not hex' => [
                [
                    '--hash' => 'z' . substr(self::HASH, 1),
                ],
                'Malformed token hash',
            ],
            'unknown user' => [
                [
                    'username' => 'nobody',
                ],
                "User 'nobody' does not exist",
            ],
        ];
    }

    /**
     * @dataProvider missingOptionProvider
     */
    public function testRegisterRequiresIdAndHash(string $option)
    {
        $input = $this->input();
        unset($input[$option]);
        $tester = $this->createTester();

        $exitCode = $tester->execute($input);

        $this->assertNotSame(0, $exitCode);
        $this->assertStringContainsString("The {$option} option is required", $tester->getDisplay());
        $this->assertSame(0, $this->countTokens());
    }

    public function missingOptionProvider(): array
    {
        return [
            'id' => ['--id'],
            'hash' => ['--hash'],
        ];
    }

    public function testRegisterRejectsDuplicateId()
    {
        $this->createTester()->execute($this->input());
        $tester = $this->createTester();

        $exitCode = $tester->execute($this->input([
            'name' => 'other-app',
        ]));

        $this->assertNotSame(0, $exitCode);
        $this->assertStringContainsString("A token with id '" . self::ID . "' already exists", $tester->getDisplay());
        $this->assertSame(1, $this->countTokens());
    }

    public function testRegisterRejectsDuplicateNameForSameUser()
    {
        $this->createTester()->execute($this->input());
        $tester = $this->createTester();

        $exitCode = $tester->execute($this->input([
            '--id' => 'fedcba9876543210',
        ]));

        $this->assertNotSame(0, $exitCode);
        $this->assertStringContainsString("User 'admin' already has a token named 'client-app'", $tester->getDisplay());
        $this->assertSame(1, $this->countTokens());
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    private function input(array $overrides = []): array
    {
        return array_merge([
            'username' => 'admin',
            'name' => 'client-app',
            '--id' => self::ID,
            '--hash' => self::HASH,
        ], $overrides);
    }

    private function createTester(): CommandTester
    {
        return new CommandTester(new ApiTokenRegisterCommand(new PdoApiTokenStore($this->db)));
    }

    private function countTokens(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM ' . DB_SCHEMA . '.api_token')->fetchColumn();
    }
}
