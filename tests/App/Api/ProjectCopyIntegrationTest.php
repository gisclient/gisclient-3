<?php

use GisClient\Author\Api\Gateway\ApiCrudServiceGateway;
use GisClient\Author\Api\ProjectCopy\ProjectCopyRequest;
use GisClient\Author\Api\ProjectCopy\ProjectCopyRequestParser;
use GisClient\Author\Api\Service\ApiCrudService;
use GisClient\Author\Api\Service\ProjectCopyService;
use GisClient\Author\Persistence\PdoEntityRepository;
use GisClient\MapServer\Writer\MapfileWriterInterface;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Api/Support/TestApiCrudService.php';
include_once __DIR__ . '/../../../config/config.db.php';

if (!defined('DEBUG')) {
    define('DEBUG', false);
}

class ProjectCopyIntegrationTest extends TestCase
{
    /**
     * @var mixed
     */
    private $originalAuthenticationHandler;

    /**
     * @var array<int,string>
     */
    private $projectsToCleanup = [];

    protected function setUp(): void
    {
        $this->originalAuthenticationHandler = $this->getAuthenticationHandler();
        $this->setAuthenticationHandler($this->createAuthHandler(true, true, 'integration_admin'));
    }

    protected function tearDown(): void
    {
        foreach ($this->projectsToCleanup as $projectName) {
            $this->cleanupProject($projectName);
        }

        $this->setAuthenticationHandler($this->originalAuthenticationHandler);
    }

    public function testClonesDefaultProjectThroughRealGateway(): void
    {
        $targetProject = 'project_copy_it_' . substr(md5((string) microtime(true)), 0, 8);
        $this->projectsToCleanup[] = $targetProject;

        $service = $this->createService();
        $response = $service->execute(new ProjectCopyRequest(
            'default',
            $targetProject,
            'Integration title',
            ProjectCopyRequestParser::MAPSET_NAMING_REPLACE_PROJECT_NAME,
            false,
            false
        ));
        $payload = $response->toArray();

        $this->assertSame($targetProject, $payload['target_project']);
        $this->assertSame(1, $payload['created']['project']);
        $this->assertSame(2, $payload['created']['project_srs']);
        $this->assertSame(1, $payload['created']['catalog']);
        $this->assertSame(5, $payload['created']['theme']);
        $this->assertSame(6, $payload['created']['layergroup']);
        $this->assertSame(1, $payload['created']['mapset']);
        $this->assertSame(10, $payload['created']['layer']);
        $this->assertSame(21, $payload['created']['class']);
        $this->assertSame(25, $payload['created']['style']);
        $this->assertSame(40, $payload['created']['field']);
        $this->assertSame(6, $payload['created']['mapset_layergroup']);
        $this->assertSame(1, $payload['created']['project_admin']);

        $db = GCApp::getDB();
        $this->assertSame(1, $this->fetchCount($db, 'SELECT COUNT(*) FROM ' . DB_SCHEMA . '.project WHERE project_name = :project', $targetProject));
        $stmt = $db->prepare('SELECT project_title FROM ' . DB_SCHEMA . '.project WHERE project_name = :project');
        $stmt->execute([
            ':project' => $targetProject,
        ]);
        $this->assertSame('Integration title', (string) $stmt->fetchColumn());
        $this->assertSame(1, $this->fetchCount($db, 'SELECT COUNT(*) FROM ' . DB_SCHEMA . '.mapset WHERE project_name = :project AND mapset_name = :project', $targetProject));
        $this->assertSame(6, $this->fetchCount($db, 'SELECT COUNT(*) FROM ' . DB_SCHEMA . '.mapset_layergroup WHERE mapset_name = :project', $targetProject));
        $this->assertSame(1, $this->fetchCount($db, 'SELECT COUNT(*) FROM ' . DB_SCHEMA . '.project_admin WHERE project_name = :project AND username = :username', $targetProject, 'integration_admin'));
    }

    public function testRejectsDuplicateTargetProjectThroughRealGateway(): void
    {
        $service = $this->createService();

        $this->expectException(\GisClient\Author\Api\Exception\ApiException::class);
        $this->expectExceptionMessage('Target project already exists');

        $service->execute(new ProjectCopyRequest(
            'default',
            'default',
            null,
            ProjectCopyRequestParser::MAPSET_NAMING_REPLACE_PROJECT_NAME,
            false,
            false
        ));
    }

    private function createService(): ProjectCopyService
    {
        $repository = new PdoEntityRepository(GCApp::getDB());
        $crudService = new ApiCrudService($repository, new \GisClient\Author\Api\Validation\EntityValidator(GCApp::getDB()));

        $gateway = new ApiCrudServiceGateway($crudService, GCApp::getDB());

        return new ProjectCopyService($gateway, new \GisClient\Author\Api\Service\ProjectTransferService(), $this->createMock(MapfileWriterInterface::class));
    }

    private function cleanupProject(string $projectName): void
    {
        $db = GCApp::getDB();
        $statements = [
            'DELETE FROM ' . DB_SCHEMA . '.project_admin WHERE project_name = :project',
            'DELETE FROM ' . DB_SCHEMA . '.project_languages WHERE project_name = :project',
            'DELETE FROM ' . DB_SCHEMA . '.selgroup_layer WHERE selgroup_id IN (SELECT selgroup_id FROM ' . DB_SCHEMA . '.selgroup WHERE project_name = :project)',
            'DELETE FROM ' . DB_SCHEMA . '.selgroup WHERE project_name = :project',
            'DELETE FROM ' . DB_SCHEMA . '.mapset_layergroup WHERE mapset_name IN (SELECT mapset_name FROM ' . DB_SCHEMA . '.mapset WHERE project_name = :project)',
            'DELETE FROM ' . DB_SCHEMA . '.style WHERE class_id IN (
                SELECT c.class_id
                FROM ' . DB_SCHEMA . '.class c
                INNER JOIN ' . DB_SCHEMA . '.layer l ON l.layer_id = c.layer_id
                INNER JOIN ' . DB_SCHEMA . '.layergroup lg ON lg.layergroup_id = l.layergroup_id
                INNER JOIN ' . DB_SCHEMA . '.theme t ON t.theme_id = lg.theme_id
                WHERE t.project_name = :project
            )',
            'DELETE FROM ' . DB_SCHEMA . '.field WHERE layer_id IN (
                SELECT l.layer_id
                FROM ' . DB_SCHEMA . '.layer l
                INNER JOIN ' . DB_SCHEMA . '.layergroup lg ON lg.layergroup_id = l.layergroup_id
                INNER JOIN ' . DB_SCHEMA . '.theme t ON t.theme_id = lg.theme_id
                WHERE t.project_name = :project
            )',
            'DELETE FROM ' . DB_SCHEMA . '.class WHERE layer_id IN (
                SELECT l.layer_id
                FROM ' . DB_SCHEMA . '.layer l
                INNER JOIN ' . DB_SCHEMA . '.layergroup lg ON lg.layergroup_id = l.layergroup_id
                INNER JOIN ' . DB_SCHEMA . '.theme t ON t.theme_id = lg.theme_id
                WHERE t.project_name = :project
            )',
            'DELETE FROM ' . DB_SCHEMA . '.layer WHERE layergroup_id IN (
                SELECT lg.layergroup_id
                FROM ' . DB_SCHEMA . '.layergroup lg
                INNER JOIN ' . DB_SCHEMA . '.theme t ON t.theme_id = lg.theme_id
                WHERE t.project_name = :project
            )',
            'DELETE FROM ' . DB_SCHEMA . '.mapset WHERE project_name = :project',
            'DELETE FROM ' . DB_SCHEMA . '.layergroup WHERE theme_id IN (SELECT theme_id FROM ' . DB_SCHEMA . '.theme WHERE project_name = :project)',
            'DELETE FROM ' . DB_SCHEMA . '.theme WHERE project_name = :project',
            'DELETE FROM ' . DB_SCHEMA . '.catalog WHERE project_name = :project',
            'DELETE FROM ' . DB_SCHEMA . '.link WHERE project_name = :project',
            'DELETE FROM ' . DB_SCHEMA . '.project_srs WHERE project_name = :project',
            'DELETE FROM ' . DB_SCHEMA . '.project WHERE project_name = :project',
        ];

        foreach ($statements as $sql) {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':project' => $projectName,
            ]);
        }
    }

    private function fetchCount(\PDO $db, string $sql, string $projectName, ?string $username = null): int
    {
        $stmt = $db->prepare($sql);
        $params = [
            ':project' => $projectName,
        ];
        if ($username !== null) {
            $params[':username'] = $username;
        }
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function createAuthHandler(bool $isAuthenticated, bool $isAdmin, ?string $username)
    {
        return new class($isAuthenticated, $isAdmin, $username) {
            private $isAuthenticated;
            private $isAdmin;
            private $username;

            public function __construct(bool $isAuthenticated, bool $isAdmin, ?string $username)
            {
                $this->isAuthenticated = $isAuthenticated;
                $this->isAdmin = $isAdmin;
                $this->username = $username;
            }

            public function isAuthenticated()
            {
                return $this->isAuthenticated;
            }

            public function isAdmin()
            {
                return $this->isAdmin;
            }

            public function getToken()
            {
                return new class($this->username) {
                    private $username;

                    public function __construct(?string $username)
                    {
                        $this->username = $username;
                    }

                    public function getUsername()
                    {
                        return $this->username;
                    }
                };
            }
        };
    }

    private function getAuthenticationHandler()
    {
        $property = new ReflectionProperty('GCApp', 'authenticationHandler');
        $property->setAccessible(true);

        return $property->getValue();
    }

    private function setAuthenticationHandler($handler): void
    {
        $property = new ReflectionProperty('GCApp', 'authenticationHandler');
        $property->setAccessible(true);
        $property->setValue(null, $handler);
    }
}
