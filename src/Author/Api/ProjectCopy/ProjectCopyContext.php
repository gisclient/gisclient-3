<?php

namespace GisClient\Author\Api\ProjectCopy;

use GisClient\Author\Api\Exception\ApiException;

class ProjectCopyContext
{
    /**
     * @var string
     */
    private $sourceProject;

    /**
     * @var string
     */
    private $targetProject;

    /**
     * @var array<string,array<string|int,string|int>>
     */
    private $idMaps = [];

    /**
     * @var array<string,int>
     */
    private $createdCounters = [
        'project' => 0,
        'project_srs' => 0,
        'project_languages' => 0,
        'catalog' => 0,
        'link' => 0,
        'theme' => 0,
        'layergroup' => 0,
        'mapset' => 0,
        'layer' => 0,
        'class' => 0,
        'style' => 0,
        'field' => 0,
        'mapset_layergroup' => 0,
        'selgroup' => 0,
        'selgroup_layer' => 0,
        'project_admin' => 0,
    ];

    /**
     * @var array<int,string>
     */
    private $warnings = [];

    public function __construct(string $sourceProject, string $targetProject)
    {
        $this->sourceProject = $sourceProject;
        $this->targetProject = $targetProject;
    }

    public function getSourceProject(): string
    {
        return $this->sourceProject;
    }

    public function getTargetProject(): string
    {
        return $this->targetProject;
    }

    public function rememberMapping(string $type, $sourceId, $targetId): void
    {
        if (!isset($this->idMaps[$type])) {
            $this->idMaps[$type] = [];
        }

        $this->idMaps[$type][$sourceId] = $targetId;
    }

    public function mapId(string $type, $sourceId)
    {
        if (!isset($this->idMaps[$type]) || !array_key_exists($sourceId, $this->idMaps[$type])) {
            throw new ApiException(
                422,
                'unresolved_cloned_reference',
                'Invalid Clone Graph',
                sprintf("Missing cloned mapping for %s '%s'", $type, $sourceId)
            );
        }

        return $this->idMaps[$type][$sourceId];
    }

    public function incrementCreated(string $type): void
    {
        $this->createdCounters[$type] = ($this->createdCounters[$type] ?? 0) + 1;
    }

    /**
     * @return array<string,int>
     */
    public function getCreatedCounters(): array
    {
        return $this->createdCounters;
    }

    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    /**
     * @return array<int,string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
