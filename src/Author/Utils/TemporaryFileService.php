<?php

namespace GisClient\Author\Utils;

use Symfony\Component\Filesystem\Filesystem;

class TemporaryFileService
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Directory where to create temporary files
     *
     * @var string
     */
    private $tmpDir;

    /**
     * List of created temporary files
     *
     * @var array
     */
    private $files = [];

    /**
     * Constructor
     *
     * @param string $tmpDir
     */
    public function __construct($tmpDir)
    {
        $this->filesystem = new Filesystem;
        $this->tmpDir = $tmpDir;
    }
    
    /**
     * Create a temporary file with prefix
     *
     * @param string $prefix
     * @return string
     */
    public function create($prefix)
    {
        $file = $this->filesystem->tempnam($this->tmpDir, $prefix);
        $this->files[] = $file;
        return $file;
    }

    /**
     * Delete temporary files
     */
    public function cleanup()
    {
        while ($file = array_shift($this->files)) {
            if ($this->filesystem->exists($file)) {
                $this->filesystem->remove($file);
            }
        }
    }
}
