<?php

namespace GisClient\Author\Api\ProjectCopy;

class ProjectCopyRequest
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
     * @var string|null
     */
    private $projectTitle;

    /**
     * @var string
     */
    private $mapsetNamingMode;

    /**
     * @var bool
     */
    private $refreshPrivateMapfiles;

    /**
     * @var bool
     */
    private $refreshPublicMapfiles;

    public function __construct(
        string $sourceProject,
        string $targetProject,
        ?string $projectTitle,
        string $mapsetNamingMode,
        bool $refreshPrivateMapfiles,
        bool $refreshPublicMapfiles
    ) {
        $this->sourceProject = $sourceProject;
        $this->targetProject = $targetProject;
        $this->projectTitle = $projectTitle;
        $this->mapsetNamingMode = $mapsetNamingMode;
        $this->refreshPrivateMapfiles = $refreshPrivateMapfiles;
        $this->refreshPublicMapfiles = $refreshPublicMapfiles;
    }

    public function getSourceProject(): string
    {
        return $this->sourceProject;
    }

    public function getTargetProject(): string
    {
        return $this->targetProject;
    }

    public function getProjectTitle(): ?string
    {
        return $this->projectTitle;
    }

    public function getMapsetNamingMode(): string
    {
        return $this->mapsetNamingMode;
    }

    public function shouldRefreshPrivateMapfiles(): bool
    {
        return $this->refreshPrivateMapfiles;
    }

    public function shouldRefreshPublicMapfiles(): bool
    {
        return $this->refreshPublicMapfiles;
    }

    public function shouldRefreshAnyMapfiles(): bool
    {
        return $this->refreshPrivateMapfiles || $this->refreshPublicMapfiles;
    }
}
