<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/ProjectCopyGatewayStub.php';

use GisClient\Author\Api\Dto\CatalogDto;
use GisClient\Author\Api\Dto\ClassDto;
use GisClient\Author\Api\Dto\FieldDto;
use GisClient\Author\Api\Dto\LayerDto;
use GisClient\Author\Api\Dto\LayergroupDto;
use GisClient\Author\Api\Dto\MapsetDto;
use GisClient\Author\Api\Dto\MapsetLayergroupDto;
use GisClient\Author\Api\Dto\ProjectAdminDto;
use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ProjectLanguageDto;
use GisClient\Author\Api\Dto\ProjectSrsDto;
use GisClient\Author\Api\Dto\SelgroupDto;
use GisClient\Author\Api\Dto\SelgroupLayerDto;
use GisClient\Author\Api\Dto\StyleDto;
use GisClient\Author\Api\Dto\ThemeDto;
use GisClient\Author\Api\ProjectCopy\ProjectCopyRequest;
use GisClient\Author\Api\ProjectCopy\ProjectCopyRequestParser;
use GisClient\Author\Api\Service\ProjectCopyService;
use GisClient\Author\Api\Service\ProjectTransferService;
use PHPUnit\Framework\TestCase;

class ProjectCopyServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    /**
     * @var mixed
     */
    private $originalAuthenticationHandler;

    protected function setUp(): void
    {
        $this->originalAuthenticationHandler = $this->getAuthenticationHandler();
        $this->setAuthenticationHandler($this->createAuthHandler(false, false, null));
    }

    protected function tearDown(): void
    {
        $this->setAuthenticationHandler($this->originalAuthenticationHandler);
    }

    public function testExecuteRejectsMissingSourceProject(): void
    {
        $service = new ProjectCopyService(new ProjectCopyGatewayStub(), new ProjectTransferService());

        $this->assertApiException(static function () use ($service): void {
            $service->execute(new ProjectCopyRequest('missing', 'target', null, ProjectCopyRequestParser::MAPSET_NAMING_REPLACE_PROJECT_NAME, false, false));
        }, 404, 'resource_not_found', '/source_project');
    }

    public function testExecuteRejectsExistingTargetProject(): void
    {
        $gateway = new ProjectCopyGatewayStub([
            'project' => [
                $this->makeDto(ProjectDto::class, 'source', [
                    'project_title' => 'Source',
                    'project_srid' => 3857,
                    'max_extent_scale' => 50000,
                    'charset_encodings_id' => 1,
                    'default_language_id' => 'it',
                ]),
                $this->makeDto(ProjectDto::class, 'target', [
                    'project_title' => 'Target',
                    'project_srid' => 3857,
                    'max_extent_scale' => 50000,
                    'charset_encodings_id' => 1,
                    'default_language_id' => 'it',
                ]),
            ],
        ]);
        $service = new ProjectCopyService($gateway, new ProjectTransferService());

        $this->assertApiException(static function () use ($service): void {
            $service->execute(new ProjectCopyRequest('source', 'target', null, ProjectCopyRequestParser::MAPSET_NAMING_REPLACE_PROJECT_NAME, false, false));
        }, 409, 'target_project_exists', '/target_project');
    }

    public function testExecuteRejectsInvalidTargetProjectName(): void
    {
        $gateway = new ProjectCopyGatewayStub([
            'project' => [
                $this->makeDto(ProjectDto::class, 'source', [
                    'project_title' => 'Source',
                    'project_srid' => 3857,
                    'max_extent_scale' => 50000,
                    'charset_encodings_id' => 1,
                    'default_language_id' => 'it',
                ]),
            ],
        ]);
        $service = new ProjectCopyService($gateway, new ProjectTransferService());

        $this->assertApiException(static function () use ($service): void {
            $service->execute(new ProjectCopyRequest('source', 'bad-name', null, ProjectCopyRequestParser::MAPSET_NAMING_REPLACE_PROJECT_NAME, false, false));
        }, 422, 'invalid_target_project_name', '/target_project');
    }

    public function testExecuteRejectsCollidingGeneratedMapsetName(): void
    {
        $sourceProject = $this->makeProject('source');
        $gateway = new ProjectCopyGatewayStub([
            'project' => [$sourceProject],
            'mapset' => [
                $this->makeMapset('source', 'source', 'source'),
                $this->makeMapset('target', 'target', 'target'),
            ],
        ]);
        $service = new ProjectCopyService($gateway, new ProjectTransferService());

        $this->assertApiException(static function () use ($service): void {
            $service->execute(new ProjectCopyRequest('source', 'target', null, ProjectCopyRequestParser::MAPSET_NAMING_REPLACE_PROJECT_NAME, false, false));
        }, 409, 'mapset_name_collision', '/mapset_naming_mode');
    }

    public function testExecuteClonesResourcesInOrderAndCapturesGeneratedIds(): void
    {
        $this->setAuthenticationHandler($this->createAuthHandler(true, true, 'caller'));

        $sourceProject = $this->makeProject('source');
        $projectSrs = $this->makeDto(ProjectSrsDto::class, null, [
            'srid' => 3857,
            'projparam' => 'proj=merc',
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'source'),
        ]);
        $catalog = $this->makeDto(CatalogDto::class, 10, [
            'catalog_name' => 'cat',
            'connection_type' => 6,
            'catalog_path' => '/tmp',
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'source'),
        ]);
        $theme = $this->makeDto(ThemeDto::class, 20, [
            'theme_name' => 'theme',
            'theme_title' => 'Theme',
            'theme_order' => 1,
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'source'),
        ]);
        $layergroup = $this->makeDto(LayergroupDto::class, 30, [
            'layergroup_name' => 'group',
            'layergroup_title' => 'Group',
        ], [
            'theme' => $this->identifierDto(ThemeDto::class, 20),
        ]);
        $mapset = $this->makeMapset('source', 'source', 'source');
        $layer = $this->makeDto(LayerDto::class, 40, [
            'layer_name' => 'layer',
            'layertype_id' => 1,
            'sizeunits_id' => 1,
        ], [
            'catalog' => $this->identifierDto(CatalogDto::class, 10),
            'layergroup' => $this->identifierDto(LayergroupDto::class, 30),
        ]);
        $class = $this->makeDto(ClassDto::class, 50, [
            'class_name' => 'class',
        ], [
            'layer' => $this->identifierDto(LayerDto::class, 40),
        ]);
        $style = $this->makeDto(StyleDto::class, 60, [
            'style_name' => 'style',
        ], [
            'class' => $this->identifierDto(ClassDto::class, 50),
        ]);
        $field = $this->makeDto(FieldDto::class, 70, [
            'relation_id' => 1,
            'field_name' => 'field',
            'field_header' => 'Field',
            'field_order' => 1,
            'fieldtype_id' => 1,
            'datatype_id' => 1,
            'resultype_id' => 6,
            'searchtype_id' => 1,
            'orderby_id' => 1,
        ], [
            'layer' => $this->identifierDto(LayerDto::class, 40),
        ]);
        $mapsetLayergroup = $this->makeDto(MapsetLayergroupDto::class, null, [
            'status' => 1,
        ], [
            'mapset' => $this->identifierDto(MapsetDto::class, 'source'),
            'layergroup' => $this->identifierDto(LayergroupDto::class, 30),
        ]);
        $projectLanguage = new ProjectLanguageDto();
        $projectLanguage->languageId = 'it';
        $projectLanguage->markPresent('language_id');
        $projectLanguage->project = $this->identifierDto(ProjectDto::class, 'source');
        $projectLanguage->markPresent('project');
        $selgroup = new SelgroupDto();
        $selgroup->id = 80;
        $selgroup->markPresent('id');
        $selgroup->selgroupName = 'selection';
        $selgroup->markPresent('selgroup_name');
        $selgroup->selgroupTitle = 'Selection';
        $selgroup->markPresent('selgroup_title');
        $selgroup->selgroupOrder = 1;
        $selgroup->markPresent('selgroup_order');
        $selgroup->project = $this->identifierDto(ProjectDto::class, 'source');
        $selgroup->markPresent('project');
        $selgroupLayer = new SelgroupLayerDto();
        $selgroupLayer->selgroup = $this->identifierDto(SelgroupDto::class, 80);
        $selgroupLayer->markPresent('selgroup');
        $selgroupLayer->layer = $this->identifierDto(LayerDto::class, 40);
        $selgroupLayer->markPresent('layer');
        $projectAdmin = new ProjectAdminDto();
        $projectAdmin->username = 'admin';
        $projectAdmin->markPresent('username');
        $projectAdmin->project = $this->identifierDto(ProjectDto::class, 'source');
        $projectAdmin->markPresent('project');

        $gateway = new ProjectCopyGatewayStub([
            'project' => [$sourceProject],
            'project_srs' => [$projectSrs],
            'project_languages' => [$projectLanguage],
            'catalog' => [$catalog],
            'link' => [],
            'theme' => [$theme],
            'layergroup' => [$layergroup],
            'mapset' => [$mapset],
            'layer' => [$layer],
            'class' => [$class],
            'style' => [$style],
            'field' => [$field],
            'mapset_layergroup' => [$mapsetLayergroup],
            'selgroup' => [$selgroup],
            'selgroup_layer' => [$selgroupLayer],
            'project_admin' => [$projectAdmin],
        ]);
        $service = new ProjectCopyService($gateway, new ProjectTransferService());

        $response = $service->execute(new ProjectCopyRequest(
            'source',
            'target',
            null,
            ProjectCopyRequestParser::MAPSET_NAMING_REPLACE_PROJECT_NAME,
            false,
            false
        ));

        $payload = $response->toArray();
        $this->assertSame(['project', 'project_srs', 'project_languages', 'catalog', 'theme', 'layergroup', 'mapset', 'layer', 'class', 'style', 'field', 'mapset_layergroup', 'selgroup', 'selgroup_layer', 'project_admin', 'project_admin'], array_column($gateway->created, 'type'));
        $this->assertSame('target', $payload['target_project']);
        $this->assertSame(1, $payload['created']['project']);
        $this->assertSame(1, $payload['created']['project_languages']);
        $this->assertSame(1, $payload['created']['selgroup']);
        $this->assertSame(2, $payload['created']['project_admin']);

        /** @var LayerDto $createdLayer */
        $createdLayer = $gateway->created[7]['dto'];
        $this->assertSame(2000, $createdLayer->layergroup->id);
        $this->assertSame(950, $createdLayer->catalog->id);

        /** @var MapsetLayergroupDto $createdMapsetLayergroup */
        $createdMapsetLayergroup = $gateway->created[11]['dto'];
        $this->assertSame('target', $createdMapsetLayergroup->mapset->id);
        $this->assertSame(2000, $createdMapsetLayergroup->layergroup->id);

        /** @var SelgroupLayerDto $createdSelgroupLayer */
        $createdSelgroupLayer = $gateway->created[13]['dto'];
        $this->assertSame(7000, $createdSelgroupLayer->selgroup->id);
        $this->assertSame(3000, $createdSelgroupLayer->layer->id);
    }

    public function testExecuteUsesExplicitTargetProjectTitleOverride(): void
    {
        $gateway = new ProjectCopyGatewayStub([
            'project' => [
                $this->makeProject('source'),
            ],
            'project_srs' => [],
            'project_languages' => [],
            'catalog' => [],
            'link' => [],
            'theme' => [],
            'layergroup' => [],
            'mapset' => [],
            'layer' => [],
            'class' => [],
            'style' => [],
            'field' => [],
            'mapset_layergroup' => [],
            'selgroup' => [],
            'selgroup_layer' => [],
            'project_admin' => [],
        ]);
        $service = new ProjectCopyService($gateway, new ProjectTransferService());

        $service->execute(new ProjectCopyRequest(
            'source',
            'target',
            'Target title',
            ProjectCopyRequestParser::MAPSET_NAMING_REPLACE_PROJECT_NAME,
            false,
            false
        ));

        /** @var ProjectDto $createdProject */
        $createdProject = $gateway->created[0]['dto'];
        $this->assertSame('Target title', $createdProject->projectTitle);
    }

    private function makeProject(string $id): ProjectDto
    {
        return $this->makeDto(ProjectDto::class, $id, [
            'project_title' => ucfirst($id),
            'project_srid' => 3857,
            'max_extent_scale' => 50000,
            'charset_encodings_id' => 1,
            'default_language_id' => 'it',
        ]);
    }

    private function makeMapset(string $projectId, string $id, string $title): MapsetDto
    {
        return $this->makeDto(MapsetDto::class, $id, [
            'mapset_title' => $title,
            'maxscale' => 50000,
            'mapset_srid' => 3857,
            'mapset_extent' => '0 0 10 10',
            'mapset_scale_type' => 1,
            'mapset_order' => 1,
        ], [
            'project' => $this->identifierDto(ProjectDto::class, $projectId),
        ]);
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
