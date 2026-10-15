<?php

namespace GisClient\MapServer\Writer;

interface MapfileRefresherInterface
{
    /**
     * Regenerate the mapfiles of a project, or of one of its mapsets.
     * Needs no PHP session, so it also runs on stateless (API Token) requests.
     *
     * @param string      $project The project_name
     * @param string|null $mapset  The mapset_name, or null for all mapsets of the project
     * @param bool        $publish If false, generate the temporary mapfiles (tmp. prefix)
     *
     * @throws \Exception if the project or mapset does not exist or the generation registered errors
     */
    public function refresh(string $project, ?string $mapset, bool $publish): void;
}
