<?php

namespace GisClient\Author\Api\Serializer;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Mapper\RowToDtoMapper;
use GisClient\Author\Api\Model\ResourceCollectionData;
use GisClient\Author\Api\Model\ResourceData;

class JsonApiSerializer
{
    /**
     * @var DtoHydrator
     */
    private $dtoHydrator;

    /**
     * @var RowToDtoMapper
     */
    private $rowToDtoMapper;

    /**
     * @var DtoSerializer
     */
    private $dtoSerializer;

    public function __construct(
        ?DtoHydrator $dtoHydrator = null,
        ?RowToDtoMapper $rowToDtoMapper = null,
        ?DtoSerializer $dtoSerializer = null
    ) {
        $this->dtoHydrator = $dtoHydrator ?: new DtoHydrator();
        $this->rowToDtoMapper = $rowToDtoMapper ?: new RowToDtoMapper();
        $this->dtoSerializer = $dtoSerializer ?: new DtoSerializer();
    }

    /**
     * @return JsonApiDto
     */
    public function deserializeRequestBody($content, $expectedType)
    {
        return $this->dtoHydrator->hydrateDocument((string) $content, (string) $expectedType);
    }

    /**
     * @return array<string,mixed>
     */
    public function serializeResource(ResourceData $resource)
    {
        return [
            'data' => $this->dtoSerializer->serialize(
                $this->rowToDtoMapper->map($resource->getDefinition(), $resource->getRow())
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function serializeCollection(ResourceCollectionData $collection)
    {
        $data = [];
        foreach ($collection->getRows() as $row) {
            $data[] = $this->dtoSerializer->serialize(
                $this->rowToDtoMapper->map($collection->getDefinition(), $row)
            );
        }

        return [
            'data' => $data,
            'meta' => [
                'total' => $collection->getTotal(),
                'limit' => $collection->getLimit(),
                'offset' => $collection->getOffset(),
            ],
        ];
    }
}
