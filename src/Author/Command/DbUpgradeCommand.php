<?php

declare(strict_types=1);

namespace GisClient\Author\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbUpgradeCommand extends Command
{
    private const MIGRATIONS_DIR = 'doc/migrations/';

    protected function configure(): void
    {
        $this->setName('gisclient:dbupgrade')
            ->setDescription('Initialises or upgrades the gisclient_34 schema to the current version')
            ->setHelp("Creates the schema from scratch on a fresh database, then applies all pending migrations.");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = $this->getDatabase();
        $rootDir = $this->getRootDir();

        // Phase 1 + 2: fresh install only — runs when gisclient_34 schema does not yet exist.
        // Both files execute in one transaction so a partial failure rolls back the schema
        // creation entirely, allowing a clean retry on the next invocation.
        if (!$this->schemaExists($db)) {
            $output->writeln('<info>Fresh install — creating baseline schema and seeding lookup data...</info>');
            $this->runSqlFiles($db, [
                $rootDir . self::MIGRATIONS_DIR . '0000_baseline.sql',
                $rootDir . self::MIGRATIONS_DIR . '0000_lookup_data.sql',
            ]);
        }

        // Phase 3: incremental migrations — runs on every invocation
        $currentVersion = $this->getCurrentVersion($db);
        $output->writeln("<info>Current version: {$currentVersion}</info>");

        foreach ($this->discoverMigrations($rootDir . self::MIGRATIONS_DIR) as $version => $path) {
            if (version_compare($version, $currentVersion, '>')) {
                $output->writeln("<info>Applying migration {$version}...</info>");
                $this->runSqlFile($db, $path);
                $currentVersion = $this->getCurrentVersion($db);
            }
        }

        $output->writeln('<info>Done. Author version: ' . $this->getCurrentVersion($db) . '</info>');

        return 0;
    }

    private function schemaExists(\PDO $db): bool
    {
        $stmt = $db->query("SELECT 1 FROM pg_catalog.pg_namespace WHERE nspname = 'gisclient_34'");

        return $stmt !== false && $stmt->fetchColumn() !== false;
    }

    private function getCurrentVersion(\PDO $db): string
    {
        $stmt = $db->query(
            "SELECT max(version_name) FROM gisclient_34.version WHERE version_key = 'author'"
        );

        return $stmt->fetchColumn() ?: '0';
    }

    /**
     * Returns [version => filepath] sorted by version_compare ascending.
     * Only files whose basename starts with a semver token (e.g. 3.6.4_upgrade.sql).
     * Files prefixed with 0000_ are excluded — they are handled by Phase 1/2.
     *
     * @return array<string, string>
     */
    private function discoverMigrations(string $dir): array
    {
        $migrations = [];
        foreach (glob($dir . '*.sql') ?: [] as $file) {
            if (preg_match('/^(\d+\.\d+\.\d+)/', basename($file, '.sql'), $m)) {
                $migrations[$m[1]] = $file;
            }
        }
        uksort($migrations, 'version_compare');

        return $migrations;
    }

    /**
     * @param string[] $paths
     */
    private function runSqlFiles(\PDO $db, array $paths): void
    {
        $db->beginTransaction();
        try {
            foreach ($paths as $path) {
                $sql = file_get_contents($path);
                $db->exec($sql);
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw new \RuntimeException(
                sprintf('Migration failed [%s]: %s', basename($path ?? ''), $e->getMessage()),
                0,
                $e
            );
        }
    }

    private function runSqlFile(\PDO $db, string $path): void
    {
        $sql = file_get_contents($path);
        $db->beginTransaction();
        try {
            $db->exec($sql);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw new \RuntimeException(
                sprintf('Migration failed [%s]: %s', basename($path), $e->getMessage()),
                0,
                $e
            );
        }
    }

    private function getDatabase(): \PDO
    {
        return \GCApp::getDB();
    }

    private function getRootDir(): string
    {
        return __DIR__ . '/../../../';
    }
}
