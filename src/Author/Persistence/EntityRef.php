<?php

namespace GisClient\Author\Persistence;

class EntityRef
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var int|string
     */
    private $id;

    /**
     * @param int|string $id
     */
    public function __construct(string $type, $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }
}
