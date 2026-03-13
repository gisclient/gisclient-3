<?php

namespace GisClient\Author\Api\Model;

class ResourceIdentifierData
{
    /**
     * @var string|null
     */
    private $type;

    /**
     * @var string|int|null
     */
    private $id;

    /**
     * @param string|int|null $id
     */
    public function __construct($type = null, $id = null)
    {
        $this->type = $type !== null ? (string) $type : null;
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string|int|null
     */
    public function getId()
    {
        return $this->id;
    }
}
