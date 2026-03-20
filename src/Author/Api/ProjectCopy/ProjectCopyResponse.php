<?php

namespace GisClient\Author\Api\ProjectCopy;

class ProjectCopyResponse
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
     * @var array<string,int>
     */
    private $createdCounters;

    /**
     * @var array<int,string>
     */
    private $warnings;

    /**
     * @param array<string,int> $createdCounters
     * @param array<int,string> $warnings
     */
    public function __construct(string $sourceProject, string $targetProject, array $createdCounters, array $warnings = [])
    {
        $this->sourceProject = $sourceProject;
        $this->targetProject = $targetProject;
        $this->createdCounters = $createdCounters;
        $this->warnings = $warnings;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => 'ok',
            'source_project' => $this->sourceProject,
            'target_project' => $this->targetProject,
            'created' => $this->createdCounters,
            'warnings' => $this->warnings,
        ];
    }
}
